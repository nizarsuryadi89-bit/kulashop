@extends('layouts.app')

@section('title', 'Detail Error Import - Kulashop')
@section('page_title', 'Detail Error Import')

@section('content')
<div class="row">
    <div class="col-md-12 mb-4">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white border-0 pt-4 pb-0 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 text-uppercase fw-bold text-muted">Informasi Batch</h6>
                <div>
                    @if(session('success'))
                        <span class="badge bg-success me-2">{{ session('success') }}</span>
                    @endif
                    @if(session('error'))
                        <span class="badge bg-danger me-2">{{ session('error') }}</span>
                    @endif
                    <a href="{{ route('import.history') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-arrow-left me-1"></i> Kembali
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <p class="text-muted mb-1 small">Batch ID</p>
                        <p class="fw-bold mb-0">#{{ $batch->id }}</p>
                    </div>
                    <div class="col-md-3">
                        <p class="text-muted mb-1 small">Marketplace</p>
                        <p class="fw-bold mb-0 text-capitalize">{{ $batch->marketplaceAccount->marketplace->code ?? '-' }}</p>
                    </div>
                    <div class="col-md-3">
                        <p class="text-muted mb-1 small">Marketplace Account</p>
                        <p class="fw-bold mb-0">{{ $batch->marketplaceAccount->name ?? '-' }}</p>
                    </div>
                    <div class="col-md-3">
                        <p class="text-muted mb-1 small">Import Type</p>
                        <p class="fw-bold mb-0">
                            @if(str_contains($batch->import_type, 'income'))
                                <span class="badge bg-info text-dark">Shopee Income</span>
                            @else
                                <span class="badge bg-primary">Shopee Order</span>
                            @endif
                        </p>
                    </div>
                    <div class="col-md-3">
                        <p class="text-muted mb-1 small">File Name</p>
                        <p class="fw-bold mb-0 text-truncate" title="{{ $batch->file_name ?? 'Unknown File' }}">{{ $batch->file_name ?? 'Unknown File' }}</p>
                    </div>
                    <div class="col-md-3">
                        <p class="text-muted mb-1 small">Period Start</p>
                        <p class="fw-bold mb-0">{{ $batch->period_start ? \Carbon\Carbon::parse($batch->period_start)->format('d M Y') : '-' }}</p>
                    </div>
                    <div class="col-md-3">
                        <p class="text-muted mb-1 small">Period End</p>
                        <p class="fw-bold mb-0">{{ $batch->period_end ? \Carbon\Carbon::parse($batch->period_end)->format('d M Y') : '-' }}</p>
                    </div>
                    <div class="col-md-3">
                        <p class="text-muted mb-1 small">Uploaded By</p>
                        <p class="fw-bold mb-0">{{ $batch->uploader->name ?? '-' }}</p>
                    </div>
                    <div class="col-md-3">
                        <p class="text-muted mb-1 small">Started At</p>
                        <p class="fw-bold mb-0">{{ $batch->started_at ? \Carbon\Carbon::parse($batch->started_at)->format('d M Y H:i:s') : '-' }}</p>
                    </div>
                    <div class="col-md-3">
                        <p class="text-muted mb-1 small">Completed At</p>
                        <p class="fw-bold mb-0">{{ $batch->completed_at ? \Carbon\Carbon::parse($batch->completed_at)->format('d M Y H:i:s') : '-' }}</p>
                    </div>
                    <div class="col-md-3">
                        <p class="text-muted mb-1 small">Status</p>
                        <p class="fw-bold mb-0">
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
                        </p>
                    </div>
                    <div class="col-md-3">
                        <p class="text-muted mb-1 small">Jumlah Error</p>
                        <p class="fw-bold text-danger mb-0">{{ $batch->error_rows }} Baris</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if($batch->status === 'failed' && !empty($batch->error_log))
    <div class="col-md-12 mb-4">
        <div class="card shadow-sm border-0 border-danger">
            <div class="card-header bg-danger text-white border-0 pt-3 pb-3">
                <h6 class="mb-0 fw-bold"><i class="fas fa-exclamation-triangle me-2"></i> Global Error (Import Gagal)</h6>
            </div>
            <div class="card-body">
                <p class="mb-2">Proses import ini berhenti atau gagal secara keseluruhan, bukan karena data baris yang salah, melainkan karena *Global Error* (misalnya: file *corrupt*, kendala server, atau koneksi database terputus).</p>
                <div class="alert alert-danger mb-0">
                    <pre class="mb-0" style="max-height: 250px; overflow-y: auto; font-size: 0.85rem; white-space: pre-wrap; word-wrap: break-word;">{{ $batch->error_log }}</pre>
                </div>
            </div>
        </div>
    </div>
    @endif

    <div class="col-md-12 mb-4">
        <div class="row g-3">
            <div class="col-md">
                <div class="card shadow-sm border-0 bg-light">
                    <div class="card-body text-center p-3">
                        <p class="text-muted mb-1 small text-uppercase">Total Row</p>
                        <h4 class="fw-bold mb-0 text-dark">{{ number_format($batch->total_rows) }}</h4>
                    </div>
                </div>
            </div>
            <div class="col-md">
                <div class="card shadow-sm border-0 bg-success bg-opacity-10">
                    <div class="card-body text-center p-3">
                        <p class="text-muted mb-1 small text-uppercase">Inserted</p>
                        <h4 class="fw-bold mb-0 text-success">{{ number_format($batch->success_rows) }}</h4>
                    </div>
                </div>
            </div>
            <div class="col-md">
                <div class="card shadow-sm border-0 bg-warning bg-opacity-10">
                    <div class="card-body text-center p-3">
                        <p class="text-muted mb-1 small text-uppercase">Updated</p>
                        <h4 class="fw-bold mb-0 text-warning">{{ number_format($batch->updated_rows) }}</h4>
                    </div>
                </div>
            </div>
            <div class="col-md">
                <div class="card shadow-sm border-0 bg-secondary bg-opacity-10">
                    <div class="card-body text-center p-3">
                        <p class="text-muted mb-1 small text-uppercase">Duplicate</p>
                        <h4 class="fw-bold mb-0 text-secondary">{{ number_format($batch->duplicate_rows) }}</h4>
                    </div>
                </div>
            </div>
            <div class="col-md">
                <div class="card shadow-sm border-0 bg-danger bg-opacity-10">
                    <div class="card-body text-center p-3">
                        <p class="text-muted mb-1 small text-uppercase">Error</p>
                        <h4 class="fw-bold mb-0 text-danger">{{ number_format($batch->error_rows) }}</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if($batch->error_rows > 0)
    <div class="col-md-12">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white border-0 pt-4 pb-3">
                <h6 class="mb-0 text-uppercase fw-bold text-danger">Data Error</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped mb-0" style="font-size: 0.9rem;">
                        <thead class="table-light">
                            <tr>
                                <th>Row</th>
                                <th>Order ID</th>
                                <th>Error Type</th>
                                <th>Error Message</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($errors as $error)
                            <tr>
                                <td>{{ $error->row_number ?? '-' }}</td>
                                <td>{{ $error->external_order_id ?? '-' }}</td>
                                <td>
                                    @if($error->is_resolved)
                                        <span class="badge bg-success">Resolved</span>
                                    @else
                                        <span class="badge bg-danger">{{ class_basename($error->error_type) }}</span>
                                    @endif
                                </td>
                                <td>
                                    <div style="max-width: 400px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="{{ $error->error_message }}">
                                        {{ $error->error_message }}
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-1">
                                        <a href="{{ route('import.error.show', $error->id) }}" class="btn btn-sm btn-outline-info">
                                            <i class="fas fa-eye me-1"></i> Lihat
                                        </a>
                                        @if($error->isRetriable() && !$error->is_resolved)
                                        <form action="{{ route('import.retry', $error->id) }}" method="POST" class="d-inline m-0">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-primary" onclick="return confirm('Retry pemrosesan baris ini?')">
                                                <i class="fas fa-sync-alt me-1"></i> Retry
                                            </button>
                                        </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center py-4">Tidak ada data error tercatat.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($errors->hasPages())
            <div class="card-footer bg-white pt-3 pb-0">
                {{ $errors->links('pagination::bootstrap-5') }}
            </div>
            @endif
        </div>
    </div>
    @endif
</div>
@endsection
