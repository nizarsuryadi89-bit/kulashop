@extends('layouts.app')

@section('title', 'Detail Marketplace: ' . $marketplace->name)

@section('content')
<div class="row mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <div>
            <a href="{{ route('marketplaces.index') }}" class="btn btn-sm btn-outline-secondary mb-2">
                <i class="fas fa-arrow-left"></i> Kembali ke Master
            </a>
            <h1 class="h3 mb-0 text-gray-800">Detail Marketplace: {{ $marketplace->name }}</h1>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Informasi Marketplace</h6>
            </div>
            <div class="card-body">
                <table class="table table-borderless">
                    <tr>
                        <th width="40%">Nama</th>
                        <td>: {{ $marketplace->name }}</td>
                    </tr>
                    <tr>
                        <th>Kode</th>
                        <td>: <span class="badge bg-secondary">{{ $marketplace->code }}</span></td>
                    </tr>
                    <tr>
                        <th>Status</th>
                        <td>: 
                            @if($marketplace->is_active)
                                <span class="badge bg-success">Aktif</span>
                            @else
                                <span class="badge bg-danger">Nonaktif</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th>Total Toko</th>
                        <td>: {{ $marketplace->accounts->count() }}</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">Daftar Akun Toko di {{ $marketplace->name }}</h6>
                <!-- Nanti tombol Tambah Toko akan dipindahkan/dibuat fiturnya di manajemen toko terpisah -->
                <!-- <button class="btn btn-sm btn-primary">Tambah Toko</button> -->
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th width="5%">No</th>
                                <th>Nama Toko (Akun)</th>
                                <th>Kode Akun</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($marketplace->accounts as $index => $account)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $account->account_name }}</td>
                                    <td><code>{{ $account->account_code }}</code></td>
                                    <td>
                                        @if($account->status == 'active')
                                            <span class="badge bg-success">Aktif</span>
                                        @else
                                            <span class="badge bg-danger">Nonaktif</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-4">Belum ada akun toko di marketplace ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-3">
                    <small class="text-muted">* Manajemen detail akun toko (penambahan, pengeditan pajak) akan dilakukan di modul Master Toko / Marketplace Accounts.</small>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
