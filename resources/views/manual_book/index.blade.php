@extends('layouts.app')

@section('title', 'Buku Panduan Penggunaan (Manual Book)')

@section('content')
<div class="row mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h1 class="h3 mb-1 text-gray-800 fw-bold">
                <i class="fas fa-book-open text-primary me-2"></i> Buku Panduan Penggunaan
            </h1>
            <p class="text-muted small mb-0">Dokumentasi resmi dan panduan operasional sistem ERP Online Shop (Versi 1.0)</p>
        </div>
        <div class="d-flex gap-2">
            <button onclick="window.print()" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-print me-1"></i> Cetak Dokumen
            </button>
            <a href="{{ route('dashboard') }}" class="btn btn-sm btn-primary">
                <i class="fas fa-home me-1"></i> Ke Dashboard
            </a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card shadow-sm border-0 mb-5">
            <div class="card-body p-4 p-md-5 manual-book-content">
                {!! $htmlContent !!}
            </div>
        </div>
    </div>
</div>

<style>
    .manual-book-content {
        line-height: 1.8;
        font-size: 0.95rem;
        color: #2d3748;
    }
    .manual-book-content h1 {
        font-size: 1.75rem;
        font-weight: 700;
        color: #1a202c;
        margin-top: 1.5rem;
        margin-bottom: 0.75rem;
        border-bottom: 2px solid #e2e8f0;
        padding-bottom: 0.5rem;
    }
    .manual-book-content h2 {
        font-size: 1.35rem;
        font-weight: 700;
        color: #2b6cb0;
        margin-top: 2rem;
        margin-bottom: 1rem;
        border-bottom: 1px solid #edf2f7;
        padding-bottom: 0.35rem;
    }
    .manual-book-content h3 {
        font-size: 1.1rem;
        font-weight: 600;
        color: #2d3748;
        margin-top: 1.5rem;
        margin-bottom: 0.5rem;
    }
    .manual-book-content blockquote {
        background-color: #f7fafc;
        border-left: 4px solid #4299e1;
        padding: 0.75rem 1.25rem;
        margin: 1.25rem 0;
        border-radius: 0 6px 6px 0;
        color: #4a5568;
    }
    .manual-book-content blockquote p {
        margin-bottom: 0;
    }
    .manual-book-content ul, .manual-book-content ol {
        padding-left: 1.5rem;
        margin-bottom: 1.25rem;
    }
    .manual-book-content li {
        margin-bottom: 0.35rem;
    }
    .manual-book-content code {
        background-color: #edf2f7;
        color: #c53030;
        padding: 0.15rem 0.4rem;
        border-radius: 4px;
        font-size: 0.875em;
    }
    .manual-book-content hr {
        border-top: 1px solid #e2e8f0;
        margin: 2.5rem 0;
        opacity: 0.7;
    }
    .manual-book-content a {
        color: #3182ce;
        text-decoration: none;
        font-weight: 500;
    }
    .manual-book-content a:hover {
        text-decoration: underline;
    }
    @media print {
        .sidebar, .navbar, .btn, .d-flex {
            display: none !important;
        }
        .content {
            margin: 0 !important;
            padding: 0 !important;
        }
        .card {
            box-shadow: none !important;
            border: none !important;
        }
    }
</style>
@endsection
