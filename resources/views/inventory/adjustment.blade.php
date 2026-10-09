@extends('layouts.app')

@section('title', 'Penyesuaian Stok / Stock Opname')
@section('page_title', 'Stock Opname')

@section('content')
<div class="row mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h1 class="h3 mb-0 text-gray-800 fw-bold">Stock Opname & Penyesuaian</h1>
            <p class="text-muted small mb-0">Sesuaikan selisih antara pencatatan sistem dengan hasil perhitungan fisik di gudang</p>
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
                <h6 class="m-0 font-weight-bold text-warning text-dark">
                    <i class="fas fa-balance-scale me-1"></i> Formulir Penyesuaian Stok Fisik (Opname)
                </h6>
            </div>
            <div class="card-body p-4">
                <form action="{{ route('inventory.adjustment.store') }}" method="POST">
                    @csrf

                    <div class="mb-3">
                        <label for="product_id" class="form-label fw-bold text-dark">Pilih Produk <span class="text-danger">*</span></label>
                        <select name="product_id" id="product_id" class="form-select @error('product_id') is-invalid @enderror" required>
                            <option value="">-- Pilih Produk Internal --</option>
                            @foreach($products as $product)
                                <option value="{{ $product->id }}" {{ (old('product_id', $selectedProductId) == $product->id) ? 'selected' : '' }} data-stock="{{ $product->stock }}">
                                    [{{ $product->sku }}] {{ $product->product_name }}
                                </option>
                            @endforeach
                        </select>
                        @error('product_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="warehouse_id" class="form-label fw-bold text-dark">Pilih Gudang <span class="text-danger">*</span></label>
                        <select name="warehouse_id" id="warehouse_id" class="form-select @error('warehouse_id') is-invalid @enderror" required>
                            <option value="">-- Pilih Gudang --</option>
                            @foreach($warehouses as $wh)
                                <option value="{{ $wh->id }}" {{ (old('warehouse_id', $selectedWarehouseId) == $wh->id || (empty(old('warehouse_id', $selectedWarehouseId)) && $wh->is_default)) ? 'selected' : '' }}>
                                    {{ $wh->name }} ({{ $wh->code }}) {{ $wh->is_default ? '★ [Utama]' : '' }}
                                </option>
                            @endforeach
                        </select>
                        @error('warehouse_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Comparison Box -->
                    <div class="card bg-light border-0 rounded-3 p-3 mb-3">
                        <div class="row align-items-center text-center">
                            <div class="col-sm-4 border-end">
                                <span class="text-muted small fw-semibold">Stok Saat Ini di Sistem</span>
                                <h4 class="fw-bold text-secondary mb-0 mt-1" id="displaySystemStock">{{ $currentStock ?? 0 }} pcs</h4>
                            </div>
                            <div class="col-sm-4 border-end">
                                <span class="text-muted small fw-semibold">Stok Fisik Sebenarnya</span>
                                <h4 class="fw-bold text-primary mb-0 mt-1" id="displayActualStock">--</h4>
                            </div>
                            <div class="col-sm-4">
                                <span class="text-muted small fw-semibold">Selisih Penyesuaian</span>
                                <h4 class="fw-bold text-muted mb-0 mt-1" id="displayDifference">--</h4>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="actual_stock" class="form-label fw-bold text-dark">Stok Fisik Sebenarnya <span class="text-danger">*</span></label>
                        <input type="number" name="actual_stock" id="actual_stock" class="form-control form-control-lg @error('actual_stock') is-invalid @enderror" value="{{ old('actual_stock', $currentStock) }}" min="0" step="0.001" required placeholder="Masukkan jumlah fisik saat ini">
                        @error('actual_stock')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">Masukkan angka mutlak barang yang ada di rak/gudang saat ini (bukan selisihnya).</small>
                    </div>

                    <div class="mb-4">
                        <label for="notes" class="form-label fw-bold text-dark">Alasan Penyesuaian / Keterangan Opname <span class="text-danger">*</span></label>
                        <input type="text" name="notes" id="notes" class="form-control @error('notes') is-invalid @enderror" value="{{ old('notes') }}" placeholder="Contoh: Stock opname 19 September 2026, selisih barcode hilang, dll" required>
                        @error('notes')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-warning text-dark px-4 py-2 fw-semibold">
                            <i class="fas fa-check-circle me-1"></i> Simpan Penyesuaian Opname
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
                <h6 class="fw-bold text-dark mb-3"><i class="fas fa-info-circle text-warning text-dark me-1"></i> Aturan Stock Opname</h6>
                <ul class="text-muted small ps-3 mb-0" style="line-height: 1.7;">
                    <li>Jika <strong>Stok Fisik > Sistem</strong>: Sistem otomatis mencatat mutasi <strong>Penyesuaian Masuk</strong> (+).</li>
                    <li>Jika <strong>Stok Fisik < Sistem</strong>: Sistem otomatis mencatat mutasi <strong>Penyesuaian Keluar</strong> (-).</li>
                    <li>Nomor mutasi resmi <code>ADJ-YYYYMMDD-XXXX</code> dibuat otomatis pada kartu stok.</li>
                    <li>Saldo agregat di Master Produk akan langsung sinkron dengan total fisik terbaru.</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    $('select[name="product_id"]').select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: '-- Pilih Produk Internal --'
    });
    
    const productSelect = document.getElementById('product_id');
    const warehouseSelect = document.getElementById('warehouse_id');
    const actualInput = document.getElementById('actual_stock');
    const displaySystemStock = document.getElementById('displaySystemStock');
    const displayActualStock = document.getElementById('displayActualStock');
    const displayDifference = document.getElementById('displayDifference');

    let currentSystemStock = {{ $currentStock ?? 0 }};

    function updateStockLookup() {
        const prodId = productSelect.value;
        const whId = warehouseSelect.value;

        if (prodId && whId) {
            // Optional fetch or if user changed selection via reload
            if (prodId != '{{ $selectedProductId }}' || whId != '{{ $selectedWarehouseId }}') {
                const url = new URL(window.location.href);
                url.searchParams.set('product_id', prodId);
                url.searchParams.set('warehouse_id', whId);
                window.location.href = url.toString();
                return;
            }
        }
        calculateDiff();
    }

    function calculateDiff() {
        const actual = parseInt(actualInput.value);
        if (!isNaN(actual)) {
            displayActualStock.innerText = actual + ' pcs';
            const diff = actual - currentSystemStock;
            if (diff > 0) {
                displayDifference.className = 'fw-bold text-success mb-0 mt-1';
                displayDifference.innerText = '+' + diff + ' pcs (Lebih)';
            } else if (diff < 0) {
                displayDifference.className = 'fw-bold text-danger mb-0 mt-1';
                displayDifference.innerText = diff + ' pcs (Kurang)';
            } else {
                displayDifference.className = 'fw-bold text-secondary mb-0 mt-1';
                displayDifference.innerText = '0 pcs (Sesuai)';
            }
        } else {
            displayActualStock.innerText = '--';
            displayDifference.innerText = '--';
        }
    }

    productSelect.addEventListener('change', updateStockLookup);
    warehouseSelect.addEventListener('change', updateStockLookup);
    actualInput.addEventListener('input', calculateDiff);

    calculateDiff();
});
</script>
@endpush
