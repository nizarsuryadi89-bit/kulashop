<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class PurchaseOrderController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $status = $request->input('status');
        $payment_status = $request->input('payment_status');

        $orders = PurchaseOrder::with(['supplier', 'warehouse', 'creator'])
            ->when($search, function ($q) use ($search) {
                $q->where('po_number', 'like', "%{$search}%")
                  ->orWhereHas('supplier', function ($sq) use ($search) {
                      $sq->where('name', 'like', "%{$search}%");
                  });
            })
            ->when($status, function ($q) use ($status) {
                $q->where('status', $status);
            })
            ->when($payment_status, function ($q) use ($payment_status) {
                $q->where('payment_status', $payment_status);
            })
            ->orderBy('id', 'desc')
            ->paginate(15);

        return view('purchase.orders.index', compact('orders', 'search', 'status', 'payment_status'));
    }

    public function create()
    {
        $suppliers = Supplier::where('status', 'active')->orderBy('name')->get();
        $warehouses = Warehouse::where('status', 'active')->orderBy('name')->get();
        $products = Product::where('status', 'active')->orderBy('product_name')->get();

        return view('purchase.orders.create', compact('suppliers', 'warehouses', 'products'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'order_date' => 'required|date',
            'expected_date' => 'nullable|date|after_or_equal:order_date',
            'notes' => 'nullable|string',
            'discount' => 'nullable|numeric|min:0',
            'shipping_cost' => 'nullable|numeric|min:0',
            'other_cost' => 'nullable|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.qty' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.discount' => 'nullable|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            $yearMonth = date('Ym', strtotime($request->order_date));
            $lastPo = PurchaseOrder::where('po_number', 'like', "PO-{$yearMonth}-%")
                ->orderBy('po_number', 'desc')
                ->lockForUpdate()
                ->first();

            $sequence = 1;
            if ($lastPo) {
                $lastSequence = (int) substr($lastPo->po_number, -4);
                $sequence = $lastSequence + 1;
            }
            $poNumber = sprintf('PO-%s-%04d', $yearMonth, $sequence);

            $po = PurchaseOrder::create([
                'po_number' => $poNumber,
                'supplier_id' => $request->supplier_id,
                'warehouse_id' => $request->warehouse_id,
                'order_date' => $request->order_date,
                'expected_date' => $request->expected_date,
                'notes' => $request->notes,
                'discount' => $request->discount ?? 0,
                'shipping_cost' => $request->shipping_cost ?? 0,
                'other_cost' => $request->other_cost ?? 0,
                'status' => 'draft',
                'payment_status' => 'unpaid',
                'created_by' => auth()->id(),
            ]);

            $subtotal = 0;

            foreach ($request->items as $item) {
                $itemDiscount = $item['discount'] ?? 0;
                $itemSubtotal = ($item['qty'] * $item['unit_price']) - $itemDiscount;
                $subtotal += $itemSubtotal;

                $po->items()->create([
                    'product_id' => $item['product_id'],
                    'qty_ordered' => $item['qty'],
                    'qty_received' => 0,
                    'unit_price' => $item['unit_price'],
                    'discount' => $itemDiscount,
                    'subtotal' => $itemSubtotal,
                ]);
            }

            $grandTotal = $subtotal - $po->discount + $po->shipping_cost + $po->other_cost;

            $po->update([
                'subtotal' => $subtotal,
                'grand_total' => $grandTotal,
            ]);

            DB::commit();

            return redirect()->route('purchase.orders.show', $po->id)->with('success', 'Purchase Order berhasil dibuat.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withInput()->with('error', 'Gagal membuat PO: ' . $e->getMessage());
        }
    }

    public function show(PurchaseOrder $order)
    {
        $order->load(['supplier', 'warehouse', 'items.product', 'creator', 'updater']);
        return view('purchase.orders.show', compact('order'));
    }

    public function edit(PurchaseOrder $order)
    {
        if ($order->status !== 'draft') {
            return redirect()->route('purchase.orders.show', $order->id)->with('error', 'Hanya PO berstatus Draft yang dapat diedit.');
        }

        $order->load(['items']);
        $suppliers = Supplier::where('status', 'active')->orWhere('id', $order->supplier_id)->orderBy('name')->get();
        $warehouses = Warehouse::where('status', 'active')->orWhere('id', $order->warehouse_id)->orderBy('name')->get();
        $products = Product::where('status', 'active')->orderBy('product_name')->get();

        return view('purchase.orders.create', compact('order', 'suppliers', 'warehouses', 'products'));
    }

    public function update(Request $request, PurchaseOrder $order)
    {
        if ($order->status !== 'draft') {
            return redirect()->route('purchase.orders.show', $order->id)->with('error', 'Hanya PO berstatus Draft yang dapat diedit.');
        }

        $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'order_date' => 'required|date',
            'expected_date' => 'nullable|date|after_or_equal:order_date',
            'notes' => 'nullable|string',
            'discount' => 'nullable|numeric|min:0',
            'shipping_cost' => 'nullable|numeric|min:0',
            'other_cost' => 'nullable|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.qty' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.discount' => 'nullable|numeric|min:0',
            'action' => 'required|in:save,order',
        ]);

        try {
            DB::beginTransaction();

            $order->update([
                'supplier_id' => $request->supplier_id,
                'warehouse_id' => $request->warehouse_id,
                'order_date' => $request->order_date,
                'expected_date' => $request->expected_date,
                'notes' => $request->notes,
                'discount' => $request->discount ?? 0,
                'shipping_cost' => $request->shipping_cost ?? 0,
                'other_cost' => $request->other_cost ?? 0,
                'status' => $request->action === 'order' ? 'ordered' : 'draft',
                'updated_by' => auth()->id(),
            ]);

            // Re-create items
            $order->items()->delete();

            $subtotal = 0;
            foreach ($request->items as $item) {
                $itemDiscount = $item['discount'] ?? 0;
                $itemSubtotal = ($item['qty'] * $item['unit_price']) - $itemDiscount;
                $subtotal += $itemSubtotal;

                $order->items()->create([
                    'product_id' => $item['product_id'],
                    'qty_ordered' => $item['qty'],
                    'qty_received' => 0,
                    'unit_price' => $item['unit_price'],
                    'discount' => $itemDiscount,
                    'subtotal' => $itemSubtotal,
                ]);
            }

            $grandTotal = $subtotal - $order->discount + $order->shipping_cost + $order->other_cost;

            $order->update([
                'subtotal' => $subtotal,
                'grand_total' => $grandTotal,
            ]);

            DB::commit();

            $msg = $request->action === 'order' ? 'PO berhasil dikonfirmasi dan status menjadi Ordered.' : 'Draft PO berhasil diperbarui.';
            return redirect()->route('purchase.orders.show', $order->id)->with('success', $msg);
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withInput()->with('error', 'Gagal memperbarui PO: ' . $e->getMessage());
        }
    }
}
