@extends('layouts.app')

@section('title', 'Master Supplier')
@section('page_title', 'Master Supplier')

@section('content')
<div class="card shadow-sm border-0">
    <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
        <h5 class="mb-0 fw-bold">Daftar Supplier</h5>
        <a href="{{ route('purchase.suppliers.create') }}" class="btn btn-primary">
            <i class="fas fa-plus me-1"></i> Tambah Supplier
        </a>
    </div>
    <div class="card-body">
        <form action="{{ route('purchase.suppliers.index') }}" method="GET" class="row g-2 mb-4">
            <div class="col-md-4">
                <input type="text" name="search" class="form-control" placeholder="Cari kode atau nama..." value="{{ $search }}">
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select" onchange="this.form.submit()">
                    <option value="">Semua Status</option>
                    <option value="active" {{ $status == 'active' ? 'selected' : '' }}>Aktif</option>
                    <option value="inactive" {{ $status == 'inactive' ? 'selected' : '' }}>Non-aktif</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-secondary w-100">Cari</button>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Kode</th>
                        <th>Nama Supplier</th>
                        <th>Contact Person</th>
                        <th>Telepon</th>
                        <th>Kota</th>
                        <th>Status</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($suppliers as $s)
                    <tr>
                        <td><span class="badge bg-secondary">{{ $s->supplier_code }}</span></td>
                        <td class="fw-semibold">{{ $s->name }}</td>
                        <td>{{ $s->contact_person ?? '-' }}</td>
                        <td>{{ $s->phone ?? '-' }}</td>
                        <td>{{ $s->city ?? '-' }}</td>
                        <td>
                            @if($s->status == 'active')
                                <span class="badge bg-success">Aktif</span>
                            @else
                                <span class="badge bg-danger">Non-aktif</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <a href="{{ route('purchase.suppliers.edit', $s->id) }}" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">Belum ada data supplier.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            {{ $suppliers->links() }}
        </div>
    </div>
</div>
@endsection
