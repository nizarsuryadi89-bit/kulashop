@extends('layouts.app')

@section('title', 'Produk Shopee Belum Dimapping')
@section('page_title', 'Produk Shopee Belum Dimapping')

@section('content')
<div class="card shadow-sm border-0">
    <div class="card-header bg-white border-0 pt-4 pb-2 d-flex justify-content-between align-items-center">
        <h5 class="mb-0 fw-bold">Daftar SKU Belum Ter-Mapping</h5>
        <div class="d-flex gap-2">
            <form action="{{ route('product.unmapped.sync') }}" method="POST" class="d-inline form-sync-all">
                @csrf
                <button type="submit" class="btn btn-sm btn-primary">
                    <i class="fas fa-sync-alt me-1"></i> Sinkronisasi Otomatis
                </button>
            </form>
            <a href="{{ route('product.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Kembali ke Master Produk
            </a>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-striped mb-0">
                <thead class="table-light">
                    <tr>
                        <th width="5%">No</th>
                        <th width="20%">Marketplace SKU</th>
                        <th width="30%">Nama Produk (Terakhir)</th>
                        <th width="15%">Variant (Terakhir)</th>
                        <th width="10%" class="text-center">Jumlah Order</th>
                        <th width="10%" class="text-center">Total Qty</th>
                        <th width="10%" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($unmappedSkus as $item)
                        <tr>
                            <td>{{ ($unmappedSkus->currentPage() - 1) * $unmappedSkus->perPage() + $loop->iteration }}</td>
                            <td>
                                <span class="badge bg-secondary fs-6">{{ $item->marketplace_sku }}</span>
                            </td>
                            <td><strong class="text-dark">{{ $item->product_name }}</strong></td>
                            <td>{{ $item->variant_name ?? '-' }}</td>
                            <td class="text-center">
                                <span class="badge bg-info text-dark fs-6">{{ number_format($item->total_orders) }}</span>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-success fs-6">{{ number_format($item->total_qty) }}</span>
                            </td>
                            <td class="text-center">
                                <!-- Create new product pre-filled with this SKU -->
                                <a href="{{ route('product.create', ['sku' => $item->marketplace_sku, 'name' => $item->product_name]) }}" class="btn btn-sm btn-primary" title="Buat Master & Mapping Baru">
                                    <i class="fas fa-plus"></i> Mapping
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="fas fa-check-circle fa-3x mb-3 text-success"></i>
                                <h5>Semua SKU sudah ter-mapping!</h5>
                                <p>Tidak ada transaksi Shopee dengan SKU yang tidak dikenali.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($unmappedSkus->hasPages())
        <div class="card-footer bg-white border-0 py-3">
            {{ $unmappedSkus->links() }}
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const syncForm = document.querySelector('.form-sync-all');
        if (syncForm) {
            syncForm.addEventListener('submit', function(e) {
                e.preventDefault();
                Swal.fire({
                    title: 'Sinkronisasi Otomatis?',
                    text: 'Sistem akan membuat Master Produk baru untuk setiap SKU yang belum dikenali dan memetakannya secara otomatis.',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#0d6efd',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: '<i class="fas fa-sync-alt"></i> Ya, Sinkronkan!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.fire({
                            title: 'Memproses...',
                            text: 'Mohon tunggu sebentar.',
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });
                        syncForm.submit();
                    }
                });
            });
        }
    });
</script>
@endpush
