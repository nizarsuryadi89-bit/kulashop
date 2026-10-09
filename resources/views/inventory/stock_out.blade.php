@extends('layouts.app')

@section('title', 'Pencatatan Stok Keluar (Stock Out)')
@section('page_title', 'Stok Keluar')

@section('content')
<div class="row mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h1 class="h3 mb-0 text-gray-800 fw-bold">Pengeluaran Stok Manual</h1>
            <p class="text-muted small mb-0">Catat pengurangan barang fisik dari gudang (sampel promosi, barang rusak/hilang, penggunaan internal)</p>
        </div>
        <div>
            <a href="{{ route('inventory.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Kembali ke Ringkasan
            </a>
        </div>
    </div>
</div>

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm" role="alert">
        <i class="fas fa-exclamation-triangle me-1"></i> {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<div class="row">
    <div class="col-lg-8">
        <div class="card shadow-sm border-0 rounded-3 mb-4">
            <div class="card-header bg-white py-3 border-bottom">
                <h6 class="m-0 font-weight-bold text-danger">
                    <i class="fas fa-arrow-circle-up me-1"></i> Formulir Pengeluaran Barang Manual
                </h6>
            </div>
            <div class="card-body p-4">
                <form action="{{ route('inventory.stock-out.store') }}" method="POST">
                    @csrf

                    <div class="mb-3">
                        <label for="product_id" class="form-label fw-bold text-dark">Pilih Produk <span class="text-danger">*</span></label>
                        <select name="product_id" id="product_id" class="form-select @error('product_id') is-invalid @enderror" required>
                            <option value="">-- Pilih Produk Internal --</option>
                            @foreach($products as $product)
                                <option value="{{ $product->id }}" {{ old('product_id') == $product->id ? 'selected' : '' }} data-stock="{{ $product->stock }}">
                                    [{{ $product->sku }}] {{ $product->product_name }} (Total Stok Tersedia: {{ $product->stock }})
                                </option>
                            @endforeach
                        </select>
                        @error('product_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="warehouse_id" class="form-label fw-bold text-dark">Gudang Asal <span class="text-danger">*</span></label>
                            <select name="warehouse_id" id="warehouse_id" class="form-select @error('warehouse_id') is-invalid @enderror" required>
                                <option value="">-- Pilih Gudang --</option>
                                @foreach($warehouses as $wh)
                                    <option value="{{ $wh->id }}" {{ (old('warehouse_id') == $wh->id || (empty(old('warehouse_id')) && $wh->is_default)) ? 'selected' : '' }}>
                                        {{ $wh->name }} ({{ $wh->code }}) {{ $wh->is_default ? '★ [Utama]' : '' }}
                                    </option>
                                @endforeach
                            </select>
                            @error('warehouse_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="quantity" class="form-label fw-bold text-dark">Jumlah Keluar <span class="text-danger">*</span></label>
                            <input type="number" name="quantity" id="quantity" class="form-control @error('quantity') is-invalid @enderror" value="{{ old('quantity') }}" placeholder="0" min="0.01" step="0.001" required>
                            @error('quantity')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="notes" class="form-label fw-bold text-dark">Alasan Pengeluaran / Referensi <span class="text-danger">*</span></label>
                        <input type="text" name="notes" id="notes" class="form-control @error('notes') is-invalid @enderror" value="{{ old('notes') }}" placeholder="Contoh: Sampel endorse influencer, barang rusak saat loading, retur reject" required>
                        @error('notes')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">Wajib diisi agar histori audit di kartu stok dapat dipertanggungjawabkan.</small>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-danger px-4 py-2 fw-semibold">
                            <i class="fas fa-save me-1"></i> Simpan Stok Keluar
                        </button>
                        <a href="{{ route('inventory.index') }}" class="btn btn-light px-4 py-2 border">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card shadow-sm border-0 rounded-3 mb-4 bg-light">
            <div class="card-body p-4">
                <h6 class="fw-bold text-dark mb-3"><i class="fas fa-shield-alt text-danger me-1"></i> Validasi Stok Keluar</h6>
                <ul class="text-muted small ps-3 mb-0" style="line-height: 1.7;">
                    <li>Sistem memiliki <strong>proteksi saldo negatif</strong>: pengeluaran manual akan ditolak jika jumlah melebihi stok yang ada di gudang terpilih.</li>
                    <li>Untuk penjualan pesanan marketplace Shopee, pemotongan stok dilakukan secara <strong>otomatis & terintegrasi</strong> saat impor pesanan.</li>
                    <li>Nomor mutasi resmi <code>OUT-YYYYMMDD-XXXX</code> akan otomatis dicatat di kartu stok.</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        $('select[name="product_id"]').select2({
            theme: 'bootstrap-5',
            width: '100%',
            placeholder: '-- Pilih Produk Internal --'
        });
    });
</script>
@endpush
