@extends('layouts.app')

@section('title', 'Kategori Pengeluaran')
@section('page_title', 'Kategori Pengeluaran')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="fw-bold mb-0">Master Kategori Pengeluaran</h5>
        <span class="text-muted small">Kelola jenis-jenis biaya operasional</span>
    </div>
    <a href="{{ route('expense-categories.create') }}" class="btn btn-primary shadow-sm">
        <i class="fas fa-plus me-2"></i> Tambah Kategori
    </a>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<div class="card border-0 shadow-sm rounded-4">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">No</th>
                        <th>Kode</th>
                        <th>Nama Kategori</th>
                        <th>Deskripsi</th>
                        <th>Status</th>
                        <th class="text-end pe-4">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($categories as $index => $category)
                        <tr>
                            <td class="ps-4">{{ $categories->firstItem() + $index }}</td>
                            <td><span class="badge bg-light text-dark border">{{ $category->code }}</span></td>
                            <td class="fw-medium text-dark">{{ $category->name }}</td>
                            <td class="text-muted">{{ $category->description ?? '-' }}</td>
                            <td>
                                @if($category->status == 'active')
                                    <span class="badge bg-success">Aktif</span>
                                @else
                                    <span class="badge bg-secondary">Non-Aktif</span>
                                @endif
                            </td>
                            <td class="text-end pe-4">
                                <a href="{{ route('expense-categories.edit', $category->id) }}" class="btn btn-sm btn-light text-primary border me-1" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form action="{{ route('expense-categories.destroy', $category->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus / menonaktifkan kategori ini?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-light text-danger border" title="Hapus">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">Belum ada kategori pengeluaran.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($categories->hasPages())
        <div class="card-footer bg-white border-0 py-3">
            {{ $categories->links() }}
        </div>
    @endif
</div>
@endsection
