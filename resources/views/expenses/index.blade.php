@extends('layouts.app')

@section('title', 'Daftar Pengeluaran')
@section('page_title', 'Daftar Pengeluaran Operasional')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
    <div>
        <h5 class="fw-bold mb-0">Transaksi Pengeluaran</h5>
        <span class="text-muted small">Catatan semua biaya operasional bisnis Anda</span>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('expense-categories.index') }}" class="btn btn-light border shadow-sm">
            <i class="fas fa-tags me-1"></i> Kelola Kategori
        </a>
        <a href="{{ route('expenses.create') }}" class="btn btn-primary shadow-sm">
            <i class="fas fa-plus me-1"></i> Catat Pengeluaran
        </a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<!-- Filter Section -->
<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('expenses.index') }}" class="row g-3 align-items-end">
            <div class="col-md-2">
                <label class="form-label small text-muted mb-1">Tanggal Mulai</label>
                <input type="date" name="start_date" class="form-control" value="{{ request('start_date', now()->startOfMonth()->format('Y-m-d')) }}">
            </div>
            <div class="col-md-2">
                <label class="form-label small text-muted mb-1">Tanggal Akhir</label>
                <input type="date" name="end_date" class="form-control" value="{{ request('end_date', now()->endOfMonth()->format('Y-m-d')) }}">
            </div>
            <div class="col-md-3">
                <label class="form-label small text-muted mb-1">Kategori</label>
                <select name="category_id" class="form-select">
                    <option value="all">Semua Kategori</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small text-muted mb-1">Status</label>
                <select name="status" class="form-select">
                    <option value="all" {{ request('status') == 'all' ? 'selected' : '' }}>Semua Status</option>
                    <option value="paid" {{ request('status', 'paid') == 'paid' ? 'selected' : '' }}>Lunas (Paid)</option>
                    <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                    <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Dibatalkan</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-filter me-2"></i> Filter
                </button>
            </div>
        </form>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4 bg-primary text-white h-100">
            <div class="card-body p-4 d-flex align-items-center">
                <div class="bg-white bg-opacity-25 p-3 rounded-circle me-3">
                    <i class="fas fa-money-bill-wave fa-2x text-white"></i>
                </div>
                <div>
                    <p class="mb-0 text-white-50 small fw-medium">Total Pengeluaran (Lunas)</p>
                    <h3 class="mb-0 fw-bold">Rp {{ number_format($totalExpenses, 0, ',', '.') }}</h3>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Tanggal</th>
                        <th>Kategori</th>
                        <th>Deskripsi</th>
                        <th>Nominal</th>
                        <th>Metode & Ref</th>
                        <th>Status</th>
                        <th>Keterangan</th>
                        <th class="text-end pe-4">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($expenses as $expense)
                        <tr>
                            <td class="ps-4 text-nowrap">
                                <i class="far fa-calendar-alt text-muted me-2"></i>
                                {{ \Carbon\Carbon::parse($expense->expense_date)->format('d M Y') }}
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border">{{ $expense->category->name ?? '-' }}</span>
                            </td>
                            <td class="fw-medium text-dark">
                                {{ $expense->description }}
                            </td>
                            <td class="fw-bold text-danger">
                                Rp {{ number_format($expense->amount, 0, ',', '.') }}
                            </td>
                            <td>
                                <div class="small">
                                    <span class="fw-medium">{{ ucfirst(str_replace('_', ' ', $expense->payment_method)) }}</span>
                                    @if($expense->reference_number)
                                        <br><span class="text-muted">{{ $expense->reference_number }}</span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                @if($expense->status == 'paid')
                                    <span class="badge bg-success">Lunas</span>
                                @elseif($expense->status == 'draft')
                                    <span class="badge bg-warning text-dark">Draft</span>
                                @else
                                    <span class="badge bg-danger">Batal</span>
                                @endif
                            </td>
                            <td class="text-muted small">
                                {{ Str::limit($expense->notes ?? '-', 30) }}
                                @if($expense->receipt_file)
                                    <br>
                                    <a href="{{ Storage::url($expense->receipt_file) }}" target="_blank" class="text-primary text-decoration-none mt-1 d-inline-block">
                                        <i class="fas fa-paperclip me-1"></i> Bukti
                                    </a>
                                @endif
                            </td>
                            <td class="text-end pe-4">
                                @if($expense->status !== 'cancelled')
                                    <a href="{{ route('expenses.edit', $expense->id) }}" class="btn btn-sm btn-light text-primary border me-1" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form action="{{ route('expenses.destroy', $expense->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin membatalkan pengeluaran ini? Pengeluaran yang dibatalkan tidak akan dihitung dalam Laba Bersih.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-light text-danger border" title="Batalkan">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </form>
                                @else
                                    <span class="text-muted small fst-italic">Dibatalkan</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="fas fa-box-open fa-3x mb-3 text-light"></i>
                                <p class="mb-0">Belum ada catatan pengeluaran pada periode ini.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($expenses->hasPages())
        <div class="card-footer bg-white border-0 py-3">
            {{ $expenses->links() }}
        </div>
    @endif
</div>
@endsection
