@extends('layouts.app')

@section('page_title', 'Analisis Pencairan')

@section('content')
<div class="container-fluid px-4 py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-1">Analisis Pencairan Dana</h4>
            <p class="text-muted mb-0">Lihat ringkasan dana yang dicairkan oleh Marketplace ke saldo/rekening Anda berdasarkan tanggal pencairan.</p>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-4">
            <form action="{{ route('reconciliation.settlement') }}" method="GET" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label text-muted fw-bold small">Pilih Bulan</label>
                    <input type="month" id="filter_month" class="form-control form-control-sm">
                </div>
                <div class="col-md-3">
                    <label class="form-label text-muted fw-bold small">Dari Tanggal</label>
                    <input type="date" id="start_date" name="start_date" class="form-control form-control-sm" value="{{ $startDate }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label text-muted fw-bold small">Sampai Tanggal</label>
                    <input type="date" id="end_date" name="end_date" class="form-control form-control-sm" value="{{ $endDate }}">
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary btn-sm rounded-3 px-4 fw-bold">Terapkan Filter</button>
                    <a href="{{ route('reconciliation.settlement') }}" class="btn btn-light btn-sm border rounded-3 px-4 ms-2">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Data Table -->
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="px-4 py-3 text-muted" style="font-weight: 600;">Tanggal Cair</th>
                            <th class="py-3 text-muted" style="font-weight: 600;">Jml Order</th>
                            <th class="py-3 text-muted text-end" style="font-weight: 600;">Total Harga Jual</th>
                            <th class="py-3 text-muted text-end" style="font-weight: 600;">Total Biaya (Admin/Platform)</th>
                            <th class="px-4 py-3 text-success text-end" style="font-weight: 600;">Dana Cair (Settlement)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($settlements as $item)
                        <tr style="cursor: pointer;" onclick="showDetail('{{ $item->release_date }}')">
                            <td class="px-4 py-3">
                                <span class="fw-bold text-dark">{{ \Carbon\Carbon::parse($item->release_date)->format('d M Y') }}</span>
                            </td>
                            <td class="py-3">
                                <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-3">{{ $item->total_orders }} Order</span>
                            </td>
                            <td class="py-3 text-end fw-medium">
                                Rp {{ number_format($item->total_sales, 0, ',', '.') }}
                            </td>
                            <td class="py-3 text-end text-danger fw-medium">
                                -Rp {{ number_format($item->total_fees, 0, ',', '.') }}
                                <span class="badge bg-danger bg-opacity-10 text-danger ms-1" style="font-size: 0.7em;">
                                    {{ $item->total_sales > 0 ? number_format(($item->total_fees / $item->total_sales) * 100, 1, ',', '.') : 0 }}%
                                </span>
                            </td>
                            <td class="px-4 py-3 text-end">
                                <span class="fw-bold text-success fs-6">Rp {{ number_format($item->total_settlement, 0, ',', '.') }}</span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center py-5">
                                <div class="text-muted">
                                    <i class="fas fa-folder-open fs-1 mb-3 text-light"></i>
                                    <p class="mb-0 fw-medium">Belum ada data pencairan untuk periode ini.</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                    @if($settlements->count() > 0)
                    <tfoot class="bg-light fw-bold">
                        <tr>
                            <td class="px-4 py-3">TOTAL</td>
                            <td class="py-3">{{ $settlements->sum('total_orders') }} Order</td>
                            <td class="py-3 text-end">Rp {{ number_format($settlements->sum('total_sales'), 0, ',', '.') }}</td>
                            <td class="py-3 text-end text-danger">
                                -Rp {{ number_format($settlements->sum('total_fees'), 0, ',', '.') }}
                                <span class="badge bg-danger bg-opacity-10 text-danger ms-1" style="font-size: 0.7em;">
                                    {{ $settlements->sum('total_sales') > 0 ? number_format(($settlements->sum('total_fees') / $settlements->sum('total_sales')) * 100, 1, ',', '.') : 0 }}%
                                </span>
                            </td>
                            <td class="px-4 py-3 text-end text-success fs-5">Rp {{ number_format($settlements->sum('total_settlement'), 0, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Detail -->
<div class="modal fade" id="detailModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-xl">
    <div class="modal-content border-0 shadow" style="border-radius: 16px;">
      <div class="modal-header bg-light border-0 py-3" style="border-radius: 16px 16px 0 0;">
        <h5 class="modal-title fw-bold text-dark"><i class="fas fa-list-alt text-primary me-2"></i> Rincian Pesanan Cair - <span id="modalDateTitle"></span></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-0">
        <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-white sticky-top" style="z-index: 10;">
                    <tr>
                        <th class="px-4 py-3 text-muted" style="font-weight: 600;">No. Pesanan</th>
                        <th class="py-3 text-muted" style="font-weight: 600;">Toko</th>
                        <th class="py-3 text-muted text-end" style="font-weight: 600;">Harga Jual</th>
                        <th class="py-3 text-muted text-end" style="font-weight: 600;">Biaya Admin</th>
                        <th class="px-4 py-3 text-success text-end" style="font-weight: 600;">Pencairan</th>
                    </tr>
                </thead>
                <tbody id="detailTableBody">
                    <!-- Data diload via AJAX -->
                </tbody>
            </table>
        </div>
      </div>
      <div class="modal-footer border-0 bg-light" style="border-radius: 0 0 16px 16px;">
        <button type="button" class="btn btn-secondary rounded-pill fw-bold px-4" data-bs-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
function showDetail(date) {
    document.getElementById('detailTableBody').innerHTML = '<tr><td colspan="5" class="text-center py-4"><i class="fas fa-spinner fa-spin text-primary fs-3"></i></td></tr>';
    
    var myModal = new bootstrap.Modal(document.getElementById('detailModal'));
    myModal.show();
    
    fetch('{{ url("reconciliation/settlement") }}/' + date)
        .then(response => response.json())
        .then(data => {
            document.getElementById('modalDateTitle').innerText = data.date;
            
            let html = '';
            data.data.forEach(item => {
                html += `
                    <tr>
                        <td class="px-4 py-2"><span class="fw-bold">${item.order_id}</span></td>
                        <td class="py-2">
                            <span class="badge bg-secondary bg-opacity-10 text-dark border">${item.marketplace}</span>
                            <span class="small text-muted ms-1">${item.store}</span>
                        </td>
                        <td class="py-2 text-end">Rp ${parseInt(item.product_amount).toLocaleString('id-ID')}</td>
                        <td class="py-2 text-end text-danger">
                            -Rp ${parseInt(item.total_fees).toLocaleString('id-ID')}
                            <span class="badge bg-danger bg-opacity-10 text-danger ms-1" style="font-size: 0.7em;">
                                ${item.product_amount > 0 ? ((item.total_fees / item.product_amount) * 100).toFixed(1).replace('.', ',') : 0}%
                            </span>
                        </td>
                        <td class="px-4 py-2 text-end fw-bold text-success">Rp ${parseInt(item.settlement_amount).toLocaleString('id-ID')}</td>
                    </tr>
                `;
            });
            
            if(data.data.length === 0) {
                html = '<tr><td colspan="5" class="text-center py-4 text-muted">Data tidak ditemukan.</td></tr>';
            }
            
            document.getElementById('detailTableBody').innerHTML = html;
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('detailTableBody').innerHTML = '<tr><td colspan="5" class="text-center py-4 text-danger">Gagal memuat data.</td></tr>';
        });
}
</script>
@endpush
