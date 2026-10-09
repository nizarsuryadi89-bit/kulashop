@extends('layouts.app')

@section('title', 'Akun Pengguna')
@section('page_title', 'Akun Pengguna')

@section('content')
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white border-0 pt-4 pb-3 d-flex justify-content-between align-items-center">
        <h6 class="mb-0 text-uppercase fw-bold text-muted">Daftar Akun Pengguna</h6>
        <a href="{{ route('users.create') }}" class="btn btn-primary"><i class="fas fa-plus me-1"></i> Tambah Akun</a>
    </div>
    
    @if(session('success'))
        <div class="alert alert-success mx-4">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger mx-4">
            {{ session('error') }}
        </div>
    @endif

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-striped mb-0">
                <thead class="table-light">
                    <tr>
                        <th width="5%" class="ps-4">No</th>
                        <th width="25%">Nama</th>
                        <th width="30%">Email</th>
                        <th width="20%">Role</th>
                        <th width="20%" class="text-end pe-4">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $index => $user)
                        <tr>
                            <td class="ps-4">{{ $index + 1 }}</td>
                            <td><strong>{{ $user->name }}</strong></td>
                            <td>{{ $user->email }}</td>
                            <td>
                                @if($user->role === 'owner')
                                    <span class="badge bg-danger">Owner</span>
                                @else
                                    <span class="badge bg-info text-dark">Admin</span>
                                @endif
                            </td>
                            <td class="text-end pe-4">
                                <a href="{{ route('users.edit', $user->id) }}" class="btn btn-sm btn-outline-primary me-2">Edit</a>
                                @if(auth()->id() !== $user->id)
                                    <form action="{{ route('users.destroy', $user->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus akun ini?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Hapus</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">Belum ada data pengguna lainnya.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
