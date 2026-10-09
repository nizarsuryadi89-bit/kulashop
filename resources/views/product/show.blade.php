@extends('layouts.app')

@section('title', 'Detail Produk')
@section('page_title', 'Detail Produk')

@section('content')
<div class="row">
    <!-- Kolom Kiri: Informasi Produk & Ringkasan Penjualan -->
    <div class="col-md-5">
        
        <!-- Informasi Produk -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white border-0 pt-4 pb-2 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold">Informasi Produk</h5>
                <div>
                    @if($product->status === 'active')
                        <span class="badge bg-success">Aktif</span>
                    @else
                        <span class="badge bg-danger">Nonaktif</span>
                    @endif
                </div>
            </div>
            <div class="card-body">
                <div class="text-center mb-4">
                    @if($product->image)
                        <img src="{{ Storage::url($product->image) }}" alt="{{ $product->product_name }}" class="img-fluid rounded shadow-sm" style="max-height: 200px; object-fit: cover;">
                    @else
                        <div class="bg-light rounded d-flex align-items-center justify-content-center text-muted shadow-sm border mx-auto" style="width: 150px; height: 150px;">
                            <i class="fas fa-image fa-3x"></i>
                        </div>
                    @endif
                </div>

                <table class="table table-sm table-borderless">
                    <tbody>
                        <tr>
                            <th width="40%" class="text-muted">SKU</th>
                            <td><span class="badge bg-secondary">{{ $product->sku }}</span></td>
                        </tr>
                        <tr>
                            <th class="text-muted">Nama</th>
                            <td><strong class="text-dark">{{ $product->product_name }}</strong></td>
                        </tr>
                        <tr>
                            <th class="text-muted">Varian</th>
                            <td>{{ $product->variant_name ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th class="text-muted">Kategori</th>
                            <td>{{ $product->category ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th class="text-muted">Satuan</th>
                            <td>{{ $product->unit ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th class="text-muted">HPP</th>
                            <td>Rp {{ number_format($product->hpp, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <th class="text-muted">Harga Jual</th>
                            <td>Rp {{ number_format($product->selling_price, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <th class="text-muted">Margin Kotor Teoritis</th>
                            <td class="text-success fw-bold">Rp {{ number_format($product->selling_price - $product->hpp, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <th class="text-muted">Stock</th>
                            <td>
                                <span class="badge {{ $product->stock > 0 ? 'bg-success' : 'bg-danger' }}">
                                    {{ $product->stock }}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th class="text-muted">Minimum Stock</th>
                            <td>{{ $product->minimum_stock ?? 0 }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Ringkasan Penjualan -->
        <div class="card shadow-sm border-0 mb-4 bg-primary text-white">
            <div class="card-body">
                <h6 class="fw-bold mb-3"><i class="fas fa-chart-line me-2"></i> Ringkasan Penjualan</h6>
                <div class="row text-center">
                    <div class="col-6 border-end border-light border-opacity-25">
                        <h3 class="mb-0 fw-bold">{{ number_format($totalOrders) }}</h3>
                        <small class="text-white-50">Total Order</small>
                    </div>
                    <div class="col-6">
                        <h3 class="mb-0 fw-bold">{{ number_format($totalQtySold) }}</h3>
                        <small class="text-white-50">Total Qty Terjual</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Kolom Kanan: Marketplace Mapping -->
    <div class="col-md-7">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white border-0 pt-4 pb-2 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold">Marketplace Mapping</h5>
                <a href="{{ route('product.mapping', $product->id) }}" class="btn btn-sm btn-info text-white">
                    <i class="fas fa-link me-1"></i> Kelola Mapping
                </a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-striped mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Marketplace</th>
                                <th>Account</th>
                                <th>Marketplace SKU</th>
                                <th>Product ID</th>
                                <th>Variant</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($product->productMarketplaces as $mapping)
                                <tr>
                                    <td>
                                        <span class="badge" style="background-color: {{ $mapping->marketplaceAccount->marketplace->color ?? '#6c757d' }}">
                                            {{ $mapping->marketplaceAccount->marketplace->name }}
                                        </span>
                                    </td>
                                    <td>{{ $mapping->marketplaceAccount->account_name }}</td>
                                    <td><strong>{{ $mapping->marketplace_sku }}</strong></td>
                                    <td>{{ $mapping->marketplace_product_id ?? '-' }}</td>
                                    <td>{{ $mapping->marketplace_variant ?? '-' }}</td>
                                    <td>
                                        @if($mapping->is_active)
                                            <span class="badge bg-success">Aktif</span>
                                        @else
                                            <span class="badge bg-danger">Nonaktif</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">
                                        <i class="fas fa-link fa-2x mb-3 text-light"></i>
                                        <p class="mb-0">Belum ada mapping Shopee.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Riwayat Perubahan HPP -->
        <div class="card shadow-sm border-0 mt-4 h-100">
            <div class="card-header bg-white border-0 pt-4 pb-2 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold">Riwayat Perubahan HPP</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-striped mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Tanggal Berlaku</th>
                                <th class="text-end">HPP Lama</th>
                                <th class="text-end">HPP Baru</th>
                                <th>Alasan</th>
                                <th>Diobuh Oleh</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($product->hppHistories as $history)
                                <tr>
                                    <td>
                                        {{ $history->effective_at ? $history->effective_at->format('d/m/Y H:i') : $history->created_at->format('d/m/Y H:i') }}
                                        @if($history->effective_at && $history->effective_at->format('Y-m-d') != $history->created_at->format('Y-m-d'))
                                            <br><small class="text-muted">Dibuat: {{ $history->created_at->format('d/m/Y') }}</small>
                                        @endif
                                    </td>
                                    <td class="text-end text-danger text-decoration-line-through">Rp {{ number_format($history->old_hpp, 0, ',', '.') }}</td>
                                    <td class="text-end text-success fw-bold">Rp {{ number_format($history->new_hpp, 0, ',', '.') }}</td>
                                    <td>{{ $history->reason ?? '-' }}</td>
                                    <td>{{ $history->changer->name ?? 'System' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">
                                        <i class="fas fa-history fa-2x mb-3 text-light"></i>
                                        <p class="mb-0">Belum ada riwayat perubahan HPP.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
            <div class="card-footer bg-white border-0 py-3 text-end">
                <a href="{{ route('product.index') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Kembali ke Daftar
                </a>
                <button type="button" class="btn btn-warning text-dark" data-bs-toggle="modal" data-bs-toggle="modal" data-bs-target="#updateHppModal">
                    <i class="fas fa-money-bill-wave me-1"></i> Update HPP
                </button>
                <a href="{{ route('product.edit', $product->id) }}" class="btn btn-primary">
                    <i class="fas fa-edit me-1"></i> Edit Produk
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Modal Update HPP -->
<div class="modal fade" id="updateHppModal" tabindex="-1" aria-labelledby="updateHppModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form action="{{ route('product.update-hpp', $product->id) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="modal-content">
                <div class="modal-header bg-light">
                    <h5 class="modal-title" id="updateHppModalLabel"><i class="fas fa-money-bill-wave text-warning me-2"></i>Update HPP Produk</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label text-muted">HPP Lama</label>
                        <input type="text" class="form-control bg-light text-danger text-decoration-line-through" value="Rp {{ number_format($product->hpp, 0, ',', '.') }}" readonly>
                    </div>
                    <div class="mb-3">
                        <label for="new_hpp" class="form-label fw-bold">HPP Baru <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="number" class="form-control fw-bold text-success" id="new_hpp" name="new_hpp" value="{{ old('new_hpp', $product->hpp) }}" required min="0">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="reason" class="form-label fw-bold">Alasan Perubahan <span class="text-danger">*</span></label>
                        <select class="form-select" id="reason" name="reason" required>
                            <option value="">Pilih alasan...</option>
                            <option value="Harga supplier naik">Harga supplier naik</option>
                            <option value="Harga supplier turun">Harga supplier turun</option>
                            <option value="Harga bahan baku berubah">Harga bahan baku berubah</option>
                            <option value="Biaya produksi naik">Biaya produksi naik</option>
                            <option value="Koreksi HPP">Koreksi HPP (Kesalahan input)</option>
                            <option value="Lainnya">Lainnya...</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning fw-bold text-dark"><i class="fas fa-save me-1"></i> Simpan HPP</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
