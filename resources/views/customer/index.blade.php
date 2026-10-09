@extends('layouts.app')

@section('title', 'Master Pelanggan')
@section('page_title', 'Master Pelanggan')

@section('content')
<div class="row">
    <div class="col-md-12">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show rounded-3 border-0 shadow-sm" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body bg-white rounded-3">
                <form action="{{ route('customers.index') }}" method="GET" class="d-flex flex-column flex-md-row gap-3">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="fas fa-search text-muted"></i></span>
                        <input type="text" name="search" class="form-control border-start-0 ps-0 bg-light" placeholder="Cari berdasarkan Nama, No HP, atau Kota..." value="{{ request('search') }}">
                    </div>
                    <button type="submit" class="btn btn-primary px-4">Cari</button>
                    @if(request('search'))
                        <a href="{{ route('customers.index') }}" class="btn btn-outline-secondary px-4">Reset</a>
                    @endif
                </form>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-white border-0 pt-4 pb-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold">Daftar Pelanggan</h5>
                <a href="{{ route('customers.create') }}" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus me-1"></i> Tambah Pelanggan Baru
                </a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-striped mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Nama Pelanggan</th>
                                <th>No. Handphone</th>
                                <th>Alamat Lengkap</th>
                                <th>Wilayah</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($customers as $customer)
                                <tr>
                                    <td>#{{ $customer->id }}</td>
                                    <td>
                                        <strong class="text-dark">{{ $customer->customer_name ?: ($customer->username ?: '-') }}</strong>
                                        @if($customer->username && $customer->customer_name && $customer->username !== $customer->customer_name)
                                            <div class="small text-muted"><i class="fas fa-at me-1"></i>{{ $customer->username }}</div>
                                        @endif
                                    </td>
                                    <td>{{ $customer->phone ?? '-' }}</td>
                                    <td>
                                        <div class="text-wrap" style="max-width: 250px;">
                                            {{ Str::limit($customer->address ?? '-', 60) }}
                                        </div>
                                    </td>
                                    <td>
                                        @if($customer->city || $customer->province)
                                            {{ $customer->city ?? '-' }}, {{ $customer->province ?? '-' }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <a href="{{ route('customers.edit', $customer->id) }}" class="btn btn-sm btn-outline-primary mb-1">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="{{ route('customers.destroy', $customer->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus data ini?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger mb-1">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-5">
                                        <div class="text-muted">
                                            <i class="fas fa-users fs-1 mb-3"></i>
                                            <h5>Belum Ada Data Pelanggan</h5>
                                            <p>Data pelanggan yang melakukan transaksi akan muncul di sini.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($customers->hasPages())
            <div class="card-footer bg-white border-0 pt-3">
                {{ $customers->links('pagination::bootstrap-5') }}
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
