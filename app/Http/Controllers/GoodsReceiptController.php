<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\GoodsReceipt;
use App\Models\PurchaseOrder;
use App\Models\WarehouseStock;
use App\Models\StockMovement;
use App\Models\ProductPurchasePriceHistory;
use Illuminate\Support\Facades\DB;

class GoodsReceiptController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        
        $receipts = GoodsReceipt::with(['purchaseOrder', 'supplier', 'warehouse', 'receiver'])
            ->when($search, function ($q) use ($search) {
                $q->where('receipt_number', 'like', "%{$search}%")
                  ->orWhere('delivery_order_number', 'like', "%{$search}%")
                  ->orWhereHas('purchaseOrder', function($q) use ($search) {
                      $q->where('po_number', 'like', "%{$search}%");
                  });
            })
            ->orderBy('id', 'desc')
            ->paginate(15);

        return view('purchase.receipts.index', compact('receipts', 'search'));
    }

    public function create(Request $request)
    {
        $po_id = $request->input('po_id');
        $order = null;
        
        if ($po_id) {
            $order = PurchaseOrder::with(['items.product'])->whereIn('status', ['ordered', 'partial_received'])->findOrFail($po_id);
        }

        $orders = PurchaseOrder::whereIn('status', ['ordered', 'partial_received'])->orderBy('po_number', 'desc')->get();

        return view('purchase.receipts.create', compact('order', 'orders'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'purchase_order_id' => 'required|exists:purchase_orders,id',
            'receipt_date' => 'required|date',
            'delivery_order_number' => 'nullable|string',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.po_item_id' => 'required|exists:purchase_order_items,id',
            'items.*.qty_received' => 'required|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            $order = PurchaseOrder::with('items')->whereIn('status', ['ordered', 'partial_received'])->findOrFail($request->purchase_order_id);

            // Cek adakah qty yang > 0
            $totalReceivedInput = 0;
            foreach ($request->items as $item) {
                $totalReceivedInput += $item['qty_received'];
            }

            if ($totalReceivedInput <= 0) {
                throw new \Exception('Tidak ada qty barang yang diterima (semua 0).');
            }

            // Generate GR Number
            $yearMonth = date('Ym', strtotime($request->receipt_date));
            $lastGr = GoodsReceipt::where('receipt_number', 'like', "GR-{$yearMonth}-%")
                ->orderBy('receipt_number', 'desc')
                ->lockForUpdate()
                ->first();

            $sequence = 1;
            if ($lastGr) {
                $lastSequence = (int) substr($lastGr->receipt_number, -4);
                $sequence = $lastSequence + 1;
            }
            $grNumber = sprintf('GR-%s-%04d', $yearMonth, $sequence);

            $gr = GoodsReceipt::create([
                'receipt_number' => $grNumber,
                'purchase_order_id' => $order->id,
                'supplier_id' => $order->supplier_id,
                'warehouse_id' => $order->warehouse_id,
                'receipt_date' => $request->receipt_date,
                'delivery_order_number' => $request->delivery_order_number,
                'status' => 'received',
                'notes' => $request->notes,
                'received_by' => auth()->id(),
            ]);

            $isFullyReceived = true;

            foreach ($request->items as $itemData) {
                $qtyReceived = (float) $itemData['qty_received'];
                if ($qtyReceived <= 0) continue;

                $poItem = $order->items->where('id', $itemData['po_item_id'])->first();
                if (!$poItem) continue;

                $remainingQty = $poItem->qty_ordered - $poItem->qty_received;
                
                if ($qtyReceived > $remainingQty) {
                    throw new \Exception("Over-receiving tidak diizinkan. Qty maksimal untuk {$poItem->product->product_name} adalah {$remainingQty}.");
                }

                // 1. Catat item GR
                $gr->items()->create([
                    'purchase_order_item_id' => $poItem->id,
                    'product_id' => $poItem->product_id,
                    'qty_received' => $qtyReceived,
                ]);

                // 2. Update qty received di PO Item
                $poItem->increment('qty_received', $qtyReceived);

                // Cek apakah masih ada sisa
                if ($poItem->qty_received < $poItem->qty_ordered) {
                    $isFullyReceived = false;
                }

                // 3. Update Warehouse Stock & Stock Movement
                $warehouseStock = WarehouseStock::firstOrCreate([
                    'warehouse_id' => $order->warehouse_id,
                    'product_id' => $poItem->product_id,
                ], [
                    'stock' => 0
                ]);

                $warehouseStock->increment('stock', $qtyReceived);
                
                // Update global product stock
                $poItem->product->increment('stock', $qtyReceived);

                StockMovement::create([
                    'product_id' => $poItem->product_id,
                    'warehouse_id' => $order->warehouse_id,
                    'type' => 'in',
                    'quantity' => $qtyReceived,
                    'reference_type' => 'goods_receipt',
                    'reference_id' => $gr->id,
                    'description' => "Penerimaan barang dari PO {$order->po_number}",
                    'created_by' => auth()->id(),
                ]);

                // 4. Catat Price History
                ProductPurchasePriceHistory::create([
                    'product_id' => $poItem->product_id,
                    'goods_receipt_id' => $gr->id,
                    'purchase_price' => $poItem->unit_price,
                    'qty' => $qtyReceived,
                ]);
            }

            // Jika ada PO item lain yang belum full, set flag ke false
            foreach ($order->items as $checkItem) {
                if ($checkItem->qty_received < $checkItem->qty_ordered) {
                    $isFullyReceived = false;
                }
            }

            // Update PO Status
            $order->update([
                'status' => $isFullyReceived ? 'received' : 'partial_received'
            ]);

            // Hitung Costing & Inventory Value (Step 16)
            $gr->load(['items.purchaseOrderItem', 'purchaseOrder']);
            app(\App\Services\InventoryCostService::class)->handlePurchaseReceipt($gr);

            DB::commit();

            return redirect()->route('purchase.receipts.show', $gr->id)->with('success', 'Goods Receipt berhasil disimpan dan stok beserta cost telah diperbarui.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withInput()->with('error', 'Gagal memproses penerimaan barang: ' . $e->getMessage());
        }
    }

    public function show(GoodsReceipt $receipt)
    {
        $receipt->load(['purchaseOrder', 'supplier', 'warehouse', 'items.product', 'receiver']);
        return view('purchase.receipts.show', compact('receipt'));
    }
}
