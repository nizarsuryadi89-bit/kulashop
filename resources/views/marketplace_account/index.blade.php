@extends('layouts.app')

@section('title', 'Master Toko (Marketplace Account)')

@section('content')
<div class="row mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <h1 class="h3 mb-0 text-gray-800">Master Toko (Account)</h1>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createModal">
            <i class="fas fa-plus"></i> Tambah Toko
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
                <h6 class="m-0 font-weight-bold text-primary">Daftar Toko</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle" id="dataTable">
                        <thead class="table-light">
                            <tr>
                                <th width="5%">No</th>
                                <th>Marketplace</th>
                                <th>Nama Toko</th>
                                <th>Kode Account</th>
                                <th>Pajak (%)</th>
                                <th>Status</th>
                                <th class="text-center" style="min-width: 220px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($accounts as $index => $account)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>
                                        <span class="badge bg-secondary">{{ $account->marketplace->name ?? '-' }}</span>
                                    </td>
                                    <td>{{ $account->account_name }}</td>
                                    <td><code>{{ $account->account_code }}</code></td>
                                    <td>{{ number_format($account->tax_percentage, 2, ',', '.') }}%</td>
                                    <td>
                                        @if($account->status == 'active')
                                            <span class="badge bg-success">Aktif</span>
                                        @else
                                            <span class="badge bg-danger">Nonaktif</span>
                                        @endif
                                    </td>
                                    <td class="text-center text-nowrap">
                                        <div class="d-inline-flex gap-1 align-items-center">
                                            <a href="{{ route('marketplace-accounts.show', $account->id) }}" class="btn btn-sm btn-outline-info px-2 py-1" title="Detail">
                                                <i class="fas fa-eye me-1"></i> Detail
                                            </a>
                                            <button type="button" class="btn btn-sm btn-outline-primary px-2 py-1" data-bs-toggle="modal" data-bs-target="#editModal{{ $account->id }}" title="Edit">
                                                <i class="fas fa-edit me-1"></i> Edit
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-danger px-2 py-1" data-bs-toggle="modal" data-bs-target="#deleteModal{{ $account->id }}" title="Hapus">
                                                <i class="fas fa-trash me-1"></i> Hapus
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
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
        <form action="{{ route('marketplace-accounts.store') }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Toko</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Platform Marketplace</label>
                        <select name="marketplace_id" class="form-select" required>
                            <option value="">-- Pilih Marketplace --</option>
                            @foreach($marketplaces as $mp)
                                <option value="{{ $mp->id }}">{{ $mp->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nama Toko (Sesuai Akun)</label>
                        <input type="text" name="account_name" class="form-control" required maxlength="100" placeholder="Contoh: Butikchlaris">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Kode Account</label>
                        <input type="text" name="account_code" class="form-control" required maxlength="50" style="text-transform: uppercase;" placeholder="Contoh: SHOPEE-BCL">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Pajak Aplikasi (%)</label>
                        <div class="input-group">
                            <input type="number" step="0.01" min="0" max="100" name="tax_percentage" class="form-control" placeholder="Contoh: 10.5" value="0">
                            <span class="input-group-text">%</span>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select" required>
                            <option value="active">Aktif</option>
                            <option value="inactive">Nonaktif</option>
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
@foreach($accounts as $account)
    <div class="modal fade" id="editModal{{ $account->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form action="{{ route('marketplace-accounts.update', $account->id) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Toko</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Platform Marketplace</label>
                            <select name="marketplace_id" class="form-select" required>
                                @foreach($marketplaces as $mp)
                                    <option value="{{ $mp->id }}" {{ $account->marketplace_id == $mp->id ? 'selected' : '' }}>{{ $mp->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nama Toko (Sesuai Akun)</label>
                            <input type="text" name="account_name" class="form-control" required maxlength="100" value="{{ $account->account_name }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Kode Account</label>
                            <input type="text" name="account_code" class="form-control" required maxlength="50" style="text-transform: uppercase;" value="{{ $account->account_code }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Pajak Aplikasi (%)</label>
                            <div class="input-group">
                                <input type="number" step="0.01" min="0" max="100" name="tax_percentage" class="form-control" value="{{ $account->tax_percentage }}">
                                <span class="input-group-text">%</span>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select" required>
                                <option value="active" {{ $account->status == 'active' ? 'selected' : '' }}>Aktif</option>
                                <option value="inactive" {{ $account->status == 'inactive' ? 'selected' : '' }}>Nonaktif</option>
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
    <div class="modal fade" id="deleteModal{{ $account->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form action="{{ route('marketplace-accounts.destroy', $account->id) }}" method="POST">
                @csrf
                @method('DELETE')
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Konfirmasi Hapus</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        Apakah Anda yakin ingin menghapus akun toko <strong>{{ $account->account_name }}</strong> secara permanen?
                        <br><br>
                        <small class="text-danger">
                            <i class="fas fa-exclamation-triangle"></i> Toko tidak akan bisa dihapus jika sudah pernah melakukan transaksi (order/impor/produk terkait).
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

@push('scripts')
<script>
    $(document).ready(function() {
        $('#dataTable').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/id.json',
            }
        });
    });
</script>
@endpush
