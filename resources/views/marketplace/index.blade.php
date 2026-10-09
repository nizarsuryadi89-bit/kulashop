@extends('layouts.app')

@section('title', 'Master Marketplace')

@section('content')
<div class="row mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <h1 class="h3 mb-0 text-gray-800">Master Marketplace</h1>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createModal">
            <i class="fas fa-plus"></i> Tambah Marketplace
        </button>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    {{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

@if($errors->any())
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <ul class="mb-0">
        @foreach($errors->all() as $err)
            <li>{{ $err }}</li>
        @endforeach
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

<div class="row">
    <div class="col-12">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Daftar Marketplace</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th width="5%">No</th>
                                <th>Marketplace</th>
                                <th>Kode</th>
                                <th>Jumlah Toko</th>
                                <th>Status</th>
                                <th class="text-center" style="min-width: 220px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($marketplaces as $index => $marketplace)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $marketplace->name }}</td>
                                    <td><span class="badge bg-secondary">{{ $marketplace->code }}</span></td>
                                    <td>
                                        <span class="badge bg-info text-dark">{{ $marketplace->accounts_count }} Toko</span>
                                    </td>
                                    <td>
                                        @if($marketplace->is_active)
                                            <span class="badge bg-success">Aktif</span>
                                        @else
                                            <span class="badge bg-danger">Nonaktif</span>
                                        @endif
                                    </td>
                                    <td class="text-center text-nowrap">
                                        <div class="d-inline-flex gap-1 align-items-center">
                                            <a href="{{ route('marketplaces.show', $marketplace->id) }}" class="btn btn-sm btn-outline-info px-2 py-1" title="Detail">
                                                <i class="fas fa-eye me-1"></i> Detail
                                            </a>
                                            <button type="button" class="btn btn-sm btn-outline-primary px-2 py-1" data-bs-toggle="modal" data-bs-target="#editModal{{ $marketplace->id }}" title="Edit">
                                                <i class="fas fa-edit me-1"></i> Edit
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-danger px-2 py-1" data-bs-toggle="modal" data-bs-target="#deleteModal{{ $marketplace->id }}" title="Hapus">
                                                <i class="fas fa-trash me-1"></i> Hapus
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4">Belum ada data marketplace.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Create -->
<div class="modal fade" id="createModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="{{ route('marketplaces.store') }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Marketplace</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nama Marketplace</label>
                        <input type="text" name="name" class="form-control" required maxlength="100" placeholder="Contoh: Shopee">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Kode Marketplace</label>
                        <input type="text" name="code" class="form-control" required maxlength="50" style="text-transform: uppercase;" placeholder="Contoh: SHOPEE">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="is_active" class="form-select" required>
                            <option value="1">Aktif</option>
                            <option value="0">Nonaktif</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modals Edit -->
@foreach($marketplaces as $marketplace)
    <div class="modal fade" id="editModal{{ $marketplace->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form action="{{ route('marketplaces.update', $marketplace->id) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Marketplace</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Nama Marketplace</label>
                            <input type="text" name="name" class="form-control" required maxlength="100" value="{{ $marketplace->name }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Kode Marketplace</label>
                            <input type="text" name="code" class="form-control" required maxlength="50" style="text-transform: uppercase;" value="{{ $marketplace->code }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select name="is_active" class="form-select" required>
                                <option value="1" {{ $marketplace->is_active ? 'selected' : '' }}>Aktif</option>
                                <option value="0" {{ !$marketplace->is_active ? 'selected' : '' }}>Nonaktif</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Update</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Delete -->
    <div class="modal fade" id="deleteModal{{ $marketplace->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form action="{{ route('marketplaces.destroy', $marketplace->id) }}" method="POST">
                @csrf
                @method('DELETE')
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Konfirmasi Hapus</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        Apakah Anda yakin ingin menghapus marketplace <strong>{{ $marketplace->name }}</strong> secara permanen?
                        <br><br>
                        <small class="text-danger">
                            <i class="fas fa-exclamation-triangle"></i> Marketplace tidak akan bisa dihapus jika sudah memiliki toko (akun) yang tertaut.
                        </small>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-danger">Ya, Hapus</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endforeach

@endsection
