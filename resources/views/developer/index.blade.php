@extends('layouts.app')
@section('title', 'Developer Control Panel')

@section('content')
<div class="container-fluid">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <h4 class="mb-0 fw-bold text-dark" style="letter-spacing: -0.5px;">Developer Control Panel <i class="fas fa-code text-primary ms-2"></i></h4>
        <a href="{{ route('developer.users.index') }}" class="btn btn-primary btn-sm fw-bold">
            <i class="fas fa-users-cog me-1"></i> User Management
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm" style="border-radius: 12px; background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);">
                <div class="card-body p-4 text-white">
                    <h6 class="fw-bold mb-3"><i class="fas fa-server me-2"></i> System Health & Diagnostics</h6>
                    <div class="row text-center">
                        <div class="col-md-2 col-6 mb-3 mb-md-0">
                            <div class="small text-white-50 text-uppercase fw-bold">Environment</div>
                            <div class="fs-5 fw-bold">{{ Str::ucfirst($systemInfo['environment']) }}</div>
                        </div>
                        <div class="col-md-2 col-6 mb-3 mb-md-0">
                            <div class="small text-white-50 text-uppercase fw-bold">Laravel / PHP</div>
                            <div class="fs-5 fw-bold">v{{ $systemInfo['laravel_version'] }} / {{ $systemInfo['php_version'] }}</div>
                        </div>
                        <div class="col-md-2 col-6 mb-3 mb-md-0">
                            <div class="small text-white-50 text-uppercase fw-bold">Database</div>
                            <div class="fs-5 fw-bold {{ $systemInfo['db_status'] === 'Connected' ? 'text-success' : 'text-danger' }}">
                                <i class="fas {{ $systemInfo['db_status'] === 'Connected' ? 'fa-check-circle' : 'fa-times-circle' }}"></i> {{ $systemInfo['db_status'] }}
                            </div>
                        </div>
                        <div class="col-md-2 col-6 mb-3 mb-md-0">
                            <div class="small text-white-50 text-uppercase fw-bold">Queue / Failed</div>
                            <div class="fs-5 fw-bold">
                                {{ $systemInfo['pending_jobs'] }} / <span class="{{ $systemInfo['failed_jobs'] > 0 ? 'text-warning' : '' }}">{{ $systemInfo['failed_jobs'] }}</span>
                            </div>
                        </div>
                        <div class="col-md-2 col-6">
                            <div class="small text-white-50 text-uppercase fw-bold">Cache / Session</div>
                            <div class="fs-5 fw-bold">{{ Str::ucfirst($systemInfo['cache_driver']) }} / {{ Str::ucfirst($systemInfo['session_driver']) }}</div>
                        </div>
                        <div class="col-md-2 col-6">
                            <div class="small text-white-50 text-uppercase fw-bold">Debug Mode</div>
                            <div class="fs-5 fw-bold {{ $systemInfo['debug_mode'] === 'Enabled' ? 'text-warning' : 'text-success' }}">
                                {{ $systemInfo['debug_mode'] }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Module Management -->
        <div class="col-md-8 mb-4">
            <div class="card border-0 shadow-sm" style="border-radius: 12px;">
                <div class="card-header bg-white border-0 pt-4 pb-3">
                    <h6 class="mb-0 fw-bold text-dark"><i class="fas fa-cubes text-info me-2"></i> Pengaturan Lisensi / Modul</h6>
                </div>
                <div class="card-body pt-0">
                    <div class="accordion" id="accordionCompanies">
                        @foreach($companies as $index => $company)
                            <div class="accordion-item border-0 mb-3 rounded shadow-sm">
                                <h2 class="accordion-header" id="heading{{ $company->id }}">
                                    <button class="accordion-button {{ $index !== 0 ? 'collapsed' : '' }} fw-bold text-dark rounded" type="button" data-bs-toggle="collapse" data-bs-target="#collapse{{ $company->id }}" aria-expanded="{{ $index === 0 ? 'true' : 'false' }}" aria-controls="collapse{{ $company->id }}">
                                        {{ $company->name }} <span class="badge bg-primary ms-3">ID: {{ $company->id }}</span>
                                    </button>
                                </h2>
                                <div id="collapse{{ $company->id }}" class="accordion-collapse collapse {{ $index === 0 ? 'show' : '' }}" aria-labelledby="heading{{ $company->id }}" data-bs-parent="#accordionCompanies">
                                    <div class="accordion-body">
                                        <form action="{{ route('developer.modules.update', $company->id) }}" method="POST">
                                            @csrf
                                            <div class="row">
                                                <div class="col-md-3 mb-3">
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox" role="switch" id="mod_basic_{{ $company->id }}" checked disabled>
                                                        <input type="hidden" name="features[basic]" value="1">
                                                        <label class="form-check-label fw-bold text-dark" for="mod_basic_{{ $company->id }}">
                                                            Modul Basic (Default)
                                                            <i class="fas fa-info-circle text-primary ms-1" style="cursor: pointer;" data-bs-toggle="modal" data-bs-target="#infoBasicModal"></i>
                                                        </label>
                                                        <div class="small text-muted">Master Data, Import Order, Dashboard Sales. <span class="badge bg-secondary bg-opacity-25 text-dark border ms-1" style="font-size: 0.6em">LOCKED</span></div>
                                                    </div>
                                                </div>
                                                <div class="col-md-3 mb-3">
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox" role="switch" id="mod_finance_{{ $company->id }}" name="features[finance]" value="1" {{ $company->hasFeature('finance') ? 'checked' : '' }}>
                                                        <label class="form-check-label fw-bold" for="mod_finance_{{ $company->id }}">
                                                            Modul Finance & Rekonsiliasi
                                                            <i class="fas fa-info-circle text-primary ms-1" style="cursor: pointer;" data-bs-toggle="modal" data-bs-target="#infoFinanceModal"></i>
                                                        </label>
                                                        <div class="small text-muted">Import Income, Rekonsiliasi Dana.</div>
                                                    </div>
                                                </div>
                                                <div class="col-md-3 mb-3">
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox" role="switch" id="mod_profit_{{ $company->id }}" name="features[profit]" value="1" {{ $company->hasFeature('profit') ? 'checked' : '' }}>
                                                        <label class="form-check-label fw-bold" for="mod_profit_{{ $company->id }}">
                                                            Modul HPP & Profit
                                                            <i class="fas fa-info-circle text-primary ms-1" style="cursor: pointer;" data-bs-toggle="modal" data-bs-target="#infoProfitModal"></i>
                                                        </label>
                                                        <div class="small text-muted">HPP Snapshot, Expense, Profitability Dashboard.</div>
                                                    </div>
                                                </div>
                                                <div class="col-md-3 mb-3">
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox" role="switch" id="mod_purchase_{{ $company->id }}" name="features[purchase]" value="1" {{ $company->hasFeature('purchase') ? 'checked' : '' }}>
                                                        <label class="form-check-label fw-bold" for="mod_purchase_{{ $company->id }}">
                                                            Modul Purchase
                                                            <i class="fas fa-info-circle text-primary ms-1" style="cursor: pointer;" data-bs-toggle="modal" data-bs-target="#infoPurchaseModal"></i>
                                                        </label>
                                                        <div class="small text-muted">PO, Goods Receipt, Histori Harga, Pembayaran.</div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="text-end mt-2">
                                                <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-save me-1"></i> Simpan Modul</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <!-- API Integrations -->
        <div class="col-md-4 mb-4">
            <div class="card border-0 shadow-sm" style="border-radius: 12px;">
                <div class="card-header bg-white border-0 pt-4 pb-3">
                    <h6 class="mb-0 fw-bold text-dark"><i class="fas fa-plug text-warning me-2"></i> API Integrations</h6>
                </div>
                <div class="card-body pt-0">
                    <form action="{{ route('developer.api-token.update') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label text-muted small fw-bold">Shopee API Token</label>
                            <input type="text" class="form-control" name="shopee_api_token" value="{{ env('SHOPEE_API_TOKEN') }}" placeholder="Masukkan token API Shopee...">
                            <div class="form-text small">Digunakan untuk integrasi narik data otomatis di masa depan.</div>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-warning fw-bold text-dark"><i class="fas fa-save me-2"></i> Simpan Token</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Factory Reset -->
        <div class="col-md-4 mb-4">
            <div class="card border-0 shadow-sm bg-danger bg-opacity-10" style="border-radius: 12px; border: 1px solid rgba(220, 53, 69, 0.2) !important;">
                <div class="card-header bg-transparent border-0 pt-4 pb-3">
                    <h6 class="mb-0 fw-bold text-danger"><i class="fas fa-exclamation-triangle me-2"></i> DANGER ZONE</h6>
                </div>
                <div class="card-body pt-0">
                    <p class="text-dark small mb-4">
                        <strong>Factory Reset</strong> akan menghapus <b>seluruh data transaksi</b> dan operasional dari sistem, termasuk:
                        <ul class="small text-danger">
                            <li>Data Pesanan (Orders & Items)</li>
                            <li>Data Keuangan (Order Financials)</li>
                            <li>Riwayat HPP & Stok (Stock Movements)</li>
                            <li>Data Customer</li>
                            <li>Master Produk & Warehouse Stocks</li>
                            <li>Data Import Batches</li>
                            <li>Biaya Operasional (Expenses)</li>
                        </ul>
                        Pengaturan dasar seperti User, Company, dan Tema akan tetap dipertahankan. Gunakan fungsi ini hanya saat ingin membersihkan database untuk digunakan oleh klien/tempat lain.
                    </p>
                    
                    <form action="{{ route('developer.factory-reset') }}" method="POST" id="form-reset">
                        @csrf
                        <div class="d-grid">
                            <button type="button" class="btn btn-danger" onclick="confirmReset()"><i class="fas fa-skull-crossbones me-2"></i> RESET DATA PABRIK</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modals for Module Info -->
<!-- Modal Basic -->
<div class="modal fade" id="infoBasicModal" tabindex="-1" aria-labelledby="infoBasicModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content border-0 shadow" style="border-radius: 16px;">
      <div class="modal-header bg-light border-0 py-3" style="border-radius: 16px 16px 0 0;">
        <h5 class="modal-title fw-bold text-dark" id="infoBasicModalLabel"><i class="fas fa-box-open text-primary me-2"></i> Modul Basic (Wajib)</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-4 text-dark">
        <p class="mb-3">Modul tulang punggung (backbone) KulaShop. Difokuskan pada manajemen pesanan, kontrol stok gudang, dan basis data pelanggan.</p>
        <div class="row">
            <div class="col-md-6 mb-3">
                <h6 class="fw-bold"><i class="fas fa-home text-muted me-2"></i> Dashboard Penjualan</h6>
                <ul class="small text-muted mb-0"><li>Grafik Penjualan, Metrik Transaksi, Ranking Customer & Kurir.</li></ul>
            </div>
            <div class="col-md-6 mb-3">
                <h6 class="fw-bold"><i class="fas fa-database text-muted me-2"></i> Master Data</h6>
                <ul class="small text-muted mb-0"><li>Marketplace (Platform & Akun).</li><li>Database Produk & SKU.</li><li>Database Customers.</li></ul>
            </div>
            <div class="col-md-6 mb-3">
                <h6 class="fw-bold"><i class="fas fa-boxes text-muted me-2"></i> Inventory (Stok)</h6>
                <ul class="small text-muted mb-0"><li>Multi Gudang & Lokasi.</li><li>Ringkasan & Stok per Produk.</li><li>Stok Masuk, Penyesuaian, & Mutasi.</li></ul>
            </div>
            <div class="col-md-6 mb-3">
                <h6 class="fw-bold"><i class="fas fa-shopping-cart text-muted me-2"></i> Transaksi & Laporan</h6>
                <ul class="small text-muted mb-0"><li>Import Shopee Order.</li><li>Daftar Transaksi (Orders).</li><li>Laporan Keuangan Dasar (Omzet).</li></ul>
            </div>
        </div>
      </div>
      <div class="modal-footer border-0">
        <button type="button" class="btn btn-secondary rounded-pill fw-bold px-4" data-bs-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal Finance -->
<div class="modal fade" id="infoFinanceModal" tabindex="-1" aria-labelledby="infoFinanceModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content border-0 shadow" style="border-radius: 16px;">
      <div class="modal-header bg-light border-0 py-3" style="border-radius: 16px 16px 0 0;">
        <h5 class="modal-title fw-bold text-dark" id="infoFinanceModalLabel"><i class="fas fa-hand-holding-usd text-info me-2"></i> Modul Finance & Rekonsiliasi</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-4 text-dark">
        <p class="mb-3">Modul untuk memastikan tidak ada kebocoran dana dengan melacak kecocokan pesanan dan pencairan uang dari Marketplace.</p>
        <div class="row">
            <div class="col-md-6 mb-3">
                <h6 class="fw-bold"><i class="fas fa-file-import text-muted me-2"></i> Import Data</h6>
                <ul class="small text-muted mb-0">
                    <li><strong>Shopee Income:</strong> Alat khusus menarik file pencairan dana (Settlement) dari Shopee.</li>
                </ul>
            </div>
            <div class="col-md-6 mb-3">
                <h6 class="fw-bold"><i class="fas fa-check-double text-muted me-2"></i> Rekonsiliasi Marketplace</h6>
                <ul class="small text-muted mb-0">
                    <li>Pencocokan otomatis antara Nomor Pesanan dengan Nomor Pencairan.</li>
                    <li>Mendeteksi pesanan menggantung yang belum dibayar.</li>
                </ul>
            </div>
            <div class="col-md-12">
                <h6 class="fw-bold"><i class="fas fa-bolt text-muted me-2"></i> Dashboard Extension</h6>
                <ul class="small text-muted mb-0">
                    <li>Membuka akses tombol cepat "Rekonsiliasi" pada halaman utama Dashboard.</li>
                </ul>
            </div>
        </div>
      </div>
      <div class="modal-footer border-0">
        <button type="button" class="btn btn-secondary rounded-pill fw-bold px-4" data-bs-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal Profit -->
<div class="modal fade" id="infoProfitModal" tabindex="-1" aria-labelledby="infoProfitModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content border-0 shadow" style="border-radius: 16px;">
      <div class="modal-header bg-light border-0 py-3" style="border-radius: 16px 16px 0 0;">
        <h5 class="modal-title fw-bold text-dark" id="infoProfitModalLabel"><i class="fas fa-chart-pie text-warning me-2"></i> Modul HPP & Profit</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-4 text-dark">
        <p class="mb-3">Modul tingkat lanjut untuk memonitor kesehatan finansial bisnis. Mengubah aplikasi dari pencatat pesanan menjadi mesin penganalisa laba.</p>
        <div class="row">
            <div class="col-md-6 mb-3">
                <h6 class="fw-bold"><i class="fas fa-file-invoice-dollar text-muted me-2"></i> Manajemen Pengeluaran</h6>
                <ul class="small text-muted mb-0">
                    <li><strong>Pengeluaran Bisnis:</strong> Mencatat biaya operasional, gaji, listrik, sewa gudang, iklan, dll.</li>
                </ul>
            </div>
            <div class="col-md-6 mb-3">
                <h6 class="fw-bold"><i class="fas fa-chart-line text-muted me-2"></i> Laporan Keuangan Advance</h6>
                <ul class="small text-muted mb-0">
                    <li><strong>Laporan HPP:</strong> Melacak Harga Pokok Penjualan (COGS) setiap barang.</li>
                    <li><strong>Profit & Margin:</strong> Analisa Laba/Rugi komprehensif.</li>
                </ul>
            </div>
            <div class="col-md-12">
                <h6 class="fw-bold"><i class="fas fa-columns text-muted me-2"></i> Dashboard Extension</h6>
                <ul class="small text-muted mb-0">
                    <li>Membuka "Profit Overview" di Dashboard (Total Income, Total HPP, Gross Profit, Total Expense, Net Margin %).</li>
                </ul>
            </div>
        </div>
      </div>
      <div class="modal-footer border-0">
        <button type="button" class="btn btn-secondary rounded-pill fw-bold px-4" data-bs-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal Purchase -->
<div class="modal fade" id="infoPurchaseModal" tabindex="-1" aria-labelledby="infoPurchaseModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content border-0 shadow" style="border-radius: 16px;">
      <div class="modal-header bg-light border-0 py-3" style="border-radius: 16px 16px 0 0;">
        <h5 class="modal-title fw-bold text-dark" id="infoPurchaseModalLabel"><i class="fas fa-shopping-cart text-success me-2"></i> Modul Purchase</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-4 text-dark">
        <p class="mb-3">Modul terintegrasi untuk menangani pembelian barang dagangan, supplier, dan hutang usaha.</p>
        <div class="row">
            <div class="col-md-6 mb-3">
                <h6 class="fw-bold"><i class="fas fa-file-invoice text-muted me-2"></i> Purchase Order & Penerimaan</h6>
                <ul class="small text-muted mb-0">
                    <li>Buat PO ke Supplier.</li>
                    <li>Goods Receipt: Penambahan stok otomatis berdasar barang datang.</li>
                </ul>
            </div>
            <div class="col-md-6 mb-3">
                <h6 class="fw-bold"><i class="fas fa-money-bill-wave text-muted me-2"></i> Hutang & Pembayaran</h6>
                <ul class="small text-muted mb-0">
                    <li>Pencatatan pembayaran atas PO (Payable).</li>
                    <li>Histori fluktuasi harga modal beli.</li>
                </ul>
            </div>
        </div>
      </div>
      <div class="modal-footer border-0">
        <button type="button" class="btn btn-secondary rounded-pill fw-bold px-4" data-bs-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>

@push('scripts')
<script>
function confirmReset() {
    Swal.fire({
        title: 'Anda Sangat Yakin?',
        text: "Tindakan ini tidak dapat dibatalkan! Seluruh data operasional akan lenyap selamanya.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Ya, Hapus Semua!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Konfirmasi Terakhir',
                text: "Ketik 'RESET' untuk melanjutkan.",
                input: 'text',
                icon: 'error',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: 'Eksekusi',
            }).then((result2) => {
                if (result2.isConfirmed && result2.value === 'RESET') {
                    document.getElementById('form-reset').submit();
                } else if(result2.isConfirmed) {
                    Swal.fire('Gagal', 'Teks konfirmasi salah. Reset dibatalkan.', 'info');
                }
            });
        }
    })
}
</script>
@endpush
@endsection
