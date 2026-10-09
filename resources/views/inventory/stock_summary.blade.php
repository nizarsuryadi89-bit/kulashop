@extends('layouts.app')

@section('title', 'Ringkasan Stok')
@section('page_title', 'Ringkasan Stok')

@section('content')
{{-- ===== PAGE HEADER ===== --}}
<div class="row mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h1 class="h3 mb-0 text-gray-800 fw-bold">Ringkasan Stok Inventory</h1>
            <p class="text-muted small mb-0">Pantau kondisi keseluruhan stok barang secara real-time.</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('inventory.stock-in') }}" class="btn btn-success btn-sm">
                <i class="fas fa-plus me-1"></i> Stok Masuk
            </a>
            <a href="{{ route('inventory.stock-out') }}" class="btn btn-danger btn-sm">
                <i class="fas fa-minus me-1"></i> Stok Keluar
            </a>
            <a href="{{ route('inventory.adjustment') }}" class="btn btn-warning btn-sm">
                <i class="fas fa-balance-scale me-1"></i> Opname
            </a>
            <a href="{{ route('inventory.stock-movements') }}" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-history me-1"></i> Mutasi
            </a>
        </div>
    </div>
</div>

{{-- ===== KPI CARDS ===== --}}
<div class="row g-3 mb-4">

    {{-- Total Produk --}}
    <div class="col-6 col-md-4 col-xl-2">
        <div class="card border-0 shadow-sm h-100" style="border-left: 4px solid #4e73df !important;">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <span class="text-muted small fw-semibold text-uppercase" style="font-size: 10px; letter-spacing: .05em;">Total Produk</span>
                    <div class="bg-primary bg-opacity-10 text-primary rounded p-1">
                        <i class="fas fa-box fa-sm"></i>
                    </div>
                </div>
                <div class="h3 fw-bold text-dark mb-0">{{ number_format($totalProducts) }}</div>
                <small class="text-muted">produk aktif</small>
            </div>
        </div>
    </div>

    {{-- Total SKU --}}
    <div class="col-6 col-md-4 col-xl-2">
        <div class="card border-0 shadow-sm h-100" style="border-left: 4px solid #36b9cc !important;">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <span class="text-muted small fw-semibold text-uppercase" style="font-size: 10px; letter-spacing: .05em;">Total SKU</span>
                    <div class="bg-info bg-opacity-10 text-info rounded p-1">
                        <i class="fas fa-barcode fa-sm"></i>
                    </div>
                </div>
                <div class="h3 fw-bold text-dark mb-0">{{ number_format($totalSku) }}</div>
                <small class="text-muted">kode unik</small>
            </div>
        </div>
    </div>

    {{-- Total Qty --}}
    <div class="col-6 col-md-4 col-xl-2">
        <div class="card border-0 shadow-sm h-100" style="border-left: 4px solid #1cc88a !important;">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <span class="text-muted small fw-semibold text-uppercase" style="font-size: 10px; letter-spacing: .05em;">Total Qty</span>
                    <div class="bg-success bg-opacity-10 text-success rounded p-1">
                        <i class="fas fa-cubes fa-sm"></i>
                    </div>
                </div>
                <div class="h3 fw-bold text-dark mb-0">{{ number_format($totalQty) }}</div>
                <small class="text-muted">unit tersedia</small>
            </div>
        </div>
    </div>

    {{-- Low Stock --}}
    <div class="col-6 col-md-4 col-xl-2">
        <div class="card border-0 shadow-sm h-100" style="border-left: 4px solid #f6c23e !important;">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <span class="text-muted small fw-semibold text-uppercase" style="font-size: 10px; letter-spacing: .05em;">Low Stock</span>
                    <div class="bg-warning bg-opacity-10 text-warning rounded p-1">
                        <i class="fas fa-exclamation-triangle fa-sm"></i>
                    </div>
                </div>
                <div class="h3 fw-bold {{ $lowStockCount > 0 ? 'text-warning' : 'text-dark' }} mb-0">
                    {{ number_format($lowStockCount) }}
                </div>
                <small class="text-muted">di bawah minimum</small>
            </div>
        </div>
    </div>

    {{-- Out of Stock --}}
    <div class="col-6 col-md-4 col-xl-2">
        <div class="card border-0 shadow-sm h-100" style="border-left: 4px solid #e74a3b !important;">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <span class="text-muted small fw-semibold text-uppercase" style="font-size: 10px; letter-spacing: .05em;">Out of Stock</span>
                    <div class="bg-danger bg-opacity-10 text-danger rounded p-1">
                        <i class="fas fa-times-circle fa-sm"></i>
                    </div>
                </div>
                <div class="h3 fw-bold {{ $outOfStockCount > 0 ? 'text-danger' : 'text-dark' }} mb-0">
                    {{ number_format($outOfStockCount) }}
                </div>
                <small class="text-muted">stok habis</small>
            </div>
        </div>
    </div>

    {{-- Gudang Aktif --}}
    <div class="col-6 col-md-4 col-xl-2">
        <div class="card border-0 shadow-sm h-100" style="border-left: 4px solid #858796 !important;">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <span class="text-muted small fw-semibold text-uppercase" style="font-size: 10px; letter-spacing: .05em;">Gudang Aktif</span>
                    <div class="bg-secondary bg-opacity-10 text-secondary rounded p-1">
                        <i class="fas fa-warehouse fa-sm"></i>
                    </div>
                </div>
                <div class="h3 fw-bold text-dark mb-0">{{ number_format($totalWarehouses) }}</div>
                <small class="text-muted">{{ $movementsToday }} mutasi hari ini</small>
            </div>
        </div>
    </div>

