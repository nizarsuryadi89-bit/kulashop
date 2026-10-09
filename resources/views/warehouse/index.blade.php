@extends('layouts.app')

@section('title', 'Master Gudang')
@section('page_title', 'Master Gudang')

@section('content')
<div class="row mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h1 class="h3 mb-0 text-gray-800 fw-bold">Master Gudang</h1>
            <p class="text-muted small mb-0">Kelola lokasi penyimpanan fisik barang & gudang utama online shop</p>
        </div>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createWarehouseModal">
            <i class="fas fa-plus me-1"></i> Tambah Gudang
        </button>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm" role="alert">
        <i class="fas fa-check-circle me-1"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm" role="alert">
        <i class="fas fa-exclamation-triangle me-1"></i> {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm" role="alert">
        <ul class="mb-0">
            @foreach($errors->all() as $err)
                <li>{{ $err }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white py-3 border-0">
        <h5 class="mb-0 fw-bold text-primary"><i class="fas fa-warehouse me-2"></i>Daftar Gudang Penyimpanan</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th width="5%">No</th>
                        <th>Kode Gudang</th>
                        <th>Nama Gudang</th>
                        <th>Alamat</th>
                        <th class="text-center">Tipe / Status</th>
                        <th class="text-center" style="min-width: 220px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($warehouses as $index => $warehouse)
                        <tr>
                            <td>{{ $warehouses->firstItem() + $index }}</td>
                            <td>
                                <span class="badge bg-secondary font-monospace fs-6">{{ $warehouse->code }}</span>
                            </td>
                            <td>
                                <strong class="text-dark">{{ $warehouse->name }}</strong>
                                @if($warehouse->is_default)
                                    <span class="badge bg-primary ms-1"><i class="fas fa-star me-1"></i>Gudang Utama</span>
                                @endif
                            </td>
                            <td>{{ $warehouse->address ?: '-' }}</td>
                            <td class="text-center">
                                @if($warehouse->status === 'active')
                                    <span class="badge bg-success">Aktif</span>
                                @else
                                    <span class="badge bg-danger">Nonaktif</span>
                                @endif
                            </td>
                            <td class="text-center text-nowrap">
                                <div class="d-inline-flex gap-1 align-items-center">
                                    @if(!$warehouse->is_default)
                                        <form action="{{ route('warehouses.set-default', $warehouse->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-sm btn-outline-warning px-2 py-1" title="Jadikan Gudang Utama">
                                                <i class="fas fa-star me-1"></i> Utama
                                            </button>
                                        </form>
                                    @endif

                                    <button type="button" class="btn btn-sm btn-outline-primary px-2 py-1" data-bs-toggle="modal" data-bs-target="#editWarehouseModal{{ $warehouse->id }}">
                                        <i class="fas fa-edit me-1"></i> Edit
                                    </button>

                                    @if(!$warehouse->is_default)
                                        <button type="button" class="btn btn-sm btn-outline-danger px-2 py-1" data-bs-toggle="modal" data-bs-target="#deleteWarehouseModal{{ $warehouse->id }}">
                                            <i class="fas fa-trash me-1"></i> Hapus
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">Belum ada data gudang.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($warehouses->hasPages())
        <div class="card-footer bg-white border-0 py-3">
            {{ $warehouses->links() }}
        </div>
    @endif
</div>

<!-- Modal Tambah Gudang -->
<div class="modal fade" id="createWarehouseModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="{{ route('warehouses.store') }}" method="POST">
            @csrf
            <div class="modal-content border-0 shadow">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="fas fa-plus-circle text-primary me-2"></i>Tambah Gudang Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Kode Gudang <span class="text-danger">*</span></label>
                        <input type="text" name="code" class="form-control" required maxlength="50" placeholder="Contoh: GDG-JAKARTA" style="text-transform: uppercase;">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nama Gudang <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required maxlength="100" placeholder="Contoh: Gudang Cabang Jakarta">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Alamat Lengkap</label>
                        <textarea name="address" class="form-control" rows="3" placeholder="Alamat fisik gudang..."></textarea>
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" name="is_default" value="1" id="createIsDefault">
                        <label class="form-check-label" for="createIsDefault">
                            Jadikan sebagai Gudang Utama (Default pemotongan order Shopee)
                        </label>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Status <span class="text-danger">*</span></label>
                        <select name="status" class="form-select">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Simpan Gudang</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit & Delete -->
@foreach($warehouses as $warehouse)
    <div class="modal fade" id="editWarehouseModal{{ $warehouse->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form action="{{ route('warehouses.update', $warehouse->id) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-content border-0 shadow">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold"><i class="fas fa-edit text-primary me-2"></i>Edit Gudang: {{ $warehouse->name }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Kode Gudang <span class="text-danger">*</span></label>
                            <input type="text" name="code" class="form-control" required maxlength="50" value="{{ $warehouse->code }}" style="text-transform: uppercase;">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Nama Gudang <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" required maxlength="100" value="{{ $warehouse->name }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Alamat Lengkap</label>
                            <textarea name="address" class="form-control" rows="3">{{ $warehouse->address }}</textarea>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="is_default" value="1" id="editIsDefault{{ $warehouse->id }}" {{ $warehouse->is_default ? 'checked' : '' }}>
                            <label class="form-check-label" for="editIsDefault{{ $warehouse->id }}">
                                Jadikan sebagai Gudang Utama (Default)
                            </label>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Status <span class="text-danger">*</span></label>
                            <select name="status" class="form-select">
                                <option value="active" {{ $warehouse->status === 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ $warehouse->status === 'inactive' ? 'selected' : '' }}>Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Update Gudang</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @if(!$warehouse->is_default)
        <div class="modal fade" id="deleteWarehouseModal{{ $warehouse->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <form action="{{ route('warehouses.destroy', $warehouse->id) }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <div class="modal-content border-0 shadow">
                        <div class="modal-header bg-danger text-white">
                            <h5 class="modal-title fw-bold"><i class="fas fa-exclamation-triangle me-2"></i>Konfirmasi Hapus Gudang</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            Apakah Anda yakin ingin menghapus gudang <strong>{{ $warehouse->name }} ({{ $warehouse->code }})</strong>?
                            <div class="alert alert-warning mt-3 mb-0 small">
                                <i class="fas fa-info-circle me-1"></i> Gudang hanya bisa dihapus jika seluruh saldo stok produk di dalamnya sudah kosong (0).
                            </div>
                        </div>
                        <div class="modal-footer border-0">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-danger"><i class="fas fa-trash me-1"></i> Ya, Hapus</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    @endif
@endforeach

@endsection
