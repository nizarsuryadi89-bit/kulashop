@extends('layouts.app')

@section('title', 'Tabel Stok Inventory')
@section('page_title', 'Tabel Stok')

@section('content')
{{-- ===== PAGE HEADER ===== --}}
<div class="row mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h1 class="h3 mb-0 text-gray-800 fw-bold">Tabel Stok Produk</h1>
            <p class="text-muted small mb-0">Stok per produk per gudang. Satu baris = satu produk × satu gudang.</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('inventory.stock-in') }}" class="btn btn-success btn-sm">
                <i class="fas fa-arrow-circle-down me-1"></i> Stok Masuk
            </a>
            <a href="{{ route('inventory.stock-out') }}" class="btn btn-danger btn-sm">
                <i class="fas fa-arrow-circle-up me-1"></i> Stok Keluar
            </a>
            <a href="{{ route('inventory.adjustment') }}" class="btn btn-warning text-dark btn-sm">
                <i class="fas fa-balance-scale me-1"></i> Opname
            </a>
        </div>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm" role="alert">
        <i class="fas fa-check-circle me-1"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm" role="alert">
        <i class="fas fa-exclamation-triangle me-1"></i> {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

{{-- ===== KPI CARDS ===== --}}
<div class="row g-3 mb-4">
    <div class="col-md-3 col-sm-6">
        <div class="card border-0 shadow-sm rounded-3 p-3" style="border-left: 4px solid #4e73df !important;">
            <span class="text-muted small fw-bold text-uppercase">Total Produk Aktif</span>
            <h3 class="fw-bold mb-0 text-dark mt-1">{{ number_format($totalProductsCount) }}</h3>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card border-0 shadow-sm rounded-3 p-3" style="border-left: 4px solid #1cc88a !important;">
            <span class="text-muted small fw-bold text-uppercase">Total Stok Fisik</span>
            <h3 class="fw-bold mb-0 text-success mt-1">{{ number_format($totalStockUnits) }}</h3>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card border-0 shadow-sm rounded-3 p-3" style="border-left: 4px solid #f6c23e !important;">
            <span class="text-muted small fw-bold text-uppercase">Low Stock</span>
            <h3 class="fw-bold mb-0 {{ $lowStockCount > 0 ? 'text-warning' : 'text-dark' }} mt-1">{{ number_format($lowStockCount) }}</h3>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card border-0 shadow-sm rounded-3 p-3" style="border-left: 4px solid #e74a3b !important;">
            <span class="text-muted small fw-bold text-uppercase">Out of Stock</span>
            <h3 class="fw-bold mb-0 {{ $outOfStockCount > 0 ? 'text-danger' : 'text-dark' }} mt-1">{{ number_format($outOfStockCount) }}</h3>
        </div>
    </div>
</div>

{{-- ===== FILTERS ===== --}}
<div class="card shadow-sm border-0 mb-4">
    <div class="card-body p-3">
        <form method="GET" action="{{ route('inventory.index') }}" class="row g-2 align-items-center">
            <div class="col-md-4">
                <div class="input-group">
                    <span class="input-group-text bg-light border-0"><i class="fas fa-search text-muted"></i></span>
                    <input type="text" name="search" class="form-control bg-light border-0"
                        placeholder="Cari SKU atau Nama Produk..." value="{{ $search }}">
                </div>
            </div>
            <div class="col-md-3">
                <select name="warehouse_id" class="form-select bg-light border-0">
                    <option value="">Semua Gudang</option>
                    @foreach($warehouses as $wh)
                        <option value="{{ $wh->id }}" {{ $warehouseId == $wh->id ? 'selected' : '' }}>
                            {{ $wh->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="stock_filter" class="form-select bg-light border-0">
                    <option value="">Semua Status</option>
                    <option value="normal"   {{ $stockFilter === 'normal'   ? 'selected' : '' }}>Normal</option>
                    <option value="low"      {{ $stockFilter === 'low'      ? 'selected' : '' }}>Low Stock</option>
                    <option value="out"      {{ $stockFilter === 'out'      ? 'selected' : '' }}>Out of Stock</option>
                    <option value="in_stock" {{ $stockFilter === 'in_stock' ? 'selected' : '' }}>Tersedia</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100"><i class="fas fa-filter me-1"></i> Filter</button>
            </div>
            <div class="col-md-1">
                <a href="{{ route('inventory.index') }}" class="btn btn-outline-secondary w-100" title="Reset">
                    <i class="fas fa-undo"></i>
                </a>
            </div>
        </form>
    </div>
</div>

{{-- ===== STOCK TABLE ===== --}}
<div class="card shadow-sm border-0">
    <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
        <h6 class="m-0 fw-bold text-dark">
            <i class="fas fa-table me-1 text-primary"></i> Tabel Stok
        </h6>
        <small class="text-muted">Total: {{ $stocks->total() }} baris</small>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light text-secondary">
                <tr>
                    <th class="ps-4" width="5%">No</th>
                    <th>SKU</th>
                    <th>Produk</th>
                    <th>Gudang</th>
                    <th class="text-end">Stok</th>
                    <th class="text-end">Minimum</th>
                    <th class="text-center">Status</th>
                    <th class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($stocks as $index => $ws)
                    @php
                        $qty     = (float) $ws->qty;
                        $minStok = $ws->product->minimum_stock !== null ? (float) $ws->product->minimum_stock : null;

                        if ($qty <= 0) {
                            $statusLabel = 'Out of Stock';
                            $statusClass = 'danger';
                            $qtyClass    = 'text-danger';
                        } elseif ($minStok !== null && $qty <= $minStok) {
                            $statusLabel = 'Low Stock';
                            $statusClass = 'warning';
                            $qtyClass    = 'text-warning';
                        } else {
                            $statusLabel = 'Normal';
                            $statusClass = 'success';
                            $qtyClass    = 'text-success';
                        }
                    @endphp
                    <tr>
                        <td class="ps-4 text-muted">{{ $stocks->firstItem() + $index }}</td>
                        <td>
                            <code class="text-secondary fw-bold">{{ $ws->product->sku ?? '-' }}</code>
                        </td>
                        <td>
                            <div class="fw-semibold text-dark">{{ $ws->product->product_name ?? '-' }}</div>
                            @if($ws->product->variant_name)
                                <small class="text-muted">{{ $ws->product->variant_name }}</small>
                            @endif
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border">
                                <i class="fas fa-warehouse me-1 text-secondary" style="font-size:10px;"></i>
                                {{ $ws->warehouse->name ?? '-' }}
                            </span>
                            @if($ws->warehouse && $ws->warehouse->is_default)
                                <span class="badge bg-warning text-dark ms-1" style="font-size:9px;">UTAMA</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <span class="fs-5 fw-bold {{ $qtyClass }}">{{ number_format($qty) }}</span>
                        </td>
                        <td class="text-end text-muted">
                            @if($minStok !== null)
                                {{ number_format($minStok) }}
                            @else
                                <span class="text-muted small">—</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @if($statusClass === 'danger')
                                <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 px-2 py-1">
                                    <i class="fas fa-times-circle me-1"></i>{{ $statusLabel }}
                                </span>
                            @elseif($statusClass === 'warning')
                                <span class="badge bg-warning bg-opacity-10 text-warning text-dark border border-warning border-opacity-25 px-2 py-1">
                                    <i class="fas fa-exclamation-triangle me-1"></i>{{ $statusLabel }}
                                </span>
                            @else
                                <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1">
                                    <i class="fas fa-check-circle me-1"></i>{{ $statusLabel }}
                                </span>
                            @endif
                        </td>
                        <td class="text-center">
                            <div class="d-inline-flex gap-1">
                                <a href="{{ route('inventory.stock-card', $ws->product_id) }}"
                                   class="btn btn-sm btn-outline-info px-2 py-1" title="Kartu Stok">
                                    <i class="fas fa-file-alt"></i>
                                </a>
                                <a href="{{ route('inventory.adjustment', ['product_id' => $ws->product_id, 'warehouse_id' => $ws->warehouse_id]) }}"
                                   class="btn btn-sm btn-outline-warning text-dark px-2 py-1" title="Stock Opname">
                                    <i class="fas fa-balance-scale"></i>
                                </a>
                                <a href="{{ route('inventory.stock-in', ['product_id' => $ws->product_id, 'warehouse_id' => $ws->warehouse_id]) }}"
                                   class="btn btn-sm btn-outline-success px-2 py-1" title="Tambah Stok">
                                    <i class="fas fa-plus"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="fas fa-box-open fa-3x mb-3 text-secondary d-block"></i>
                            Tidak ada data stok yang sesuai filter.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($stocks->hasPages())
        <div class="card-footer bg-white border-0 py-3">
            {{ $stocks->links() }}
        </div>
    @endif
</div>
@endsection
