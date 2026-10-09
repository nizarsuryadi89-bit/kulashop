<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\PurchasePayment;
use App\Models\PurchaseOrder;
use Illuminate\Support\Facades\DB;

class PurchasePaymentController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        
        $payments = PurchasePayment::with(['purchaseOrder.supplier', 'creator'])
            ->when($search, function ($q) use ($search) {
                $q->where('payment_number', 'like', "%{$search}%")
                  ->orWhere('reference_number', 'like', "%{$search}%")
                  ->orWhereHas('purchaseOrder', function($sq) use ($search) {
                      $sq->where('po_number', 'like', "%{$search}%")
                         ->orWhereHas('supplier', function($ssq) use ($search) {
                             $ssq->where('name', 'like', "%{$search}%");
                         });
                  });
            })
            ->orderBy('id', 'desc')
            ->paginate(15);

        return view('purchase.payments.index', compact('payments', 'search'));
    }

    public function create(Request $request)
    {
        $po_id = $request->input('po_id');
        $order = null;
        
        if ($po_id) {
            $order = PurchaseOrder::whereIn('status', ['ordered', 'partial_received', 'received'])
                                  ->whereIn('payment_status', ['unpaid', 'partial'])
                                  ->findOrFail($po_id);
        }

        $orders = PurchaseOrder::with('supplier')
                    ->whereIn('status', ['ordered', 'partial_received', 'received'])
                    ->whereIn('payment_status', ['unpaid', 'partial'])
                    ->orderBy('po_number', 'desc')
                    ->get();

        return view('purchase.payments.create', compact('order', 'orders'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'purchase_order_id' => 'required|exists:purchase_orders,id',
            'payment_date' => 'required|date',
            'amount' => 'required|numeric|min:1',
            'payment_method' => 'required|string|max:50',
            'reference_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $order = PurchaseOrder::whereIn('status', ['ordered', 'partial_received', 'received'])
                                  ->lockForUpdate()
                                  ->findOrFail($request->purchase_order_id);

            $amount = (float) $request->amount;
            
            if ($amount > $order->outstanding_amount) {
                throw new \Exception("Jumlah pembayaran (Rp " . number_format($amount, 0, ',', '.') . ") melebihi sisa tagihan (Rp " . number_format($order->outstanding_amount, 0, ',', '.') . ").");
            }

            // Generate Payment Number
            $yearMonth = date('Ym', strtotime($request->payment_date));
            $lastPay = PurchasePayment::where('payment_number', 'like', "PAY-{$yearMonth}-%")
                ->orderBy('payment_number', 'desc')
                ->lockForUpdate()
                ->first();

            $sequence = 1;
            if ($lastPay) {
                $lastSequence = (int) substr($lastPay->payment_number, -4);
                $sequence = $lastSequence + 1;
            }
            $payNumber = sprintf('PAY-%s-%04d', $yearMonth, $sequence);

            $payment = PurchasePayment::create([
                'payment_number' => $payNumber,
                'purchase_order_id' => $order->id,
                'payment_date' => $request->payment_date,
                'amount' => $amount,
                'payment_method' => $request->payment_method,
                'reference_number' => $request->reference_number,
                'notes' => $request->notes,
                'created_by' => auth()->id(),
            ]);

            // Update PO paid amount & status
            $newPaidAmount = $order->paid_amount + $amount;
            $newPaymentStatus = ($newPaidAmount >= $order->grand_total) ? 'paid' : 'partial';

            $order->update([
                'paid_amount' => $newPaidAmount,
                'payment_status' => $newPaymentStatus
            ]);

            DB::commit();

            return redirect()->route('purchase.payments.show', $payment->id)->with('success', 'Pembayaran berhasil disimpan.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withInput()->with('error', 'Gagal memproses pembayaran: ' . $e->getMessage());
        }
    }

    public function show(PurchasePayment $payment)
    {
        $payment->load(['purchaseOrder.supplier', 'creator']);
        return view('purchase.payments.show', compact('payment'));
    }
}
