@extends('layouts.app')

@section('title', 'Kartu Stok - ' . $product->sku)
@section('page_title', 'Kartu Stok Produk')

@section('content')
<div class="row mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h1 class="h3 mb-0 text-gray-800 fw-bold">Buku Mutasi & Kartu Stok</h1>
            <p class="text-muted small mb-0">Histori pergerakan barang fisik secara kronologis dan terlacak</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('inventory.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Kembali ke Ringkasan
            </a>
            <a href="{{ route('inventory.adjustment', ['product_id' => $product->id]) }}" class="btn btn-warning text-dark">
                <i class="fas fa-balance-scale me-1"></i> Stock Opname
            </a>
        </div>
    </div>
</div>

<!-- Product Info Header Card -->
<div class="card shadow-sm border-0 rounded-3 mb-4 bg-white">
    <div class="card-body p-4">
        <div class="row align-items-center">
            <div class="col-md-7">
                <span class="badge bg-secondary mb-1">SKU: {{ $product->sku }}</span>
                <h4 class="fw-bold text-dark mb-1">{{ $product->product_name }}</h4>
                <p class="text-muted small mb-2">Kategori: {{ $product->category->name ?? '-' }} | Harga Satuan: Rp {{ number_format($product->selling_price, 0, ',', '.') }}</p>

                <!-- Warehouse distribution tags -->
                <div class="d-flex gap-2 flex-wrap align-items-center mt-2">
                    <span class="text-muted small fw-semibold">Sebaran Gudang:</span>
                    @forelse($warehouseStocks as $ws)
                        <span class="badge bg-light text-dark border p-2">
                            <i class="fas fa-warehouse text-primary me-1"></i> {{ $ws->warehouse->name }}: <strong>{{ number_format($ws->qty) }}</strong> pcs
                        </span>
                    @empty
                        <span class="text-muted small">Belum ada sebaran fisik</span>
                    @endforelse
                </div>
            </div>
            <div class="col-md-5 text-md-end mt-3 mt-md-0">
                <div class="d-inline-block text-center p-3 bg-light rounded-3 border">
                    <span class="text-muted small fw-bold text-uppercase d-block">Total Saldo Fisik</span>
                    <h2 class="fw-bold {{ $product->stock <= $product->minimum_stock ? 'text-danger' : 'text-success' }} mb-0">
                        {{ number_format($product->stock) }} <small class="fs-6 text-muted">pcs</small>
                    </h2>
                    <span class="text-muted small">Batas Min: {{ $product->minimum_stock }} pcs</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filter Bar -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-body p-3">
        <form method="GET" action="{{ route('inventory.stock-card', $product->id) }}" class="row g-2 align-items-center">
            <div class="col-md-4">
                <label class="form-label small text-muted mb-1">Pilih Gudang</label>
                <select name="warehouse_id" class="form-select form-select-sm">
                    <option value="">Semua Gudang</option>
                    @foreach($warehouses as $wh)
                        <option value="{{ $wh->id }}" {{ $warehouseId == $wh->id ? 'selected' : '' }}>
                            {{ $wh->name }} ({{ $wh->code }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small text-muted mb-1">Dari Tanggal</label>
                <input type="date" name="start_date" class="form-control form-control-sm" value="{{ $startDate }}">
            </div>
            <div class="col-md-3">
                <label class="form-label small text-muted mb-1">Sampai Tanggal</label>
                <input type="date" name="end_date" class="form-control form-control-sm" value="{{ $endDate }}">
            </div>
            <div class="col-md-2 d-flex align-items-end gap-1">
                <button type="submit" class="btn btn-sm btn-primary w-100">
                    <i class="fas fa-filter me-1"></i> Filter
                </button>
                @if($warehouseId || $startDate || $endDate)
                    <a href="{{ route('inventory.stock-card', $product->id) }}" class="btn btn-sm btn-outline-secondary" title="Reset Filter">
                        <i class="fas fa-undo"></i>
                    </a>
                @endif
            </div>
        </form>
    </div>
</div>

<!-- Stock Ledger Table -->
<div class="card shadow-sm border-0 rounded-3 mb-4">
    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
        <h6 class="m-0 font-weight-bold text-dark">
            <i class="fas fa-clipboard-list me-1"></i> Riwayat Transaksi Mutasi
        </h6>
        <span class="badge bg-light text-dark border">Total {{ $movements->total() }} Catatan</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Waktu Transaksi</th>
                    <th>No. Dokumen / Mutasi</th>
                    <th>Gudang</th>
                    <th>Tipe Mutasi</th>
                    <th class="text-end">Jumlah</th>
                    <th class="text-end">Stok Awal</th>
                    <th class="text-end">Stok Akhir</th>
                    <th>Referensi / Alasan</th>
                    <th>Petugas</th>
                </tr>
            </thead>
            <tbody>
                @forelse($movements as $m)
                    <tr>
                        <td class="small text-muted">
                            {{ $m->created_at->format('d/m/Y H:i') }}
                        </td>
                        <td>
                            <code class="fw-bold text-dark">{{ $m->movement_number }}</code>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border">
                                <i class="fas fa-warehouse me-1 text-secondary"></i> {{ $m->warehouse->name ?? '-' }}
                            </span>
                        </td>
                        <td>
                            @if($m->type === 'in')
                                <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1">
                                    <i class="fas fa-arrow-down me-1"></i> Masuk
                                </span>
                            @elseif($m->type === 'out')
                                <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 px-2 py-1">
                                    <i class="fas fa-arrow-up me-1"></i> Keluar
                                </span>
                            @elseif($m->type === 'adjustment')
                                <span class="badge bg-warning bg-opacity-10 text-warning text-dark border border-warning border-opacity-25 px-2 py-1">
                                    <i class="fas fa-balance-scale me-1"></i> Opname
                                </span>
                            @else
                                <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 px-2 py-1">
                                    <i class="fas fa-exchange-alt me-1"></i> Transfer
                                </span>
                            @endif
                        </td>
                        <td class="text-end fw-bold {{ $m->qty > 0 ? 'text-success' : 'text-danger' }}">
                            {{ $m->qty > 0 ? '+' . number_format($m->qty) : number_format($m->qty) }}
                        </td>
                        <td class="text-end text-muted">{{ number_format($m->stock_before) }}</td>
                        <td class="text-end fw-bold text-dark">{{ number_format($m->stock_after) }}</td>
                        <td class="small">
                            @if($m->reference_type === 'order')
                                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 me-1">Shopee Order</span>
                            @endif
                            <span class="text-dark">{{ $m->note ?? '-' }}</span>
                        </td>
                        <td class="small text-muted">
                            <i class="fas fa-user-circle me-1"></i> {{ $m->user->name ?? 'Sistem' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center py-5 text-muted">
                            <i class="fas fa-inbox fa-3x mb-3 text-secondary d-block"></i>
                            Belum ada riwayat mutasi stok untuk produk ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($movements->hasPages())
        <div class="card-footer bg-white py-3">
            {{ $movements->links() }}
        </div>
    @endif
</div>
@endsection
