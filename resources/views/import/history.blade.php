@extends('layouts.app')

@section('title', 'Riwayat Import - Kulashop')
@section('page_title', 'Riwayat Import')

@section('content')
<div class="row">
    <div class="col-md-12 mb-4">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white border-0 pt-4 pb-0">
                <h6 class="mb-0 text-uppercase fw-bold text-muted">Filter Riwayat</h6>
            </div>
            <div class="card-body">
                <form method="GET" action="{{ route('import.history') }}" class="row g-3">
                    <div class="col-md-2">
                        <label class="form-label small text-muted">Tanggal Mulai</label>
                        <input type="date" name="tanggal_mulai" class="form-control form-control-sm" value="{{ request('tanggal_mulai') }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small text-muted">Tanggal Akhir</label>
                        <input type="date" name="tanggal_akhir" class="form-control form-control-sm" value="{{ request('tanggal_akhir') }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small text-muted">Marketplace Account</label>
                        <select name="toko" class="form-select form-select-sm">
                            <option value="">Semua</option>
                            @foreach($accounts as $acc)
                                <option value="{{ $acc->id }}" {{ request('toko') == $acc->id ? 'selected' : '' }}>{{ $acc->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small text-muted">Import Type</label>
                        <select name="import_type" class="form-select form-select-sm">
                            <option value="">Semua</option>
                            <option value="shopee_order" {{ request('import_type') == 'shopee_order' ? 'selected' : '' }}>Shopee Order</option>
                            <option value="shopee_income" {{ request('import_type') == 'shopee_income' ? 'selected' : '' }}>Shopee Income</option>
                            <option value="tiktok_order" {{ request('import_type') == 'tiktok_order' ? 'selected' : '' }}>Tiktok Order</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small text-muted">Status</label>
                        <select name="status" class="form-select form-select-sm">
                            <option value="">Semua</option>
                            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="processing" {{ request('status') == 'processing' ? 'selected' : '' }}>Processing</option>
                            <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                            <option value="completed_with_errors" {{ request('status') == 'completed_with_errors' ? 'selected' : '' }}>Completed with Errors</option>
                            <option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>Failed</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small text-muted">Cari File / ID</label>
                        <div class="input-group input-group-sm">
                            <input type="text" name="search" class="form-control" placeholder="Nama file..." value="{{ request('search') }}">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i></button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-white border-0 pt-4 pb-3 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 text-uppercase fw-bold text-muted">Daftar Riwayat Import</h6>
                <div>
                    <a href="{{ route('import.shopee_income.index') }}" class="btn btn-outline-success btn-sm me-2">
                        <i class="fas fa-file-invoice-dollar me-1"></i> Import Income
                    </a>
                    <a href="{{ route('import.index') }}" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-upload me-1"></i> Import Order
                    </a>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0" style="font-size: 0.85rem;">
                        <thead class="table-light">
                            <tr>
                                <th>No</th>
                                <th>Marketplace</th>
                                <th>Account</th>
                                <th>Jenis</th>
                                <th>File</th>
                                <th>Periode</th>
                                <th class="text-end">Total Row</th>
                                <th class="text-end">Inserted</th>
                                <th class="text-end">Updated</th>
                                <th class="text-end">Duplicate</th>
                                <th class="text-end">Error</th>
                                <th>Status</th>
                                <th>Waktu</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($batches as $batch)
                            <tr>
                                <td>{{ $batches->firstItem() + $loop->index }}</td>
                                <td><span class="fw-bold">{{ ucfirst($batch->marketplace->code ?? '-') }}</span></td>
                                <td>{{ $batch->marketplaceAccount->name ?? '-' }}</td>
                                <td>
                                    @if(str_contains($batch->import_type, 'income'))
                                        <span class="badge bg-info text-dark">Income</span>
                                    @else
                                        <span class="badge bg-primary">Order</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="text-truncate d-inline-block" style="max-width: 150px;" title="{{ $batch->file_name }}">
                                        {{ $batch->file_name }}
                                    </span>
                                </td>
                                <td class="text-nowrap" style="font-size: 0.75rem;">
                                    @if($batch->period_start && $batch->period_end)
                                        {{ \Carbon\Carbon::parse($batch->period_start)->format('d/m/y') }} - {{ \Carbon\Carbon::parse($batch->period_end)->format('d/m/y') }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="text-end">{{ number_format($batch->total_rows) }}</td>
                                <td class="text-end text-success fw-bold">{{ number_format($batch->success_rows) }}</td>
                                <td class="text-end text-warning fw-bold">{{ number_format($batch->updated_rows) }}</td>
                                <td class="text-end text-secondary">{{ number_format($batch->duplicate_rows) }}</td>
                                <td class="text-end text-danger fw-bold">{{ number_format($batch->error_rows) }}</td>
                                <td>
                                    @if($batch->status == 'completed')
                                        <span class="badge bg-success">COMPLETED</span>
                                    @elseif($batch->status == 'completed_with_errors')
                                        <span class="badge bg-warning text-dark">WITH ERRORS</span>
                                    @elseif($batch->status == 'failed')
                                        <span class="badge bg-danger">FAILED</span>
                                    @elseif($batch->status == 'processing')
                                        <span class="badge bg-warning text-dark"><i class="fas fa-circle-notch fa-spin me-1"></i>PROCESSING</span>
                                    @else
                                        <span class="badge bg-secondary">PENDING</span>
                                    @endif
                                </td>
                                <td class="text-nowrap">{{ $batch->created_at->format('d M Y H:i') }}</td>
                                <td class="text-end">
                                    <a href="{{ route('import.show', $batch->id) }}" class="btn btn-sm btn-outline-primary" style="padding: 0.1rem 0.4rem; font-size: 0.75rem;">
                                        Detail
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="14" class="text-center py-4">Belum ada riwayat import yang ditemukan.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                
                @if($batches->hasPages())
                <div class="p-3 border-top">
                    {{ $batches->links('pagination::bootstrap-5') }}
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const processingBatches = @json($batches->whereIn('status', ['pending', 'processing'])->pluck('id'));
    
    if (processingBatches.length > 0) {
        let activeBatches = [...processingBatches];
        
        const interval = setInterval(() => {
            if (activeBatches.length === 0) {
                clearInterval(interval);
                return;
            }

            activeBatches.forEach(id => {
                fetch(`/import/${id}/status`)
                    .then(res => res.json())
                    .then(data => {
                        if (data.status === 'completed' || data.status === 'completed_with_errors' || data.status === 'failed') {
                            // Remove from active list
                            activeBatches = activeBatches.filter(bId => bId !== id);
                            
                            let swalConfig = {
                                icon: 'success',
                                title: 'Import Selesai',
                                confirmButtonText: 'Tutup & Refresh',
                                allowOutsideClick: false
                            };

                            if (data.status === 'failed') {
                                swalConfig.icon = 'error';
                                swalConfig.title = 'Import Gagal (Fatal)';
                                swalConfig.html = 'Terjadi kesalahan sistem (Global Error) saat memproses file.<br><br>Silakan buka halaman Detail untuk melihat pesan error log.';
                            } else {
                                let numFmt = new Intl.NumberFormat('id-ID');
                                let html = `
                                    <div class="text-start ms-4" style="font-family: monospace; font-size: 14px;">
                                        Total Row &nbsp;: ${numFmt.format(data.total_rows)}<br>
                                        Inserted &nbsp;&nbsp;: ${numFmt.format(data.success_rows)}<br>
                                        Updated &nbsp;&nbsp;&nbsp;: ${numFmt.format(data.updated_rows)}<br>
                                        Duplicate &nbsp;: ${numFmt.format(data.duplicate_rows)}<br>
                                        Error &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: <span class="${data.error_rows > 0 ? 'text-danger fw-bold' : ''}">${numFmt.format(data.error_rows)}</span>
                                    </div>
                                `;

                                if (data.status === 'completed_with_errors') {
                                    swalConfig.icon = 'warning';
                                    swalConfig.title = `Import selesai dengan ${numFmt.format(data.error_rows)} error`;
                                    html += `<hr><span class="text-danger">Lihat detail import untuk memeriksa data bermasalah.</span>`;
                                } else {
                                    swalConfig.title = 'Import Berhasil';
                                    html += `<hr><span class="text-success">Seluruh data berhasil diproses tanpa error.</span>`;
                                }
                                
                                swalConfig.html = html;
                            }

                            Swal.fire(swalConfig).then((result) => {
                                if (result.isConfirmed) {
                                    window.location.reload();
                                }
                            });
                        }
                    })
                    .catch(err => console.error("Error polling status:", err));
            });
        }, 3000); // Poll every 3 seconds
    }
});
</script>
@endpush
