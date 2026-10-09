@extends('layouts.app')
@section('title', 'Inventory Valuation')

@section('content')
<div class="container-fluid">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <h4 class="mb-0 fw-bold text-dark" style="letter-spacing: -0.5px;">Inventory Valuation <i class="fas fa-boxes text-primary ms-2"></i></h4>
        <div>
            <a href="{{ route('inventory.costing.opening_stock') }}" class="btn btn-outline-primary btn-sm fw-bold">
                <i class="fas fa-plus me-1"></i> Opening Stock Cost
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm" style="border-radius: 12px; background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                <div class="card-body text-white">
                    <h6 class="text-uppercase fw-bold mb-1 opacity-75">Total Inventory Value</h6>
                    <h3 class="mb-0 fw-bold">Rp {{ number_format($totalValue, 0, ',', '.') }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm" style="border-radius: 12px;">
                <div class="card-body">
                    <h6 class="text-uppercase fw-bold text-muted mb-1">Total Quantity</h6>
                    <h3 class="mb-0 fw-bold text-dark">{{ number_format($totalQty, 0, ',', '.') }} <small class="fs-6 text-muted">unit</small></h3>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm" style="border-radius: 12px;">
        <div class="card-body p-4">
            <form method="GET" action="{{ route('inventory.valuation') }}" class="mb-4">
                <div class="row g-2 align-items-center">
                    <div class="col-md-3">
                        <select name="warehouse_id" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">Semua Gudang</option>
                            @foreach($warehouses as $wh)
                                <option value="{{ $wh->id }}" {{ $warehouseId == $wh->id ? 'selected' : '' }}>{{ $wh->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light border-end-0"><i class="fas fa-search text-muted"></i></span>
                            <input type="text" class="form-control border-start-0" name="search" placeholder="Cari nama produk / SKU..." value="{{ $search }}">
                            <button class="btn btn-primary px-3" type="submit">Cari</button>
                        </div>
                    </div>
                    @if($search || $warehouseId)
                        <div class="col-md-auto">
                            <a href="{{ route('inventory.valuation') }}" class="btn btn-light btn-sm"><i class="fas fa-times me-1"></i> Reset</a>
                        </div>
                    @endif
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-secondary">
                        <tr>
                            <th class="fw-bold text-uppercase" style="font-size: 0.8rem;">Produk</th>
                            <th class="fw-bold text-uppercase" style="font-size: 0.8rem;">SKU</th>
                            <th class="fw-bold text-uppercase" style="font-size: 0.8rem;">Gudang</th>
                            <th class="fw-bold text-uppercase text-end" style="font-size: 0.8rem;">Qty Costing</th>
                            <th class="fw-bold text-uppercase text-end" style="font-size: 0.8rem;">Average Cost</th>
                            <th class="fw-bold text-uppercase text-end" style="font-size: 0.8rem;">Inventory Value</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($valuations as $val)
                        <tr>
                            <td class="fw-bold text-dark">
                                <div class="d-flex align-items-center">
                                    <div class="bg-light rounded p-2 me-3 text-center" style="width: 40px; height: 40px;">
                                        <i class="fas fa-box text-primary"></i>
                                    </div>
                                    <div>
                                        {{ $val->product->product_name }}
                                        @if($val->product->category)
                                            <div class="small text-muted"><i class="fas fa-tag me-1"></i>{{ $val->product->category->name }}</div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td><span class="badge bg-secondary bg-opacity-10 text-secondary border">{{ $val->product->sku }}</span></td>
                            <td class="text-muted"><i class="fas fa-warehouse me-1"></i> {{ $val->warehouse->name }}</td>
                            <td class="text-end fw-bold">{{ number_format($val->quantity, 0, ',', '.') }}</td>
                            <td class="text-end text-muted">Rp {{ number_format($val->average_cost, 2, ',', '.') }}</td>
                            <td class="text-end fw-bold text-primary">Rp {{ number_format($val->inventory_value, 0, ',', '.') }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="fas fa-boxes fa-3x mb-3 text-light"></i>
                                <p class="mb-0">Belum ada data nilai persediaan yang dihitung.</p>
                                <small>Lakukan pembelian (Goods Receipt) atau set Opening Stock.</small>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <div class="mt-4">
                {{ $valuations->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
