@extends('layouts.app')

@section('title', isset($order) ? 'Edit PO' : 'Buat PO Baru')
@section('page_title', 'Purchase Order')

@section('content')
<div class="card shadow-sm border-0">
    <div class="card-header bg-white border-0 py-3">
        <h5 class="mb-0 fw-bold">{{ isset($order) ? 'Edit PO: ' . $order->po_number : 'Buat PO Baru' }}</h5>
    </div>
    <div class="card-body">
        <form action="{{ isset($order) ? route('purchase.orders.update', $order->id) : route('purchase.orders.store') }}" method="POST" id="poForm">
            @csrf
            @if(isset($order))
                @method('PUT')
            @endif

            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label class="form-label">Supplier <span class="text-danger">*</span></label>
                    <select name="supplier_id" class="form-select @error('supplier_id') is-invalid @enderror" required>
                        <option value="">Pilih Supplier...</option>
                        @foreach($suppliers as $s)
                            <option value="{{ $s->id }}" {{ old('supplier_id', $order->supplier_id ?? '') == $s->id ? 'selected' : '' }}>
                                {{ $s->supplier_code }} - {{ $s->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('supplier_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Warehouse <span class="text-danger">*</span></label>
                    <select name="warehouse_id" class="form-select @error('warehouse_id') is-invalid @enderror" required>
                        <option value="">Pilih Warehouse...</option>
                        @foreach($warehouses as $w)
                            <option value="{{ $w->id }}" {{ old('warehouse_id', $order->warehouse_id ?? '') == $w->id ? 'selected' : '' }}>
                                {{ $w->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('warehouse_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">Tanggal Order <span class="text-danger">*</span></label>
                    <input type="date" name="order_date" class="form-control @error('order_date') is-invalid @enderror" 
                           value="{{ old('order_date', isset($order) ? $order->order_date->format('Y-m-d') : date('Y-m-d')) }}" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Tanggal Estimasi Diterima</label>
                    <input type="date" name="expected_date" class="form-control @error('expected_date') is-invalid @enderror" 
                           value="{{ old('expected_date', isset($order) && $order->expected_date ? $order->expected_date->format('Y-m-d') : '') }}">
                </div>
            </div>

            <h6 class="fw-bold mb-3 border-bottom pb-2">Item Pembelian</h6>
            
            <div class="table-responsive mb-3">
                <table class="table table-bordered align-middle" id="itemsTable">
                    <thead class="table-light">
                        <tr>
                            <th width="35%">Produk</th>
                            <th width="15%">Qty</th>
                            <th width="20%">Harga Satuan</th>
                            <th width="15%">Diskon / Item</th>
                            <th width="10%">Subtotal</th>
                            <th width="5%" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="itemsBody">
                        @php
                            $oldItems = old('items', isset($order) ? $order->items->toArray() : []);
                        @endphp
                        
                        @if(count($oldItems) > 0)
                            @foreach($oldItems as $index => $item)
                                @include('purchase.orders._item_row', ['index' => $index, 'item' => $item])
                            @endforeach
                        @else
                            @include('purchase.orders._item_row', ['index' => 0, 'item' => null])
                        @endif
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="6">
                                <button type="button" class="btn btn-sm btn-outline-primary" id="addItemBtn">
                                    <i class="fas fa-plus me-1"></i> Tambah Item
                                </button>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Catatan Tambahan</label>
                        <textarea name="notes" class="form-control" rows="4">{{ old('notes', $order->notes ?? '') }}</textarea>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card bg-light border-0">
                        <div class="card-body">
                            <div class="row mb-2">
                                <div class="col-sm-6 fw-bold">Subtotal Item</div>
                                <div class="col-sm-6 text-end" id="subtotalDisplay">Rp 0</div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-sm-6">Diskon Tambahan (Rp)</div>
                                <div class="col-sm-6">
                                    <input type="number" name="discount" id="globalDiscount" class="form-control form-control-sm text-end input-calc" 
                                           value="{{ old('discount', floatval($order->discount ?? 0)) }}">
                                </div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-sm-6">Biaya Pengiriman (Rp)</div>
                                <div class="col-sm-6">
                                    <input type="number" name="shipping_cost" id="shippingCost" class="form-control form-control-sm text-end input-calc" 
                                           value="{{ old('shipping_cost', floatval($order->shipping_cost ?? 0)) }}">
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-sm-6">Biaya Lainnya (Rp)</div>
                                <div class="col-sm-6">
                                    <input type="number" name="other_cost" id="otherCost" class="form-control form-control-sm text-end input-calc" 
                                           value="{{ old('other_cost', floatval($order->other_cost ?? 0)) }}">
                                </div>
                            </div>
                            <div class="row border-top pt-2">
                                <div class="col-sm-6 fw-bold fs-5">Grand Total</div>
                                <div class="col-sm-6 text-end fw-bold fs-5 text-primary" id="grandTotalDisplay">Rp 0</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-4 pt-3 border-top text-end">
                <a href="{{ route('purchase.orders.index') }}" class="btn btn-outline-secondary me-2">Batal</a>
                <button type="submit" name="action" value="save" class="btn btn-secondary me-2">
                    <i class="fas fa-save me-1"></i> Simpan Draft
                </button>
                @if(isset($order))
                <button type="submit" name="action" value="order" class="btn btn-primary" onclick="return confirm('Konfirmasi PO ini? Status akan menjadi Ordered.')">
                    <i class="fas fa-paper-plane me-1"></i> Konfirmasi & Order
                </button>
                @endif
            </div>
        </form>
    </div>
</div>

<template id="itemRowTemplate">
    @include('purchase.orders._item_row', ['index' => '__INDEX__', 'item' => null])
</template>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    let itemIndex = {{ count($oldItems) > 0 ? count($oldItems) : 1 }};
    
    function calculateTotal() {
        let subtotal = 0;
        document.querySelectorAll('#itemsBody tr').forEach(row => {
            const qty = parseFloat(row.querySelector('.input-qty').value) || 0;
            const price = parseFloat(row.querySelector('.input-price').value) || 0;
            const discount = parseFloat(row.querySelector('.input-discount').value) || 0;
            
            const rowSubtotal = (qty * price) - discount;
            row.querySelector('.row-subtotal-display').textContent = 'Rp ' + Math.max(0, rowSubtotal).toLocaleString('id-ID');
            subtotal += Math.max(0, rowSubtotal);
        });

        document.getElementById('subtotalDisplay').textContent = 'Rp ' + subtotal.toLocaleString('id-ID');

        const globalDiscount = parseFloat(document.getElementById('globalDiscount').value) || 0;
        const shippingCost = parseFloat(document.getElementById('shippingCost').value) || 0;
        const otherCost = parseFloat(document.getElementById('otherCost').value) || 0;

        const grandTotal = subtotal - globalDiscount + shippingCost + otherCost;
        document.getElementById('grandTotalDisplay').textContent = 'Rp ' + Math.max(0, grandTotal).toLocaleString('id-ID');
    }

    document.getElementById('addItemBtn').addEventListener('click', function() {
        const template = document.getElementById('itemRowTemplate').innerHTML;
        const newRow = template.replace(/__INDEX__/g, itemIndex);
        document.getElementById('itemsBody').insertAdjacentHTML('beforeend', newRow);
        itemIndex++;
        attachListeners();
    });

    function attachListeners() {
        document.querySelectorAll('.btn-remove-item').forEach(btn => {
            btn.addEventListener('click', function() {
                if(document.querySelectorAll('#itemsBody tr').length > 1) {
                    this.closest('tr').remove();
                    calculateTotal();
                } else {
                    alert('Minimal harus ada 1 item.');
                }
            });
        });

        document.querySelectorAll('.input-calc').forEach(input => {
            input.addEventListener('input', calculateTotal);
        });
        
        document.querySelectorAll('.input-qty, .input-price, .input-discount').forEach(input => {
            input.addEventListener('input', calculateTotal);
        });
    }

    attachListeners();
    calculateTotal();
});
</script>
@endpush
@endsection
