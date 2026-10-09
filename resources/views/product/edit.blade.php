@extends('layouts.app')

@section('title', 'Edit Produk')
@section('page_title', 'Edit Produk')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-10">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white border-0 pt-4 pb-0">
                <h5 class="mb-0 fw-bold">Form Edit Produk Master</h5>
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

                <form action="{{ route('product.update', $product->id) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">SKU Produk <span class="text-danger">*</span></label>
                            <input type="text" name="sku" class="form-control bg-light" value="{{ old('sku', $product->sku) }}" required placeholder="Contoh: Baju-M-Merah">
                            <small class="text-muted">SKU Master. Harus unik.</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Nama Produk <span class="text-danger">*</span></label>
                            <input type="text" name="product_name" class="form-control bg-light" value="{{ old('product_name', $product->product_name) }}" required placeholder="Contoh: Kemeja Pria">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Varian</label>
                            <input type="text" name="variant_name" class="form-control bg-light" value="{{ old('variant_name', $product->variant_name) }}" placeholder="Contoh: Merah, M">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Kategori</label>
                            <input type="text" name="category" class="form-control bg-light" value="{{ old('category', $product->category) }}" placeholder="Contoh: Pakaian">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold">Satuan</label>
                            <input type="text" name="unit" class="form-control bg-light" value="{{ old('unit', $product->unit) }}" placeholder="Contoh: Pcs, Lusin">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold">HPP <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light">Rp</span>
                                <input type="number" name="hpp" class="form-control bg-light" value="{{ old('hpp', (int)$product->hpp) }}" required min="0">
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold">Harga Jual <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light">Rp</span>
                                <input type="number" name="selling_price" class="form-control bg-light" value="{{ old('selling_price', (int)$product->selling_price) }}" required min="0">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold">Minimum Stock <span class="text-danger">*</span></label>
                            <input type="number" name="minimum_stock" class="form-control bg-light" value="{{ old('minimum_stock', (int)$product->minimum_stock) }}" required min="0">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold">Status</label>
                            <select name="status" class="form-select bg-light">
                                <option value="active" {{ old('status', $product->status) == 'active' ? 'selected' : '' }}>Aktif</option>
                                <option value="inactive" {{ old('status', $product->status) == 'inactive' ? 'selected' : '' }}>Nonaktif</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold">Merek (Brand)</label>
                            <input type="text" name="brand" class="form-control bg-light" value="{{ old('brand', $product->brand) }}" placeholder="Contoh: Erigo">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Gambar Produk</label>
                        @if($product->image)
                            <div class="mb-2">
                                <img src="{{ Storage::url($product->image) }}" alt="Current Image" class="img-thumbnail" style="max-height: 150px;">
                            </div>
                        @endif
                        <input type="file" name="image" class="form-control bg-light" accept="image/png, image/jpeg, image/jpg">
                        <small class="text-muted">Biarkan kosong jika tidak ingin mengubah gambar. Maksimal 2MB.</small>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold">Deskripsi Produk</label>
                        <textarea name="description" class="form-control bg-light" rows="4" placeholder="Tuliskan deskripsi lengkap produk...">{{ old('description', $product->description) }}</textarea>
                    </div>

                    <div class="alert alert-info rounded-3 border-0 shadow-sm d-flex">
                        <div class="me-3 mt-1">
                            <i class="fas fa-info-circle fa-2x text-info"></i>
                        </div>
                        <div>
                            <strong>Informasi Penting:</strong> Perubahan pada Master Data Produk (terutama HPP dan Harga Jual) 
                            hanya akan berlaku untuk transaksi yang terjadi ke depannya. Data historis transaksi 
                            (penjualan masa lalu) tidak akan berubah demi menjaga integritas laporan keuangan.
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('product.index') }}" class="btn btn-light">Batal</a>
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="fas fa-save me-1"></i> Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
