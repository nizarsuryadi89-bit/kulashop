@extends('layouts.app')

@section('title', 'Detail Goods Receipt: ' . $receipt->receipt_number)
@section('page_title', 'Detail Penerimaan Barang')

@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white border-0 py-3">
                <h5 class="mb-0 fw-bold">Items Diterima</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Produk</th>
                                <th>Qty Diterima</th>
                                <th>Harga Beli (Histori)</th>
                                <th>Catatan Item</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($receipt->items as $item)
                            <tr>
                                <td class="ps-3 fw-semibold">
                                    {{ $item->product->product_name }}<br>
                                    <small class="text-muted">{{ $item->product->sku }}</small>
                                </td>
                                <td class="fw-bold text-success">{{ number_format($item->qty_received, 2, ',', '.') }}</td>
                                <td>Rp {{ number_format($item->purchaseOrderItem->unit_price, 0, ',', '.') }}</td>
                                <td>{{ $item->notes ?? '-' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-white border-top">
                <div class="text-muted small">
                    <i class="fas fa-info-circle me-1"></i> Stok produk telah otomatis ditambahkan ke warehouse yang dipilih saat penerimaan barang ini disimpan.
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white border-0 py-3">
                <h5 class="mb-0 fw-bold">Informasi Goods Receipt</h5>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-5">No. GR</dt>
                    <dd class="col-sm-7 fw-bold text-success">{{ $receipt->receipt_number }}</dd>

                    <dt class="col-sm-5">Referensi PO</dt>
                    <dd class="col-sm-7">
                        <a href="{{ route('purchase.orders.show', $receipt->purchase_order_id) }}">{{ $receipt->purchaseOrder->po_number }}</a>
                    </dd>

                    <dt class="col-sm-5">Status PO Saat Ini</dt>
                    <dd class="col-sm-7">
                        @if($receipt->purchaseOrder->status == 'partial_received')
                            <span class="badge bg-warning text-dark">Partial Received</span>
                        @elseif($receipt->purchaseOrder->status == 'received')
                            <span class="badge bg-success">Received</span>
                        @else
                            <span class="badge bg-secondary">{{ $receipt->purchaseOrder->status }}</span>
                        @endif
                    </dd>

                    <dt class="col-sm-5 mt-3">Tgl Terima</dt>
                    <dd class="col-sm-7 mt-3">{{ $receipt->receipt_date->format('d M Y') }}</dd>

                    <dt class="col-sm-5">Surat Jalan</dt>
                    <dd class="col-sm-7">{{ $receipt->delivery_order_number ?? '-' }}</dd>

                    <dt class="col-sm-5 mt-3">Supplier</dt>
                    <dd class="col-sm-7 mt-3">{{ $receipt->supplier->name }}</dd>

                    <dt class="col-sm-5">Warehouse</dt>
                    <dd class="col-sm-7">{{ $receipt->warehouse->name }}</dd>

                    <dt class="col-sm-5 mt-3">Penerima</dt>
                    <dd class="col-sm-7 mt-3">{{ $receipt->receiver->name ?? '-' }}</dd>

                    @if($receipt->notes)
                    <dt class="col-sm-12 mt-3">Catatan</dt>
                    <dd class="col-sm-12">{{ $receipt->notes }}</dd>
                    @endif
                </dl>
            </div>
            <div class="card-footer bg-white">
                <a href="{{ route('purchase.receipts.index') }}" class="btn btn-outline-secondary w-100">Kembali ke Daftar Penerimaan</a>
            </div>
        </div>
    </div>
</div>
@endsection
