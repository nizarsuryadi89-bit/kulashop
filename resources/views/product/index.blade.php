@extends('layouts.app')

@section('title', 'Master Produk')
@section('page_title', 'Master Produk')

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
            <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm" role="alert">
                <i class="fas fa-exclamation-circle me-1"></i> {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <!-- Filter & Search Section -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                <form action="{{ route('product.index') }}" method="GET" class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label text-muted small fw-bold">Pencarian</label>
                        <input type="text" name="search" class="form-control" placeholder="Cari SKU, Nama, Varian..." value="{{ request('search') }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label text-muted small fw-bold">Status</label>
                        <select name="status" class="form-select">
                            <option value="">Semua Status</option>
                            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Aktif</option>
                            <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Nonaktif</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label text-muted small fw-bold">Kategori</label>
                        <select name="category" class="form-select">
                            <option value="">Semua Kategori</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat }}" {{ request('category') == $cat ? 'selected' : '' }}>{{ $cat }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label text-muted small fw-bold">Status Mapping Shopee</label>
                        <select name="mapping_status" class="form-select">
                            <option value="">Semua</option>
                            <option value="mapped" {{ request('mapping_status') === 'mapped' ? 'selected' : '' }}>Sudah Mapping</option>
                            <option value="unmapped" {{ request('mapping_status') === 'unmapped' ? 'selected' : '' }}>Belum Mapping</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search me-1"></i> Terapkan
                        </button>
                        @if(request()->anyFilled(['search', 'status', 'category', 'mapping_status']))
                            <a href="{{ route('product.index') }}" class="btn btn-light w-100 mt-2 text-muted border">
                                Reset
                            </a>
                        @endif
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-white border-0 pt-4 pb-0 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold">Daftar Master Produk</h5>
                <div>
                    <a href="{{ route('product.unmapped') }}" class="btn btn-warning me-2 text-dark fw-bold shadow-sm">
                        <i class="fas fa-exclamation-triangle me-1"></i> SKU Belum Mapping
                    </a>
                    <a href="{{ route('product.create') }}" class="btn btn-primary shadow-sm">
                        <i class="fas fa-plus me-1"></i> Tambah Produk
                    </a>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-striped mb-0">
                        <thead class="table-light">
                            <tr>
                                <th width="5%">No</th>
                                <th width="10%">SKU</th>
                                <th width="20%">Nama Produk</th>
                                <th width="10%">Varian</th>
                                <th width="10%">Kategori</th>
                                <th width="5%">Satuan</th>
                                <th width="10%">HPP</th>
                                <th width="10%">Harga Jual</th>
                                <th width="5%">Status</th>
                                <th width="10%">Shopee Mapping</th>
                                <th width="5%" class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($products as $product)
                                <tr>
                                    <td>{{ ($products->currentPage() - 1) * $products->perPage() + $loop->iteration }}</td>
                                    <td><span class="badge bg-secondary">{{ $product->sku }}</span></td>
                                    <td><strong class="text-dark">{{ $product->product_name }}</strong></td>
                                    <td>{{ $product->variant_name ?? '-' }}</td>
                                    <td>{{ $product->category ?? '-' }}</td>
                                    <td>{{ $product->unit ?? '-' }}</td>
                                    <td>Rp {{ number_format($product->hpp, 0, ',', '.') }}</td>
                                    <td>Rp {{ number_format($product->selling_price, 0, ',', '.') }}</td>
                                    <td>
                                        @if($product->status === 'active')
                                            <span class="badge bg-success">Aktif</span>
                                        @else
                                            <span class="badge bg-danger">Nonaktif</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if(isset($aliases[$product->id]) && $aliases[$product->id]->count() > 0)
                                            @foreach($aliases[$product->id] as $alias)
                                                <span class="badge bg-info text-dark mb-1 d-block text-start text-truncate" title="{{ $alias->marketplace_product_name ?? $alias->marketplace_sku }}" style="max-width: 150px;">
                                                    <i class="fas fa-store fa-xs"></i> {{ $alias->marketplaceAccount->account_name }}
                                                </span>
                                            @endforeach
                                        @else
                                            <span class="text-muted small">-</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="fas fa-cog"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('product.show', $product->id) }}">
                                                        <i class="fas fa-eye fa-fw text-secondary me-1"></i> Detail
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('product.edit', $product->id) }}">
                                                        <i class="fas fa-edit fa-fw text-primary me-1"></i> Edit
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('product.mapping', $product->id) }}">
                                                        <i class="fas fa-link fa-fw text-info me-1"></i> Mapping Shopee
                                                    </a>
                                                </li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <form action="{{ route('product.toggle-status', $product->id) }}" method="POST">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button type="submit" class="dropdown-item {{ $product->status === 'active' ? 'text-danger' : 'text-success' }}">
                                                            <i class="fas fa-power-off fa-fw me-1"></i> {{ $product->status === 'active' ? 'Nonaktifkan' : 'Aktifkan' }}
                                                        </button>
                                                    </form>
                                                </li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-5">
                                        <div class="text-muted">
                                            <i class="fas fa-box-open fs-1 mb-3"></i>
                                            <h5>Belum Ada Data Produk</h5>
                                            <p>Silakan tambah produk baru terlebih dahulu.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($products->hasPages())
            <div class="card-footer bg-white border-0 pt-3">
                {{ $products->links('pagination::bootstrap-5') }}
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