</div>

{{-- ===== ROW 2: WAREHOUSE + ALERT TABLE ===== --}}
<div class="row g-3 mb-4">

    {{-- Distribusi per Gudang --}}
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="m-0 fw-bold text-dark">
                    <i class="fas fa-warehouse me-1 text-primary"></i> Distribusi per Gudang
                </h6>
            </div>
            <div class="card-body p-0">
                @forelse($warehouseStocks as $ws)
                    <div class="d-flex justify-content-between align-items-center px-4 py-3 border-bottom">
                        <div>
                            <div class="fw-semibold text-dark">
                                {{ $ws['name'] }}
                                @if($ws['is_default'])
                                    <span class="badge bg-warning text-dark ms-1" style="font-size: 9px;">UTAMA</span>
                                @endif
                            </div>
                            <small class="text-muted">{{ $ws['code'] }} &bull; {{ $ws['total_sku'] }} SKU</small>
                        </div>
                        <div class="text-end">
                            <span class="fs-5 fw-bold text-primary">{{ number_format($ws['total_qty']) }}</span>
                            <div class="text-muted" style="font-size:10px;">unit</div>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-4 text-muted small">Belum ada gudang aktif.</div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Alert: Low Stock & Out of Stock --}}
    <div class="col-md-8">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 fw-bold text-dark">
                    <i class="fas fa-exclamation-circle me-1 text-danger"></i> Produk Perlu Perhatian
                </h6>
                <a href="{{ route('inventory.low-stock') }}" class="btn btn-outline-danger btn-sm">
                    Lihat Semua
                </a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-secondary" style="font-size: 12px;">
                        <tr>
                            <th class="ps-4">Produk / SKU</th>
                            <th class="text-end">Stok</th>
                            <th class="text-end">Min. Stok</th>
                            <th class="text-center">Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($alertProducts as $p)
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-semibold text-dark">{{ $p->product_name }}</div>
                                    <small class="text-muted">{{ $p->sku }}</small>
                                </td>
                                <td class="text-end fw-bold {{ $p->stock <= 0 ? 'text-danger' : 'text-warning' }}">
                                    {{ number_format($p->stock) }}
                                </td>
                                <td class="text-end text-muted">
                                    {{ number_format($p->minimum_stock) }}
                                </td>
                                <td class="text-center">
                                    @if($p->stock <= 0)
                                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 px-2 py-1">
                                            Out of Stock
                                        </span>
                                    @else
                                        <span class="badge bg-warning bg-opacity-10 text-warning text-dark border border-warning border-opacity-25 px-2 py-1">
                                            Low Stock
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('inventory.stock-in') }}?product_id={{ $p->id }}" class="btn btn-outline-success btn-sm" title="Tambah Stok">
                                        <i class="fas fa-plus"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">
                                    <i class="fas fa-check-circle text-success me-1"></i>
                                    Semua produk dalam kondisi stok aman.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- ===== RECENT MOVEMENTS ===== --}}
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
        <h6 class="m-0 fw-bold text-dark">
            <i class="fas fa-clock me-1 text-secondary"></i> Mutasi Stok Terkini
        </h6>
        <a href="{{ route('inventory.stock-movements') }}" class="btn btn-outline-secondary btn-sm">
            Lihat Semua
        </a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light text-secondary" style="font-size: 12px;">
                <tr>
                    <th class="ps-4">Waktu</th>
                    <th>Produk</th>
                    <th>Gudang</th>
                    <th class="text-center">Type</th>
                    <th class="text-end">Qty</th>
                    <th class="text-end">Before</th>
                    <th class="text-end">After</th>
                    <th>User</th>
                </tr>
            </thead>
            <tbody>
                @forelse($recentMovements as $m)
                    <tr>
                        <td class="ps-4 text-muted small">{{ $m->created_at->format('d/m H:i') }}</td>
                        <td>
                            <div class="fw-semibold text-dark" style="font-size: 13px;">{{ $m->product->product_name }}</div>
                            <small class="text-muted">{{ $m->product->sku }}</small>
                        </td>
                        <td class="small text-muted">{{ $m->warehouse->name ?? '-' }}</td>
                        <td class="text-center">
                            @if($m->type === 'in')
                                <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">IN</span>
                            @elseif($m->type === 'out')
                                <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25">OUT</span>
                            @elseif($m->type === 'adjustment')
                                <span class="badge bg-warning bg-opacity-10 text-warning text-dark border border-warning border-opacity-25">ADJ</span>
                            @else
                                <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25">{{ strtoupper($m->type) }}</span>
                            @endif
                        </td>
                        <td class="text-end fw-bold {{ $m->qty >= 0 ? 'text-success' : 'text-danger' }}">
                            {{ $m->type === 'in' ? '+' : '-' }}{{ number_format($m->qty) }}
                        </td>
                        <td class="text-end text-muted small">{{ number_format($m->stock_before) }}</td>
                        <td class="text-end fw-bold text-dark">{{ number_format($m->stock_after) }}</td>
                        <td class="small text-muted">{{ $m->user->name ?? 'Sistem' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="fas fa-inbox fa-2x d-block mb-2 text-secondary"></i>
                            Belum ada riwayat mutasi stok.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
