@extends('layouts.app')

@section('title', 'Detail Toko: ' . $account->account_name)

@section('content')
<div class="row mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <div>
            <a href="{{ route('marketplace-accounts.index') }}" class="btn btn-sm btn-outline-secondary mb-2">
                <i class="fas fa-arrow-left"></i> Kembali ke Master Toko
            </a>
            <h1 class="h3 mb-0 text-gray-800">Detail Toko: {{ $account->account_name }}</h1>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-5">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Informasi Akun</h6>
            </div>
            <div class="card-body">
                <table class="table table-borderless mb-0">
                    <tr>
                        <th width="40%">Marketplace</th>
                        <td>: <span class="badge bg-secondary">{{ $account->marketplace->name ?? '-' }}</span></td>
                    </tr>
                    <tr>
                        <th>Nama Toko</th>
                        <td>: {{ $account->account_name }}</td>
                    </tr>
                    <tr>
                        <th>Kode</th>
                        <td>: <code>{{ $account->account_code }}</code></td>
                    </tr>
                    <tr>
                        <th>Status</th>
                        <td>: 
                            @if($account->status == 'active')
                                <span class="badge bg-success">Aktif</span>
                            @else
                                <span class="badge bg-danger">Nonaktif</span>
                            @endif
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
    
    <div class="col-md-7">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Statistik Transaksi</h6>
            </div>
            <div class="card-body">
                @if($account->orders_count > 0 || $account->product_marketplaces_count > 0 || $account->import_batches_count > 0)
                    <div class="row text-center">
                        <div class="col-md-4 mb-3">
                            <div class="border rounded p-3 bg-light">
                                <div class="text-muted small mb-1 text-uppercase fw-bold">Total Order</div>
                                <h3 class="mb-0 text-primary">{{ number_format($account->orders_count, 0, ',', '.') }}</h3>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="border rounded p-3 bg-light">
                                <div class="text-muted small mb-1 text-uppercase fw-bold">Total Produk</div>
                                <h3 class="mb-0 text-info">{{ number_format($account->product_marketplaces_count, 0, ',', '.') }}</h3>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="border rounded p-3 bg-light">
                                <div class="text-muted small mb-1 text-uppercase fw-bold">Total Import</div>
                                <h3 class="mb-0 text-success">{{ number_format($account->import_batches_count, 0, ',', '.') }}</h3>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="alert alert-warning mb-0 text-center">
                        <i class="fas fa-info-circle me-2"></i> Belum ada data transaksi
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
