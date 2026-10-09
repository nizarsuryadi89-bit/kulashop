@extends('layouts.app')

@section('title', 'Input Goods Receipt')
@section('page_title', 'Input Goods Receipt')

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white border-0 py-3">
                <h5 class="mb-0 fw-bold">Pilih Purchase Order</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('purchase.receipts.create') }}" method="GET" class="row g-3">
                    <div class="col-md-8">
                        <select name="po_id" class="form-select select2" required>
                            <option value="">Pilih PO yang belum selesai diterima...</option>
                            @foreach($orders as $o)
                                <option value="{{ $o->id }}" {{ (isset($order) && $order->id == $o->id) ? 'selected' : '' }}>
                                    {{ $o->po_number }} - {{ $o->supplier->name ?? '' }} (Sisa: Rp {{ number_format($o->grand_total, 0, ',', '.') }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary w-100">Lanjutkan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @if(isset($order))
    <div class="col-md-12">
        <form action="{{ route('purchase.receipts.store') }}" method="POST">
            @csrf
            <input type="hidden" name="purchase_order_id" value="{{ $order->id }}">
            
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0 fw-bold">Informasi Penerimaan</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Tanggal Diterima <span class="text-danger">*</span></label>
                            <input type="date" name="receipt_date" class="form-control @error('receipt_date') is-invalid @enderror" 
                                   value="{{ old('receipt_date', date('Y-m-d')) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">No. Surat Jalan (Supplier)</label>
                            <input type="text" name="delivery_order_number" class="form-control @error('delivery_order_number') is-invalid @enderror" 
                                   value="{{ old('delivery_order_number') }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Catatan Tambahan</label>
                            <textarea name="notes" class="form-control" rows="2">{{ old('notes') }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white border-0 py-3 d-flex justify-content-between">
                    <h5 class="mb-0 fw-bold">Item yang Diterima</h5>
                    <span class="badge bg-info">PO: {{ $order->po_number }}</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Produk</th>
                                    <th>Qty Order</th>
                                    <th>Telah Diterima</th>
                                    <th>Sisa Belum Diterima</th>
                                    <th width="20%">Qty Diterima Saat Ini <span class="text-danger">*</span></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($order->items as $index => $item)
                                    @php
                                        $sisa = $item->qty_ordered - $item->qty_received;
                                    @endphp
                                    <tr class="{{ $sisa <= 0 ? 'table-secondary' : '' }}">
                                        <td>
                                            <input type="hidden" name="items[{{ $index }}][po_item_id]" value="{{ $item->id }}">
                                            <span class="fw-semibold">{{ $item->product->product_name }}</span><br>
                                            <small class="text-muted">{{ $item->product->sku }}</small>
                                        </td>
                                        <td>{{ number_format($item->qty_ordered, 2, ',', '.') }}</td>
                                        <td>{{ number_format($item->qty_received, 2, ',', '.') }}</td>
                                        <td class="fw-bold text-danger">{{ number_format($sisa, 2, ',', '.') }}</td>
                                        <td>
                                            <input type="number" name="items[{{ $index }}][qty_received]" class="form-control" 
                                                   value="{{ old("items.{$index}.qty_received", $sisa > 0 ? $sisa : 0) }}" 
                                                   min="0" max="{{ $sisa }}" step="0.01" {{ $sisa <= 0 ? 'readonly' : '' }}>
                                            <small class="text-muted">Isi 0 jika tidak ada barang datang</small>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer bg-white border-top text-end py-3">
                    <a href="{{ route('purchase.receipts.index') }}" class="btn btn-outline-secondary me-2">Batal</a>
                    <button type="submit" class="btn btn-primary" onclick="return confirm('Pastikan qty yang dimasukkan sudah sesuai dengan fisik barang. Lanjutkan?')">
                        <i class="fas fa-check-circle me-1"></i> Simpan Penerimaan Barang
                    </button>
                </div>
            </div>
        </form>
    </div>
    @endif
</div>
@endsection
