@extends('layouts.app')

@section('title', 'Mutasi Stok Global')
@section('page_title', 'Mutasi Stok Global')

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
        </div>
    </div>
</div>
<div class="card shadow-sm border-0 rounded-3 mb-4">
    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center flex-wrap">
        <h6 class="m-0 font-weight-bold text-dark">
            <i class="fas fa-filter me-1 text-primary"></i> Filter Pencarian
        </h6>
    </div>
    <div class="card-body p-4 bg-light">
        <form action="{{ route('inventory.stock-movements') }}" method="GET" class="row g-3">
            <div class="col-md-3">
                <label class="form-label small fw-bold">Gudang</label>
                <select name="warehouse_id" class="form-select form-select-sm">
                    <option value="">Semua Gudang</option>
                    @foreach($warehouses as $wh)
                        <option value="{{ $wh->id }}" {{ $warehouseId == $wh->id ? 'selected' : '' }}>
                            {{ $wh->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-bold">Produk</label>
                <select name="product_id" class="form-select form-select-sm">
                    <option value="">Semua Produk</option>
                    @foreach($products as $p)
                        <option value="{{ $p->id }}" {{ $productId == $p->id ? 'selected' : '' }}>
                            [{{ $p->sku }}] {{ $p->product_name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-bold">Mulai Tanggal</label>
                <input type="date" name="start_date" class="form-control form-control-sm" value="{{ $startDate }}">
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-bold">Sampai Tanggal</label>
                <input type="date" name="end_date" class="form-control form-control-sm" value="{{ $endDate }}">
            </div>
            <div class="col-md-2 d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-primary btn-sm w-100">
                    <i class="fas fa-search"></i> Cari
                </button>
                <a href="{{ route('inventory.stock-movements') }}" class="btn btn-outline-secondary btn-sm w-100">
                    Reset
                </a>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm border-0 rounded-3">
    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
        <h6 class="m-0 font-weight-bold text-dark">
            <i class="fas fa-history me-1 text-primary"></i> Tabel Riwayat Mutasi
        </h6>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light text-secondary">
                <tr>
                    <th class="ps-4">Tanggal</th>
                    <th>Produk</th>
                    <th>Gudang</th>
                    <th class="text-center">Type</th>
                    <th class="text-end">Qty</th>
                    <th class="text-end">Before</th>
                    <th class="text-end">After</th>
                    <th>Reference</th>
                    <th>User</th>
                </tr>
            </thead>
            <tbody>
                @forelse($movements as $m)
                    <tr>
                        <td class="ps-4 text-muted small">
                            {{ $m->created_at->format('d-m-Y H:i') }}
                        </td>
                        <td class="fw-bold text-dark">
                            {{ $m->product->product_name }}<br>
                            <small class="text-muted">{{ $m->product->sku }}</small>
                        </td>
                        <td class="small">
                            {{ $m->warehouse->name ?? '-' }}
                        </td>
                        <td class="text-center">
                            @if($m->type === 'in')
                                <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1">IN</span>
                            @elseif($m->type === 'out')
                                <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 px-2 py-1">OUT</span>
                            @elseif($m->type === 'adjustment')
                                <span class="badge bg-warning bg-opacity-10 text-warning text-dark border border-warning border-opacity-25 px-2 py-1">ADJUST</span>
                            @else
                                <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 px-2 py-1">{{ strtoupper($m->type) }}</span>
                            @endif
                        </td>
                        <td class="text-end fw-bold {{ $m->qty > 0 ? 'text-success' : 'text-danger' }}">
                            {{ $m->qty > 0 ? '+' . number_format($m->qty) : number_format($m->qty) }}
                        </td>
                        <td class="text-end text-muted">{{ number_format($m->stock_before) }}</td>
                        <td class="text-end fw-bold text-dark">{{ number_format($m->stock_after) }}</td>
                        <td class="small">
                            <span class="d-block text-dark">{{ ucfirst($m->reference_type) }}</span>
                            <span class="text-muted">{{ $m->note ?? '-' }}</span>
                        </td>
                        <td class="small text-muted">
                            {{ $m->user->name ?? 'Sistem' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center py-5 text-muted">
                            <i class="fas fa-inbox fa-3x mb-3 text-secondary d-block"></i>
                            Belum ada riwayat mutasi stok.
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
