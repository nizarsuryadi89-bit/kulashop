@extends('layouts.app')

@section('title', 'Detail Error Rekonsiliasi - Kulashop')
@section('page_title', 'Detail Error: ' . ($error->external_order_id ?? 'Unknown Order'))

@section('content')
<div class="mb-3">
    <a href="{{ route('reconciliation.shopee.index', ['tab' => $tab]) }}" class="btn btn-sm btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i> Kembali ke Data
    </a>
</div>

<div class="row">
    <!-- Kolom Pesan Error -->
    <div class="col-md-12 mb-4">
        <div class="alert alert-danger border-0 shadow-sm">
            <h5 class="fw-bold"><i class="fas fa-times-circle me-2"></i> WAITING ORDER (Order Tidak Ditemukan)</h5>
            <p class="mb-0">Sistem mendeteksi ada dana masuk untuk nomor pesanan <b>{{ $error->external_order_id }}</b> dari file penghasilan <b>{{ $error->batch->file_name ?? 'Unknown Batch' }}</b>, namun pesanan tersebut <b>belum pernah diimport</b> ke dalam ERP. Harap import pesanan tersebut terlebih dahulu agar sistem dapat melakukan pencocokan data keuangan.</p>
            <div class="mt-3">
                <a href="{{ route('import.index') }}" class="btn btn-danger fw-bold"><i class="fas fa-upload me-1"></i> Import Order Sekarang</a>
            </div>
        </div>
    </div>

    <!-- Kolom Raw Data Income -->
    <div class="col-md-12 mb-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-dark text-white border-0 py-3">
                <h6 class="mb-0 fw-bold"><i class="fas fa-code me-2"></i> RAW DATA INCOME (DARI FILE EXCEL)</h6>
            </div>
            <div class="card-body bg-light">
                <pre class="mb-0" style="font-size: 0.85rem;"><code>{{ json_encode($error->raw_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</code></pre>
            </div>
        </div>
    </div>
</div>
@endsection
