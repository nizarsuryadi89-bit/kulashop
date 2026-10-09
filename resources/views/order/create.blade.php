@extends('layouts.app')

@section('title', 'Buat Pesanan Manual')
@section('page_title', 'Buat Pesanan Manual')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white border-0 pt-4 pb-0">
                <h5 class="mb-0 fw-bold">Form Pesanan Manual</h5>
            </div>
            <div class="card-body">
                @if($errors->any())
                    <div class="alert alert-danger rounded-3 border-0 shadow-sm">
                        <ul class="mb-0">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('order.store') }}" method="POST" id="orderForm">
                    @csrf
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Pilih Pelanggan <span class="text-danger">*</span></label>
                            <select name="customer_id" class="form-select bg-light" required>
                                <option value="">-- Pilih Pelanggan --</option>
                                @foreach($customers as $customer)
                                    <option value="{{ $customer->id }}" {{ old('customer_id') == $customer->id ? 'selected' : '' }}>
                                        {{ $customer->name }} {{ $customer->phone ? '('.$customer->phone.')' : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Toko / Saluran <span class="text-danger">*</span></label>
                            <select name="marketplace_account_id" class="form-select bg-light" required>
                                <option value="">-- Pilih Toko --</option>
                                @foreach($marketplaces as $marketplace)
                                    <option value="{{ $marketplace->id }}" {{ old('marketplace_account_id') == $marketplace->id ? 'selected' : '' }}>
                                        {{ $marketplace->account_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Tanggal Pesanan <span class="text-danger">*</span></label>
                            <input type="datetime-local" name="order_date" class="form-control bg-light" value="{{ old('order_date', date('Y-m-d\TH:i')) }}" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Status Pesanan <span class="text-danger">*</span></label>
                            <select name="status" class="form-select bg-light" required>
                                <option value="COMPLETED" {{ old('status') == 'COMPLETED' ? 'selected' : '' }}>COMPLETED</option>
                                <option value="PENDING" {{ old('status') == 'PENDING' ? 'selected' : '' }}>PENDING</option>
                                <option value="CANCELLED" {{ old('status') == 'CANCELLED' ? 'selected' : '' }}>CANCELLED</option>
                            </select>
                        </div>
                    </div>

                    <hr class="my-4 text-muted">
                    <h6 class="fw-bold mb-3">Item Produk <span class="text-danger">*</span></h6>
                    
                    <div id="product-container">
                        <div class="product-item border rounded p-3 mb-3 bg-light position-relative">
                            <div class="row">
                                <div class="col-md-6 mb-2">
                                    <label class="form-label small fw-medium">Pilih Produk</label>
                                    <select name="products[0][id]" class="form-select" required onchange="updatePrice(this)">
                                        <option value="">-- Pilih Produk --</option>
                                        @foreach($products as $product)
                                            <option value="{{ $product->id }}" data-price="{{ $product->hpp }}">
                                                {{ $product->product_name }} {{ $product->variant_name ? '('.$product->variant_name.')' : '' }} - Rp {{ number_format($product->hpp, 0, ',', '.') }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3 mb-2">
                                    <label class="form-label small fw-medium">Harga</label>
                                    <input type="number" name="products[0][price]" class="form-control price-input" required min="0">
                                </div>
                                <div class="col-md-3 mb-2">
                                    <label class="form-label small fw-medium">Jumlah</label>
                                    <input type="number" name="products[0][quantity]" class="form-control qty-input" required min="1" value="1">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <button type="button" class="btn btn-sm btn-outline-primary mb-4" onclick="addProductItem()">
                        <i class="fas fa-plus"></i> Tambah Produk Lain
                    </button>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('order.index') }}" class="btn btn-light">Batal</a>
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="fas fa-save me-1"></i> Buat Pesanan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    let productIndex = 1;
    function updatePrice(selectElement) {
        const selectedOption = selectElement.options[selectElement.selectedIndex];
        const price = selectedOption.getAttribute('data-price');
        const priceInput = selectElement.closest('.product-item').querySelector('.price-input');
        if(priceInput) {
            priceInput.value = price;
        }
    }

    function addProductItem() {
        const container = document.getElementById('product-container');
        const template = `
            <div class="product-item border rounded p-3 mb-3 bg-light position-relative">
                <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0 m-2" onclick="this.closest('.product-item').remove()">
                    <i class="fas fa-times"></i>
                </button>
                <div class="row">
                    <div class="col-md-6 mb-2">
                        <label class="form-label small fw-medium">Pilih Produk</label>
                        <select name="products[${productIndex}][id]" class="form-select" required onchange="updatePrice(this)">
                            <option value="">-- Pilih Produk --</option>
                            @foreach($products as $product)
                                <option value="{{ $product->id }}" data-price="{{ $product->hpp }}">
                                    {{ $product->product_name }} {{ $product->variant_name ? '('.$product->variant_name.')' : '' }} - Rp {{ number_format($product->hpp, 0, ',', '.') }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 mb-2">
                        <label class="form-label small fw-medium">Harga</label>
                        <input type="number" name="products[${productIndex}][price]" class="form-control price-input" required min="0">
                    </div>
                    <div class="col-md-3 mb-2">
                        <label class="form-label small fw-medium">Jumlah</label>
                        <input type="number" name="products[${productIndex}][quantity]" class="form-control qty-input" required min="1" value="1">
                    </div>
                </div>
            </div>
        `;
        container.insertAdjacentHTML('beforeend', template);
        productIndex++;
    }
</script>
@endpush
