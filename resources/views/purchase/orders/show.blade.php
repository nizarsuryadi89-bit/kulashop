@extends('layouts.app')

@section('title', 'Detail PO: ' . $order->po_number)
@section('page_title', 'Detail Purchase Order')

@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold">Items Purchase Order</h5>
                @if($order->status == 'draft')
                <a href="{{ route('purchase.orders.edit', $order->id) }}" class="btn btn-sm btn-primary">
                    <i class="fas fa-edit me-1"></i> Edit PO
                </a>
                @endif
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Produk</th>
                                <th>Qty Order</th>
                                <th>Qty Diterima</th>
                                <th>Harga Satuan</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($order->items as $item)
                            <tr>
                                <td class="ps-3 fw-semibold">
                                    {{ $item->product->product_name }}<br>
                                    <small class="text-muted">{{ $item->product->sku }}</small>
                                </td>
                                <td>{{ number_format($item->qty_ordered, 2, ',', '.') }}</td>
                                <td>
                                    @if($item->qty_received < $item->qty_ordered)
                                        <span class="text-warning">{{ number_format($item->qty_received, 2, ',', '.') }}</span>
                                    @else
                                        <span class="text-success">{{ number_format($item->qty_received, 2, ',', '.') }}</span>
                                    @endif
                                </td>
                                <td>Rp {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                                <td class="fw-bold">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-white border-top text-end">
                <div class="row">
                    <div class="col-md-8 text-end text-muted">Subtotal:</div>
                    <div class="col-md-4 fw-bold">Rp {{ number_format($order->subtotal, 0, ',', '.') }}</div>
                </div>
                <div class="row">
                    <div class="col-md-8 text-end text-muted">Diskon Tambahan:</div>
                    <div class="col-md-4 text-danger">- Rp {{ number_format($order->discount, 0, ',', '.') }}</div>
                </div>
                <div class="row">
                    <div class="col-md-8 text-end text-muted">Biaya Pengiriman:</div>
                    <div class="col-md-4">+ Rp {{ number_format($order->shipping_cost, 0, ',', '.') }}</div>
                </div>
                <div class="row mb-2">
                    <div class="col-md-8 text-end text-muted">Biaya Lainnya:</div>
                    <div class="col-md-4">+ Rp {{ number_format($order->other_cost, 0, ',', '.') }}</div>
                </div>
                <div class="row border-top pt-2">
                    <div class="col-md-8 text-end fw-bold fs-5">Grand Total:</div>
                    <div class="col-md-4 fw-bold fs-5 text-primary">Rp {{ number_format($order->grand_total, 0, ',', '.') }}</div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white border-0 py-3">
                <h5 class="mb-0 fw-bold">Informasi PO</h5>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-5">Nomor PO</dt>
                    <dd class="col-sm-7 fw-bold">{{ $order->po_number }}</dd>

                    <dt class="col-sm-5">Status PO</dt>
                    <dd class="col-sm-7">
                        @if($order->status == 'draft') <span class="badge bg-secondary">Draft</span>
                        @elseif($order->status == 'ordered') <span class="badge bg-primary">Ordered</span>
                        @elseif($order->status == 'partial_received') <span class="badge bg-warning text-dark">Partial Received</span>
                        @elseif($order->status == 'received') <span class="badge bg-success">Received</span>
                        @else <span class="badge bg-danger">Cancelled</span> @endif
                    </dd>

                    <dt class="col-sm-5">Pembayaran</dt>
                    <dd class="col-sm-7">
                        @if($order->payment_status == 'unpaid') <span class="badge bg-danger">Unpaid</span>
                        @elseif($order->payment_status == 'partial') <span class="badge bg-warning text-dark">Partial</span>
                        @else <span class="badge bg-success">Paid</span> @endif
                        <div class="mt-1 small">
                            Telah dibayar: Rp {{ number_format($order->paid_amount, 0, ',', '.') }}<br>
                            Sisa: Rp {{ number_format($order->outstanding_amount, 0, ',', '.') }}
                        </div>
                    </dd>

                    <dt class="col-sm-5 mt-3">Supplier</dt>
                    <dd class="col-sm-7 mt-3">{{ $order->supplier->name }}<br><small class="text-muted">{{ $order->supplier->phone }}</small></dd>

                    <dt class="col-sm-5 mt-2">Warehouse</dt>
                    <dd class="col-sm-7 mt-2">{{ $order->warehouse->name }}</dd>

                    <dt class="col-sm-5 mt-3">Tgl Order</dt>
                    <dd class="col-sm-7 mt-3">{{ $order->order_date->format('d M Y') }}</dd>

                    <dt class="col-sm-5">Est. Tiba</dt>
                    <dd class="col-sm-7">{{ $order->expected_date ? $order->expected_date->format('d M Y') : '-' }}</dd>

                    @if($order->notes)
                    <dt class="col-sm-12 mt-3">Catatan</dt>
                    <dd class="col-sm-12">{{ $order->notes }}</dd>
                    @endif
                </dl>
            </div>
            <div class="card-footer bg-white">
                <a href="{{ route('purchase.orders.index') }}" class="btn btn-outline-secondary w-100">Kembali ke Daftar PO</a>
            </div>
        </div>
    </div>
</div>
@endsection
