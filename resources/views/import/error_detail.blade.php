@extends('layouts.app')

@section('title', 'Detail Error - Kulashop')
@section('page_title', 'Detail Error')

@section('content')
<div class="row">
    <div class="col-md-12 mb-4">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white border-0 pt-4 pb-0 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 text-uppercase fw-bold text-muted">Informasi Error</h6>
                <div>
                    <a href="{{ route('import.show', $error->import_batch_id) }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-arrow-left me-1"></i> Kembali ke Batch
                    </a>
                    @if($error->isRetriable() && !$error->is_resolved)
                    <form action="{{ route('import.retry', $error->id) }}" method="POST" class="d-inline m-0">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-primary" onclick="return confirm('Retry pemrosesan baris ini?')">
                            <i class="fas fa-sync-alt me-1"></i> Retry
                        </button>
                    </form>
                    @endif
                </div>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-2">
                        <p class="text-muted mb-1 small">Batch</p>
                        <p class="fw-bold mb-0">
                            <a href="{{ route('import.show', $error->import_batch_id) }}">#{{ $error->import_batch_id }}</a>
                        </p>
                    </div>
                    <div class="col-md-2">
                        <p class="text-muted mb-1 small">Row Number</p>
                        <p class="fw-bold mb-0">{{ $error->row_number ?? '-' }}</p>
                    </div>
                    <div class="col-md-3">
                        <p class="text-muted mb-1 small">External Order ID</p>
                        <p class="fw-bold mb-0">{{ $error->external_order_id ?? '-' }}</p>
                    </div>
                    <div class="col-md-2">
                        <p class="text-muted mb-1 small">Status / Type</p>
                        <p class="mb-0">
                            @if($error->is_resolved)
                                <span class="badge bg-success">Resolved</span>
                            @else
                                <span class="badge bg-danger">{{ class_basename($error->error_type) }}</span>
                            @endif
                        </p>
                    </div>
                    <div class="col-md-3">
                        <p class="text-muted mb-1 small">Created At</p>
                        <p class="fw-bold mb-0">{{ $error->created_at->format('d M Y H:i:s') }}</p>
                    </div>
                    <div class="col-md-12">
                        <p class="text-muted mb-1 small">Error Message</p>
                        <div class="alert alert-danger mb-0">
                            {{ $error->error_message }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-12">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white border-0 pt-4 pb-3">
                <h6 class="mb-0 text-uppercase fw-bold text-muted">Source Data</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-bordered mb-0" style="font-size: 0.9rem; table-layout: fixed; width: 100%;">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 30%;">Field</th>
                                <th style="width: 70%;">Value</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if(is_array($error->raw_data) || is_object($error->raw_data))
                                @forelse($error->raw_data as $key => $value)
                                    <tr>
                                        <td class="text-muted fw-bold text-truncate" title="{{ $key }}">{{ $key }}</td>
                                        <td>
                                            <div style="max-height: 150px; overflow-y: auto; word-wrap: break-word; white-space: pre-wrap;">{{ is_array($value) || is_object($value) ? json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : $value }}</div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="2" class="text-center py-4">Data kosong.</td>
                                    </tr>
                                @endforelse
                            @else
                                <tr>
                                    <td colspan="2">
                                        <pre class="mb-0 bg-light p-3 rounded" style="max-height: 400px; overflow-y: auto; white-space: pre-wrap; word-wrap: break-word;">{{ $error->raw_data }}</pre>
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
