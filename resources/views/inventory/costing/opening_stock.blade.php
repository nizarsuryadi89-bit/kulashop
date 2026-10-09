@extends('layouts.app')
@section('title', 'Set Opening Stock Cost')

@section('content')
<div class="container-fluid">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <h4 class="mb-0 fw-bold text-dark" style="letter-spacing: -0.5px;">Set Opening Stock Cost <i class="fas fa-box-open text-primary ms-2"></i></h4>
        <a href="{{ route('inventory.valuation') }}" class="btn btn-light btn-sm fw-bold">
            <i class="fas fa-arrow-left me-1"></i> Kembali
        </a>
    </div>

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-md-8">
            <div class="card border-0 shadow-sm" style="border-radius: 12px;">
                <div class="card-body p-4">
                    <form action="{{ route('inventory.costing.opening_stock.store') }}" method="POST">
                        @csrf
                        
                        <div class="mb-4">
                            <label class="form-label fw-bold text-dark">Pilih Produk <span class="text-danger">*</span></label>
                            <select name="product_id" class="form-select @error('product_id') is-invalid @enderror" required>
                                <option value="">-- Pilih Produk --</option>
                                @foreach($products as $product)
                                    <option value="{{ $product->id }}" {{ old('product_id') == $product->id ? 'selected' : '' }}>
                                        [{{ $product->sku }}] {{ $product->product_name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('product_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold text-dark">Pilih Gudang <span class="text-danger">*</span></label>
                            <select name="warehouse_id" class="form-select @error('warehouse_id') is-invalid @enderror" required>
                                <option value="">-- Pilih Gudang --</option>
                                @foreach($warehouses as $warehouse)
                                    <option value="{{ $warehouse->id }}" {{ old('warehouse_id') == $warehouse->id ? 'selected' : '' }}>
                                        {{ $warehouse->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('warehouse_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-dark">Quantity (Fisik) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" step="0.01" class="form-control @error('quantity') is-invalid @enderror" name="quantity" value="{{ old('quantity') }}" required>
                                    <span class="input-group-text bg-light">Unit</span>
                                </div>
                                @error('quantity')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                                <div class="form-text text-muted">Stok fisik produk akan bertambah sejumlah ini.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-dark">Unit Cost (HPP per Unit) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">Rp</span>
                                    <input type="number" step="1" class="form-control @error('unit_cost') is-invalid @enderror" name="unit_cost" value="{{ old('unit_cost') }}" required>
                                </div>
                                @error('unit_cost')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                                <div class="form-text text-muted">Harga perolehan rata-rata per unit untuk stok awal ini.</div>
                            </div>
                        </div>

                        <hr class="text-muted opacity-25">
                        
                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary px-4 fw-bold shadow-sm">
                                <i class="fas fa-save me-1"></i> Simpan Opening Stock
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card bg-info bg-opacity-10 border-info border-opacity-25 shadow-sm" style="border-radius: 12px;">
                <div class="card-body p-4">
                    <h5 class="fw-bold text-info-emphasis mb-3"><i class="fas fa-info-circle me-2"></i> Informasi Opening Stock</h5>
                    <p class="text-secondary small mb-2">
                        Opening stock digunakan untuk <strong>menginisialisasi saldo stok dan harga rata-rata (Weighted Average)</strong> pertama kali ke dalam sistem sebelum ada siklus Goods Receipt.
                    </p>
                    <p class="text-secondary small mb-2">
                        Proses ini akan:
                        <ul class="text-secondary small mb-0 ps-3">
                            <li>Menambah kuantitas di <strong>Warehouse Stock</strong>.</li>
                            <li>Membuat <strong>Inventory Cost Layer</strong> (Opening).</li>
                            <li>Menginisialisasi atau memperbarui nilai rata-rata cost di <strong>Cost Summary</strong>.</li>
                        </ul>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
