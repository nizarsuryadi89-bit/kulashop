@extends('layouts.app')

@section('title', 'Mapping SKU Marketplace')
@section('page_title', 'Mapping SKU Marketplace')

@section('content')
<div class="row">
    <div class="col-md-12">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show rounded-3 border-0 shadow-sm" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show rounded-3 border-0 shadow-sm" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show rounded-3 border-0 shadow-sm" role="alert">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="row mb-4">
            <!-- Informasi Produk -->
            <div class="col-md-4">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white border-0 pt-4">
                        <h5 class="mb-0 fw-bold">Informasi Produk</h5>
                    </div>
                    <div class="card-body">
                        <div class="text-center mb-3">
                            @if($product->image)
                                <img src="{{ Storage::url($product->image) }}" alt="{{ $product->product_name }}" class="img-fluid rounded shadow-sm" style="max-height: 200px; object-fit: cover;">
                            @else
                                <div class="bg-light rounded d-flex align-items-center justify-content-center text-muted shadow-sm border mx-auto" style="width: 150px; height: 150px;">
                                    <i class="fas fa-image fa-3x"></i>
                                </div>
                            @endif
                        </div>
                        <table class="table table-sm table-borderless">
                            <tr>
                                <th width="40%" class="text-muted">SKU Internal</th>
                                <td><span class="badge bg-secondary">{{ $product->sku }}</span></td>
                            </tr>
                            <tr>
                                <th class="text-muted">Nama Produk</th>
                                <td><strong>{{ $product->product_name }}</strong></td>
                            </tr>
                            @if($product->variant_name)
                            <tr>
                                <th class="text-muted">Variasi</th>
                                <td>{{ $product->variant_name }}</td>
                            </tr>
                            @endif
                            <tr>
                                <th class="text-muted">Stok</th>
                                <td>
                                    <span class="badge {{ $product->stock > 0 ? 'bg-success' : 'bg-danger' }}">
                                        {{ $product->stock }} {{ $product->unit ?? 'pcs' }}
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <th class="text-muted">Harga Dasar</th>
                                <td>Rp {{ number_format($product->hpp, 0, ',', '.') }}</td>
                            </tr>
                        </table>
                    </div>
                    <div class="card-footer bg-white border-0 pb-4">
                        <a href="{{ route('product.index') }}" class="btn btn-outline-secondary w-100">
                            <i class="fas fa-arrow-left me-1"></i> Kembali ke Daftar
                        </a>
                    </div>
                </div>
            </div>

            <!-- Form Mapping Baru -->
            <div class="col-md-8">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white border-0 pt-4 pb-3">
                        <h5 class="mb-0 fw-bold">Tambah Mapping SKU</h5>
                        <small class="text-muted">Hubungkan produk ini dengan SKU di toko Shopee/Marketplace Anda.</small>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('product.mapping.store', $product->id) }}" method="POST">
                            @csrf
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">Akun Marketplace <span class="text-danger">*</span></label>
                                    <select name="marketplace_account_id" class="form-select bg-light" required>
                                        <option value="">-- Pilih Akun --</option>
                                        @foreach($accounts as $account)
                                            <option value="{{ $account->id }}" {{ old('marketplace_account_id') == $account->id ? 'selected' : '' }}>
                                                {{ $account->account_name }} ({{ strtoupper($account->marketplace->code) }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">SKU Marketplace (Seller SKU) <span class="text-danger">*</span></label>
                                    <input type="text" name="marketplace_sku" class="form-control bg-light" value="{{ old('marketplace_sku') }}" required placeholder="Contoh: BJU-MRH-S">
                                    <small class="text-muted">SKU di Shopee Seller Center.</small>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">ID Produk Shopee (Opsional)</label>
                                    <input type="text" name="marketplace_product_id" class="form-control bg-light" value="{{ old('marketplace_product_id') }}" placeholder="Contoh: 123456789">
                                    <small class="text-muted">Item ID dari Shopee.</small>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Nama Produk di Marketplace (Opsional)</label>
                                    <input type="text" name="marketplace_product_name" class="form-control bg-light" value="{{ old('marketplace_product_name') }}" placeholder="Nama produk di etalase toko">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Variasi di Marketplace (Opsional)</label>
                                    <input type="text" name="marketplace_variant" class="form-control bg-light" value="{{ old('marketplace_variant') }}" placeholder="Contoh: Merah, S">
                                </div>
                            </div>
                            
                            <div class="text-end mt-4">
                                <button type="submit" class="btn btn-primary px-4">
                                    <i class="fas fa-save me-1"></i> Simpan Mapping
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Daftar Mapping -->
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white border-0 pt-4 pb-3">
                <h5 class="mb-0 fw-bold">Daftar Mapping Tersimpan</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-striped mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Marketplace</th>
                                <th>Akun Toko</th>
                                <th>Info SKU & ID Shopee</th>
                                <th>Nama Produk (Etalase)</th>
                                <th>Status</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($product->productMarketplaces as $mapping)
                                <tr>
                                    <td>
                                        @if($mapping->marketplaceAccount->marketplace->code === 'shopee')
                                            <span class="badge bg-orange text-white" style="background-color: #ee4d2d;"><i class="fas fa-shopping-bag"></i> Shopee</span>
                                        @else
                                            <span class="badge bg-dark">{{ strtoupper($mapping->marketplaceAccount->marketplace->code) }}</span>
                                        @endif
                                    </td>
                                    <td>{{ $mapping->marketplaceAccount->account_name }}</td>
                                    <td>
                                        <strong>{{ $mapping->marketplace_sku }}</strong>
                                        @if($mapping->marketplace_product_id)
                                            <br><small class="text-muted">ID: {{ $mapping->marketplace_product_id }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        {{ $mapping->marketplace_product_name ?? '-' }}
                                        @if($mapping->marketplace_variant)
                                            <br><small class="text-muted">Variasi: {{ $mapping->marketplace_variant }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        @if($mapping->is_active)
                                            <span class="badge bg-success">Aktif</span>
                                        @else
                                            <span class="badge bg-secondary">Non-aktif</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <form action="{{ route('product.mapping.destroy', [$product->id, $mapping->id]) }}" method="POST" class="d-inline form-delete">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus Mapping">
                                                <i class="fas fa-trash"></i> Hapus
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">
                                        <i class="fas fa-link fs-2 mb-2"></i>
                                        <p class="mb-0">Belum ada pemetaan SKU untuk produk ini.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const deleteForms = document.querySelectorAll('.form-delete');
        deleteForms.forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                Swal.fire({
                    title: 'Hapus Mapping?',
                    text: 'Impor pesanan yang menggunakan SKU ini mungkin akan terganggu jika belum diperbarui.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: '<i class="fas fa-trash"></i> Ya, Hapus!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });
        });
    });
</script>
@endpush
