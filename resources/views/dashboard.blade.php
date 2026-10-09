@extends('layouts.app')

@section('title', 'Dashboard Analytics')
@section('page_title', 'Dashboard')

@section('content')

<style>
    .summary-card {
        transition: transform 0.2s, box-shadow 0.2s;
        border-radius: 1rem;
        overflow: hidden;
    }
    .summary-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important;
    }
    .summary-icon {
        position: absolute;
        right: -10px;
        bottom: -15px;
        font-size: 5rem;
        opacity: 0.15;
        transform: rotate(-10deg);
        transition: transform 0.3s;
    }
    .summary-card:hover .summary-icon {
        transform: rotate(0) scale(1.1);
    }
    .table-card {
        border-radius: 1rem;
        border: none;
        box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        margin-bottom: 2rem;
    }
    .table-card .card-header {
        background-color: white;
        border-bottom: 1px solid rgba(0,0,0,0.05);
        border-radius: 1rem 1rem 0 0;
        padding: 1.5rem;
    }
</style>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0 fw-bold text-dark">Ringkasan Penjualan</h4>
    
    <form action="{{ route('dashboard') }}" method="GET" class="d-flex align-items-center" id="filterForm">
        <label for="period" class="me-2 fw-bold text-muted small text-uppercase">Filter:</label>
        
        <select name="marketplace_account_id" class="form-select form-select-sm border-0 shadow-sm rounded-pill px-3 py-2 fw-bold me-2" style="width: auto; background-color: white; cursor: pointer;" onchange="document.getElementById('filterForm').submit()">
            <option value="all" {{ (isset($marketplaceAccountId) && $marketplaceAccountId == 'all') ? 'selected' : '' }}>Semua Marketplace</option>
            @foreach($marketplaceAccounts as $account)
            <option value="{{ $account->id }}" {{ (isset($marketplaceAccountId) && $marketplaceAccountId == $account->id) ? 'selected' : '' }}>{{ $account->account_name }} ({{ $account->marketplace_name }})</option>
            @endforeach
        </select>
        
        <select name="order_status" class="form-select form-select-sm border-0 shadow-sm rounded-pill px-3 py-2 fw-bold me-2" style="width: auto; background-color: white; cursor: pointer;" onchange="document.getElementById('filterForm').submit()">
            <option value="all" {{ (isset($orderStatus) && $orderStatus == 'all') ? 'selected' : '' }}>Semua Status</option>
            @foreach($availableStatuses as $statusVal => $statusLabel)
            <option value="{{ $statusVal }}" {{ (isset($orderStatus) && $orderStatus == $statusVal) ? 'selected' : '' }}>{{ $statusLabel }}</option>
            @endforeach
        </select>
        
        <select name="period" id="period" class="form-select form-select-sm border-0 shadow-sm rounded-pill px-3 py-2 fw-bold" style="width: auto; background-color: white; cursor: pointer;" onchange="toggleCustomDates()">
            <option value="today" {{ (isset($period) && $period == 'today') ? 'selected' : '' }}>Hari Ini</option>
            <option value="yesterday" {{ (isset($period) && $period == 'yesterday') ? 'selected' : '' }}>Kemarin</option>
            <option value="7_days" {{ (isset($period) && $period == '7_days') ? 'selected' : '' }}>7 Hari</option>
            <option value="30_days" {{ (isset($period) && $period == '30_days') ? 'selected' : '' }}>30 Hari</option>
            <option value="this_month" {{ (!isset($period) || $period == 'this_month') ? 'selected' : '' }}>Bulan ini</option>
            <option value="last_month" {{ (isset($period) && $period == 'last_month') ? 'selected' : '' }}>Bulan lalu</option>
            <option value="custom" {{ (isset($period) && $period == 'custom') ? 'selected' : '' }}>Custom</option>
        </select>
        
        <div id="customDatesContainer" class="d-flex align-items-center ms-2" style="{{ (isset($period) && $period == 'custom') ? '' : 'display: none !important;' }}">
            <input type="date" name="start_date" id="start_date" class="form-control form-control-sm border-0 shadow-sm rounded-pill px-3 py-2" value="{{ request('start_date') }}">
            <span class="mx-2 text-muted fw-bold">-</span>
            <input type="date" name="end_date" id="end_date" class="form-control form-control-sm border-0 shadow-sm rounded-pill px-3 py-2" value="{{ request('end_date') }}">
            <input type="month" class="form-control form-control-sm border-0 shadow-sm rounded-pill px-3 py-2 ms-2 text-primary fw-bold" style="width: 150px" onchange="setMonth(this.value, 'start_date', 'end_date')" title="Set Bulan Cepat">
            <button type="submit" class="btn btn-sm btn-primary rounded-pill px-3 py-2 ms-2 fw-bold shadow-sm">Filter</button>
        </div>
    </form>
    
    <script>
    function toggleCustomDates() {
        var period = document.getElementById('period').value;
        var customDates = document.getElementById('customDatesContainer');
        var form = document.getElementById('filterForm');
        
        if (period === 'custom') {
            customDates.style.setProperty('display', 'flex', 'important');
        } else {
            customDates.style.setProperty('display', 'none', 'important');
            form.submit();
        }
    }
    
    function setMonth(monthStr, startId, endId) {
        if(!monthStr) return;
        let parts = monthStr.split('-');
        let year = parts[0];
        let month = parts[1];
        
        let startDate = monthStr + '-01';
        let lastDay = new Date(year, month, 0).getDate();
        let endDate = monthStr + '-' + lastDay;
        
        document.getElementById(startId).value = startDate;
        document.getElementById(endId).value = endDate;
    }
    </script>
</div>

    <!-- Quick Access -->
    <div class="card mb-4 border-0 shadow-sm bg-white" style="border-radius: 12px;">
        <div class="card-body p-3">
            <div class="d-flex flex-wrap gap-2 align-items-center">
                <span class="text-muted small fw-bold text-uppercase me-2" style="letter-spacing: 0.5px;"><i class="fas fa-bolt me-1 text-warning"></i> Quick Access:</span>
                <a href="{{ route('import.index') }}" class="btn btn-sm btn-light border-0 text-dark rounded-pill px-3 fw-bold shadow-sm hover-elevate"><i class="fas fa-file-import me-1 text-primary"></i> Import Data</a>
                <a href="{{ route('import.history') }}" class="btn btn-sm btn-light border-0 text-dark rounded-pill px-3 fw-bold shadow-sm hover-elevate"><i class="fas fa-history me-1 text-secondary"></i> Riwayat Import</a>
                @if(!auth()->user()->company || auth()->user()->company->hasFeature('finance') || auth()->user()->isDeveloper())
                <a href="{{ route('reconciliation.shopee.index') }}" class="btn btn-sm btn-light border-0 text-dark rounded-pill px-3 fw-bold shadow-sm hover-elevate"><i class="fas fa-check-double me-1 text-success"></i> Rekonsiliasi</a>
                @endif
                <a href="{{ route('order.index') }}" class="btn btn-sm btn-light border-0 text-dark rounded-pill px-3 fw-bold shadow-sm hover-elevate"><i class="fas fa-shopping-cart me-1 text-info"></i> Orders</a>
                <a href="{{ route('product.index') }}" class="btn btn-sm btn-light border-0 text-dark rounded-pill px-3 fw-bold shadow-sm hover-elevate"><i class="fas fa-box me-1 text-warning"></i> Products</a>
                <a href="{{ route('customers.index') }}" class="btn btn-sm btn-light border-0 text-dark rounded-pill px-3 fw-bold shadow-sm hover-elevate"><i class="fas fa-users me-1 text-danger"></i> Customers</a>
            </div>
        </div>
    </div>

    @if($totalOrders == 0)
    <div class="alert alert-info border-0 shadow-sm rounded-4 d-flex align-items-center p-3 mb-4 bg-white">
        <div class="bg-info bg-opacity-10 text-info rounded-circle p-2 me-3 d-flex align-items-center justify-content-center" style="width:40px; height:40px;">
            <i class="fas fa-info-circle fs-5"></i>
        </div>
        <div>
            <h6 class="mb-1 fw-bold">Belum ada data transaksi pada periode ini.</h6>
            <p class="mb-0 small text-muted">Silakan sesuaikan filter periode atau pilih akun marketplace lainnya.</p>
        </div>
    </div>
    @endif

@if(isset($profitSummary) && (!auth()->user()->company || auth()->user()->company->hasFeature('profit') || auth()->user()->isDeveloper()))
<h4 class="mb-3 fw-bold text-dark border-bottom pb-2">Profit Overview</h4>

@if(isset($profitSummary['incomplete_orders_count']) && $profitSummary['incomplete_orders_count'] > 0)
    <div class="alert alert-warning d-flex align-items-center mb-4" role="alert">
        <i class="fas fa-exclamation-triangle fs-3 me-3 text-warning"></i>
        <div>
            <h6 class="alert-heading fw-bold mb-1">Sebagian transaksi belum memiliki data HPP!</h6>
            <p class="mb-0 small">
                Profit belum dapat dihitung secara lengkap. Ada <strong>{{ number_format($profitSummary['incomplete_orders_count']) }} transaksi</strong> yang membutuhkan HPP.
                <a href="{{ route('report.profit-margin') }}" class="fw-bold ms-1 text-decoration-none">
                    [Lihat Transaksi] <i class="fas fa-arrow-right ms-1"></i>
                </a>
            </p>
        </div>
    </div>
@endif

<div class="row mb-4 g-3">
    <!-- Total Income -->
    <div class="col-md-3">
        <div class="card h-100 border-0 shadow-sm bg-white hover-elevate" style="border-radius: 16px;">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="bg-success bg-opacity-10 text-success rounded-circle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                        <i class="fas fa-hand-holding-usd fs-5"></i>
                    </div>
                    <span class="badge bg-light text-muted border px-2 py-1">Income</span>
                </div>
                <h6 class="text-muted fw-bold mb-1" style="font-size: 0.8rem; letter-spacing: 0.5px;">TOTAL INCOME</h6>
                <h4 class="mb-0 fw-bold text-dark">Rp {{ number_format($profitSummary['total_income'] ?? 0, 0, ',', '.') }}</h4>
            </div>
        </div>
    </div>
    
    <!-- Total HPP -->
    <div class="col-md-3">
        <div class="card h-100 border-0 shadow-sm bg-white hover-elevate" style="border-radius: 16px;">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="bg-secondary bg-opacity-10 text-secondary rounded-circle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                        <i class="fas fa-box fs-5"></i>
                    </div>
                    <span class="badge bg-light text-muted border px-2 py-1">COGS</span>
                </div>
                <h6 class="text-muted fw-bold mb-1" style="font-size: 0.8rem; letter-spacing: 0.5px;">TOTAL HPP</h6>
                <h4 class="mb-0 fw-bold text-dark">Rp {{ number_format($profitSummary['total_hpp'] ?? 0, 0, ',', '.') }}</h4>
            </div>
        </div>
    </div>
    
    <!-- Gross Profit -->
    <div class="col-md-3">
        <div class="card h-100 border-0 shadow-sm bg-white hover-elevate" style="border-radius: 16px;">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                        <i class="fas fa-chart-line fs-5"></i>
                    </div>
                    <span class="badge bg-light text-muted border px-2 py-1">Margin</span>
                </div>
                <h6 class="text-muted fw-bold mb-1" style="font-size: 0.8rem; letter-spacing: 0.5px;">GROSS PROFIT</h6>
                <h4 class="mb-0 fw-bold text-primary">Rp {{ number_format($profitSummary['gross_profit'] ?? 0, 0, ',', '.') }}</h4>
            </div>
        </div>
    </div>

    <!-- Net Profit -->
    <div class="col-md-3">
        <div class="card h-100 border-0 shadow-sm bg-white hover-elevate position-relative overflow-hidden" style="border-radius: 16px;">
            <div class="position-absolute end-0 bottom-0 opacity-10" style="transform: translate(20%, 20%);">
                <i class="fas fa-wallet" style="font-size: 8rem; color: #0d6efd;"></i>
            </div>
            <div class="card-body p-4 position-relative z-1">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                        <i class="fas fa-wallet fs-5"></i>
                    </div>
                    @if(isset($profitSummary['net_margin']))
                        <span class="badge {{ $profitSummary['net_margin'] >= 20 ? 'bg-success bg-opacity-10 text-success' : ($profitSummary['net_margin'] > 0 ? 'bg-warning bg-opacity-10 text-warning' : 'bg-danger bg-opacity-10 text-danger') }} border-0 px-2 py-1 fw-bold">
                            {{ number_format($profitSummary['net_margin'], 2, ',', '.') }}% Margin
                        </span>
                    @endif
                </div>
                <h6 class="text-muted fw-bold mb-1" style="font-size: 0.8rem; letter-spacing: 0.5px;">NET PROFIT</h6>
                <h4 class="mb-0 fw-bold text-dark">Rp {{ number_format($profitSummary['net_profit'] ?? 0, 0, ',', '.') }}</h4>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4 g-3">
    <!-- Operating Expenses -->
    <div class="col-md-4">
        <div class="card border-0 shadow-sm bg-white h-100 hover-elevate" style="border-radius: 12px;">
            <div class="card-body p-3 d-flex align-items-center">
                <div class="bg-danger bg-opacity-10 text-danger rounded p-3 me-3">
                    <i class="fas fa-receipt fs-4"></i>
                </div>
                <div>
                    <h6 class="text-muted fw-bold mb-1" style="font-size: 0.75rem; letter-spacing: 0.5px;">BIAYA OPERASIONAL</h6>
                    <h5 class="mb-0 fw-bold text-dark">Rp {{ number_format($profitSummary['total_operating_expenses'] ?? 0, 0, ',', '.') }}</h5>
                </div>
            </div>
        </div>
    </div>

    <!-- Profit Complete -->
    <div class="col-md-4">
        <div class="card border-0 shadow-sm bg-white h-100 hover-elevate" style="border-radius: 12px;">
            <div class="card-body p-3 d-flex align-items-center">
                <div class="bg-info bg-opacity-10 text-info rounded p-3 me-3">
                    <i class="fas fa-check-double fs-4"></i>
                </div>
                <div>
                    <h6 class="text-muted fw-bold mb-1" style="font-size: 0.75rem; letter-spacing: 0.5px;">PROFIT TERHITUNG</h6>
                    <h5 class="mb-0 fw-bold text-dark">{{ number_format($profitSummary['complete_orders_count'] ?? 0) }} <small class="text-muted fs-6 fw-normal">Pesanan</small></h5>
                </div>
            </div>
        </div>
    </div>

    <!-- Profit Incomplete -->
    <div class="col-md-4">
        <div class="card border-0 shadow-sm bg-white h-100 hover-elevate" style="border-radius: 12px;">
            <div class="card-body p-3 d-flex align-items-center">
                <div class="bg-warning bg-opacity-10 text-warning rounded p-3 me-3">
                    <i class="fas fa-exclamation-triangle fs-4"></i>
                </div>
                <div>
                    <h6 class="text-muted fw-bold mb-1" style="font-size: 0.75rem; letter-spacing: 0.5px;">BELUM DIHITUNG (NO HPP)</h6>
                    <h5 class="mb-0 fw-bold text-dark">{{ number_format($profitSummary['incomplete_orders_count'] ?? 0) }} <small class="text-muted fs-6 fw-normal">Pesanan</small></h5>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

<h4 class="mb-3 fw-bold text-dark border-bottom pb-2 mt-5">Ringkasan Penjualan</h4>

<div class="row mb-4 g-3">
    <!-- Card 1 -->
    <div class="col-md-4">
        <div class="card h-100 border-0 shadow-sm bg-white hover-elevate" style="border-radius: 12px;">
            <div class="card-body p-3 d-flex align-items-center">
                <div class="bg-success bg-opacity-10 text-success rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 48px; height: 48px;">
                    <i class="fas fa-wallet fs-5"></i>
                </div>
                <div>
                    <h6 class="text-muted fw-bold mb-1" style="font-size: 0.75rem; letter-spacing: 0.5px;">TOTAL PEMBAYARAN KOTOR</h6>
                    <h5 class="mb-0 fw-bold text-dark">Rp {{ number_format($totalPayment ?? 0, 0, ',', '.') }}</h5>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Card 2 -->
    <div class="col-md-4">
        <div class="card h-100 border-0 shadow-sm bg-white hover-elevate" style="border-radius: 12px;">
            <div class="card-body p-3 d-flex align-items-center">
                <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 48px; height: 48px;">
                    <i class="fas fa-shopping-bag fs-5"></i>
                </div>
                <div>
                    <h6 class="text-muted fw-bold mb-1" style="font-size: 0.75rem; letter-spacing: 0.5px;">TOTAL ORDER DIPROSES</h6>
                    <h5 class="mb-0 fw-bold text-dark">{{ number_format($totalOrders) }} <small class="text-muted fs-6 fw-normal">Transaksi</small></h5>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Card 3 -->
    <div class="col-md-4">
        <div class="card h-100 border-0 shadow-sm bg-white hover-elevate" style="border-radius: 12px;">
            <div class="card-body p-3 d-flex align-items-center">
                <div class="bg-info bg-opacity-10 text-info rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 48px; height: 48px;">
                    <i class="fas fa-check-circle fs-5"></i>
                </div>
                <div>
                    <h6 class="text-muted fw-bold mb-1" style="font-size: 0.75rem; letter-spacing: 0.5px;">ORDER SELESAI</h6>
                    <h5 class="mb-0 fw-bold text-dark">{{ number_format($completedOrders) }} <small class="text-muted fs-6 fw-normal">Selesai</small></h5>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Card 4 -->
    <div class="col-md-4">
        <div class="card h-100 border-0 shadow-sm bg-white hover-elevate" style="border-radius: 12px;">
            <div class="card-body p-3 d-flex align-items-center">
                <div class="bg-danger bg-opacity-10 text-danger rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 48px; height: 48px;">
                    <i class="fas fa-hand-holding-usd fs-5"></i>
                </div>
                <div>
                    <h6 class="text-muted fw-bold mb-1" style="font-size: 0.75rem; letter-spacing: 0.5px;">INCOME MARKETPLACE</h6>
                    <h5 class="mb-0 fw-bold text-dark">Rp {{ number_format($marketplaceIncome, 0, ',', '.') }}</h5>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Card 5 -->
    <div class="col-md-4">
        <div class="card h-100 border-0 shadow-sm bg-white hover-elevate" style="border-radius: 12px;">
            <div class="card-body p-3 d-flex align-items-center">
                <div class="bg-warning bg-opacity-10 text-warning rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 48px; height: 48px;">
                    <i class="fas fa-tags fs-5"></i>
                </div>
                <div>
                    <h6 class="text-muted fw-bold mb-1" style="font-size: 0.75rem; letter-spacing: 0.5px;">PRODUK TERJUAL</h6>
                    <h5 class="mb-0 fw-bold text-dark">{{ number_format($totalProductsSold) }} <small class="text-muted fs-6 fw-normal">Pcs</small></h5>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Card 6 -->
    <div class="col-md-4">
        <div class="card h-100 border-0 shadow-sm bg-white hover-elevate" style="border-radius: 12px;">
            <div class="card-body p-3 d-flex align-items-center">
                <div class="bg-secondary bg-opacity-10 text-secondary rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 48px; height: 48px;">
                    <i class="fas fa-users fs-5"></i>
                </div>
                <div>
                    <h6 class="text-muted fw-bold mb-1" style="font-size: 0.75rem; letter-spacing: 0.5px;">CUSTOMER AKTIF</h6>
                    <h5 class="mb-0 fw-bold text-dark">{{ number_format($totalCustomers) }} <small class="text-muted fs-6 fw-normal">Customer</small></h5>
                </div>
            </div>
        </div>
    </div>
</div>

<h4 class="mb-3 fw-bold text-dark border-bottom pb-2 mt-5">Informasi Stok & Gudang</h4>
<div class="row mb-4 g-3">
    <!-- Total Stock Units -->
    <div class="col-md-4">
        <div class="card h-100 border-0 shadow-sm bg-white hover-elevate" style="border-radius: 12px;">
            <div class="card-body p-3 d-flex align-items-center">
                <div class="bg-success bg-opacity-10 text-success rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 48px; height: 48px;">
                    <i class="fas fa-boxes fs-5"></i>
                </div>
                <div>
                    <h6 class="text-muted fw-bold mb-1" style="font-size: 0.75rem; letter-spacing: 0.5px;">TOTAL STOCK (ALL)</h6>
                    <h5 class="mb-0 fw-bold text-dark">{{ number_format($totalStockUnits, 0, ',', '.') }} <small class="text-muted fs-6 fw-normal">Pcs</small></h5>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Low Stock -->
    <div class="col-md-4">
        <div class="card h-100 border-0 shadow-sm bg-white hover-elevate" style="border-radius: 12px;">
            <div class="card-body p-3 d-flex align-items-center">
                <div class="bg-warning bg-opacity-10 text-warning rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 48px; height: 48px;">
                    <i class="fas fa-exclamation-triangle fs-5"></i>
                </div>
                <div>
                    <h6 class="text-muted fw-bold mb-1" style="font-size: 0.75rem; letter-spacing: 0.5px;">LOW STOCK (SKU)</h6>
                    <h5 class="mb-0 fw-bold text-dark">{{ number_format($lowStockCount) }} <small class="text-muted fs-6 fw-normal">Varian</small></h5>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Out of Stock -->
    <div class="col-md-4">
        <div class="card h-100 border-0 shadow-sm bg-white hover-elevate" style="border-radius: 12px;">
            <div class="card-body p-3 d-flex align-items-center">
                <div class="bg-danger bg-opacity-10 text-danger rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 48px; height: 48px;">
                    <i class="fas fa-times-circle fs-5"></i>
                </div>
                <div>
                    <h6 class="text-muted fw-bold mb-1" style="font-size: 0.75rem; letter-spacing: 0.5px;">OUT OF STOCK (SKU)</h6>
                    <h5 class="mb-0 fw-bold text-dark">{{ number_format($outOfStockCount) }} <small class="text-muted fs-6 fw-normal">Varian</small></h5>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4 g-3">
    <!-- Sales Trend Chart -->
    <div class="col-md-6 mb-4 mb-md-0">
        <div class="card h-100 border-0 shadow-sm bg-white" style="border-radius: 16px;">
            <div class="card-header bg-transparent border-0 pt-4 pb-0 d-flex align-items-center">
                <div class="bg-success bg-opacity-10 text-success rounded-circle d-flex align-items-center justify-content-center me-3" style="width:40px; height:40px;">
                    <i class="fas fa-chart-line fs-6"></i>
                </div>
                <h6 class="mb-0 fw-bold text-dark" style="letter-spacing: 0.5px;">TREND PENJUALAN HARIAN</h6>
            </div>
            <div class="card-body">
                <div style="height: 300px;">
                    <canvas id="salesTrendChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Net Profit Trend Chart -->
    <div class="col-md-6 mb-4 mb-md-0">
        <div class="card h-100 border-0 shadow-sm bg-white" style="border-radius: 16px;">
            <div class="card-header bg-transparent border-0 pt-4 pb-0 d-flex align-items-center">
                <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center me-3" style="width:40px; height:40px;">
                    <i class="fas fa-chart-area fs-6"></i>
                </div>
                <h6 class="mb-0 fw-bold text-dark" style="letter-spacing: 0.5px;">TREND PROFIT HARIAN</h6>
            </div>
            <div class="card-body">
                @if(auth()->user()->isOwner())
                    <div style="height: 300px;">
                        <canvas id="profitTrendChart"></canvas>
                    </div>
                @else
                    <div class="d-flex align-items-center justify-content-center h-100 text-muted">
                        <div class="text-center">
                            <i class="fas fa-lock fa-3x mb-3 text-light"></i>
                            <p class="mb-0">Data profit hanya untuk Owner.</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="row mb-4 g-3">
    <!-- Sales by Marketplace -->
    <div class="col-md-5 mb-4 mb-md-0">
        <div class="card h-100 border-0 shadow-sm bg-white" style="border-radius: 16px;">
            <div class="card-header bg-transparent border-0 pt-4 pb-3 d-flex align-items-center">
                <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center me-3" style="width:40px; height:40px;">
                    <i class="fas fa-store fs-6"></i>
                </div>
                <h6 class="mb-0 fw-bold text-dark" style="letter-spacing: 0.5px;">PENJUALAN PER TOKO</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 border-top">
                        <thead class="bg-light text-muted" style="font-size: 0.75rem; letter-spacing: 0.5px;">
                            <tr>
                                <th class="px-4 border-0">TOKO</th>
                                <th class="border-0">ORDER</th>
                                <th class="border-0">PENJUALAN</th>
                                <th class="border-0">INCOME</th>
                            </tr>
                        </thead>
                        <tbody class="border-top-0">
                            @foreach($salesByAccount as $account)
                            <tr>
                                <td class="px-4 py-3">
                                    <div class="fw-bold text-dark">{{ $account->account_name }}</div>
                                    <small class="text-muted">{{ $account->marketplace_name }}</small>
                                </td>
                                <td>{{ number_format($account->total_orders) }}</td>
                                <td class="fw-bold">Rp {{ number_format($account->total_omzet, 0, ',', '.') }}</td>
                                <td class="fw-bold text-success">Rp {{ number_format($account->total_income, 0, ',', '.') }}</td>
                            </tr>
                            @endforeach
                            @if($salesByAccount->isEmpty())
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">Tidak ada data transaksi.</td>
                            </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Pesanan Terbaru -->
    <div class="col-md-7">
        <div class="card h-100 border-0 shadow-sm bg-white" style="border-radius: 16px;">
            <div class="card-header bg-transparent border-0 pt-4 pb-3 d-flex align-items-center">
                <div class="bg-success bg-opacity-10 text-success rounded-circle d-flex align-items-center justify-content-center me-3" style="width:40px; height:40px;">
                    <i class="fas fa-shopping-bag fs-6"></i>
                </div>
                <h6 class="mb-0 fw-bold text-dark" style="letter-spacing: 0.5px;">PESANAN TERBARU</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 datatable-nosort border-top">
                        <thead class="bg-light text-muted" style="font-size: 0.75rem; letter-spacing: 0.5px;">
                            <tr>
                                <th class="px-4 border-0">NO PESANAN</th>
                                <th class="border-0">TANGGAL</th>
                                <th class="border-0">CUSTOMER</th>
                                <th class="border-0">MARKETPLACE</th>
                                <th class="border-0">TOTAL</th>
                                <th class="border-0">STATUS</th>
                            </tr>
                        </thead>
                        <tbody class="border-top-0">
                            @foreach($recentOrders as $order)
                            <tr>
                                <td class="px-4 py-3 fw-bold text-primary">{{ $order->external_order_id }}</td>
                                <td>{{ $order->order_date->format('d M Y') }}</td>
                                <td>
                                    <div class="fw-bold text-dark">{{ $order->customer->customer_name ?? '-' }}</div>
                                </td>
                                <td>
                                    @if($order->marketplaceAccount && $order->marketplaceAccount->marketplace)
                                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25">{{ $order->marketplaceAccount->marketplace->name }}</span>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="fw-bold text-success">Rp {{ number_format($order->total_payment, 0, ',', '.') }}</td>
                                <td>
                                    @if($order->order_status == 'COMPLETED')
                                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">Selesai</span>
                                    @elseif($order->order_status == 'CANCELLED')
                                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25">Batal</span>
                                    @else
                                        <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25">{{ $order->order_status }}</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                            @if($recentOrders->isEmpty())
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">Belum ada data pesanan.</td>
                            </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <!-- Best Seller -->
    <div class="col-md-6 mb-4">
        <div class="card h-100 border-0 shadow-sm bg-white" style="border-radius: 16px;">
            <div class="card-header bg-transparent border-0 pt-4 pb-3 d-flex align-items-center">
                <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center me-3" style="width:40px; height:40px;">
                    <i class="fas fa-fire fs-6"></i>
                </div>
                <h6 class="mb-0 fw-bold text-dark" style="letter-spacing: 0.5px;">BEST SELLER PRODUK</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-sm align-middle border-top mb-0">
                        <thead class="bg-light text-muted" style="font-size: 0.75rem; letter-spacing: 0.5px;">
                            <tr>
                                <th class="border-0 px-3">RANK</th>
                                <th class="border-0">PRODUK</th>
                                <th class="border-0">SKU</th>
                                <th class="border-0">STOK</th>
                                <th class="border-0">QTY</th>
                                <th class="border-0">ORDER</th>
                                <th class="border-0">PENJUALAN</th>
                            </tr>
                        </thead>
                        <tbody class="border-top-0">
                            @foreach($bestSellingProducts as $index => $product)
                            <tr>
                                <td class="fw-bold text-center px-3" style="width: 50px;">{{ $index + 1 }}</td>
                                <td class="py-2">
                                    <div class="fw-bold text-dark" style="font-size: 0.85rem;">{{ Str::limit($product->internal_product_name ?? $product->marketplace_product_name, 30) }}</div>
                                    @if($product->internal_product_name)
                                        <small class="text-muted d-block" style="font-size: 0.7rem;">
                                            <i class="fas fa-link"></i> {{ Str::limit($product->marketplace_product_name, 25) }}
                                        </small>
                                    @endif
                                </td>
                                <td><small class="text-muted">{{ $product->marketplace_sku ?: '-' }}</small></td>
                                <td>
                                    @if(is_numeric($product->current_stock))
                                        <span class="badge {{ $product->current_stock <= 0 ? 'bg-danger' : 'bg-success bg-opacity-10 text-success border border-success border-opacity-25' }} rounded-pill px-2 py-1">{{ number_format($product->current_stock) }}</span>
                                    @else
                                        <span class="text-muted fw-bold">-</span>
                                    @endif
                                </td>
                                <td><span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-2 py-1">{{ number_format($product->total_qty) }}</span></td>
                                <td>{{ number_format($product->total_orders) }}</td>
                                <td class="fw-bold text-success">Rp {{ number_format($product->total_omzet, 0, ',', '.') }}</td>
                            </tr>
                            @endforeach
                            @if($bestSellingProducts->isEmpty())
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">Belum ada data produk terjual.</td>
                            </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Customer Terbanyak -->
    <div class="col-md-6 mb-4">
        <div class="card h-100 border-0 shadow-sm bg-white" style="border-radius: 16px;">
            <div class="card-header bg-transparent border-0 pt-4 pb-3 d-flex align-items-center">
                <div class="bg-warning bg-opacity-10 text-warning rounded-circle d-flex align-items-center justify-content-center me-3" style="width:40px; height:40px;">
                    <i class="fas fa-crown fs-6"></i>
                </div>
                <h6 class="mb-0 fw-bold text-dark" style="letter-spacing: 0.5px;">CUSTOMER TERBANYAK TRANSAKSI</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle border-top mb-0">
                        <thead class="bg-light text-muted" style="font-size: 0.75rem; letter-spacing: 0.5px;">
                            <tr>
                                <th class="border-0 px-3">RANK</th>
                                <th class="border-0 px-3">PELANGGAN</th>
                                <th class="border-0">USERNAME</th>
                                <th class="border-0">ORDER</th>
                                <th class="border-0">TOTAL PENJUALAN</th>
                            </tr>
                        </thead>
                        <tbody class="border-top-0">
                            @foreach($topCustomers as $index => $customer)
                            <tr>
                                <td class="fw-bold text-center px-3" style="width: 50px;">{{ $index + 1 }}</td>
                                <td class="px-3 py-2">
                                    <div class="fw-bold text-dark">{{ $customer->customer_name }}</div>
                                </td>
                                <td><small class="text-muted">{{ $customer->username ?: '-' }}</small></td>
                                <td><span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 rounded-pill px-2 py-1">{{ number_format($customer->total_orders) }}</span></td>
                                <td class="fw-bold text-success">Rp {{ number_format($customer->total_belanja, 0, ',', '.') }}</td>
                            </tr>
                            @endforeach
                            @if($topCustomers->isEmpty())
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">Belum ada data pelanggan.</td>
                            </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Kota -->
    <div class="col-md-4 mb-4">
        <div class="card h-100 border-0 shadow-sm bg-white" style="border-radius: 16px;">
            <div class="card-header bg-transparent border-0 pt-4 pb-3 d-flex align-items-center">
                <div class="bg-info bg-opacity-10 text-info rounded-circle d-flex align-items-center justify-content-center me-3" style="width:40px; height:40px;">
                    <i class="fas fa-map-marked-alt fs-6"></i>
                </div>
                <h6 class="mb-0 fw-bold text-dark" style="letter-spacing: 0.5px;">KOTA TERBANYAK</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle border-top mb-0">
                        <thead class="bg-light text-muted" style="font-size: 0.75rem; letter-spacing: 0.5px;">
                            <tr>
                                <th class="border-0 px-3">RANK</th>
                                <th class="border-0 px-3">KOTA</th>
                                <th class="border-0">ORDER</th>
                            </tr>
                        </thead>
                        <tbody class="border-top-0">
                            @foreach($topCities as $index => $city)
                            <tr>
                                <td class="fw-bold text-center px-3" style="width: 50px;">{{ $index + 1 }}</td>
                                <td class="px-3 py-2 fw-bold text-dark">{{ $city->city }}</td>
                                <td><span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 rounded-pill px-2 py-1">{{ number_format($city->total_orders) }}</span></td>
                            </tr>
                            @endforeach
                            @if($topCities->isEmpty())
                            <tr>
                                <td colspan="3" class="text-center text-muted py-4">Belum ada data kota.</td>
                            </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Expedisi -->
    <div class="col-md-4 mb-4">
        <div class="card h-100 border-0 shadow-sm bg-white" style="border-radius: 16px;">
            <div class="card-header bg-transparent border-0 pt-4 pb-3 d-flex align-items-center">
                <div class="bg-success bg-opacity-10 text-success rounded-circle d-flex align-items-center justify-content-center me-3" style="width:40px; height:40px;">
                    <i class="fas fa-shipping-fast fs-6"></i>
                </div>
                <h6 class="mb-0 fw-bold text-dark" style="letter-spacing: 0.5px;">EXPEDISI TERBANYAK</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle border-top mb-0">
                        <thead class="bg-light text-muted" style="font-size: 0.75rem; letter-spacing: 0.5px;">
                            <tr>
                                <th class="border-0 px-3">RANK</th>
                                <th class="border-0 px-3">EXPEDISI</th>
                                <th class="border-0">ORDER</th>
                            </tr>
                        </thead>
                        <tbody class="border-top-0">
                            @foreach($topCouriers as $index => $courier)
                            <tr>
                                <td class="fw-bold text-center px-3" style="width: 50px;">{{ $index + 1 }}</td>
                                <td class="px-3 py-2 fw-bold text-dark">{{ $courier->shipping_provider }}</td>
                                <td><span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 rounded-pill px-2 py-1">{{ number_format($courier->total_orders) }}</span></td>
                            </tr>
                            @endforeach
                            @if($topCouriers->isEmpty())
                            <tr>
                                <td colspan="3" class="text-center text-muted py-4">Belum ada data expedisi.</td>
                            </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Produk Stok Rendah -->
    <div class="col-md-4 mb-4">
        <div class="card h-100 border-0 shadow-sm bg-white" style="border-radius: 16px;">
            <div class="card-header bg-transparent border-0 pt-4 pb-3 d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center">
                    <div class="bg-danger bg-opacity-10 text-danger rounded-circle d-flex align-items-center justify-content-center me-3" style="width:40px; height:40px;">
                        <i class="fas fa-exclamation-triangle fs-6"></i>
                    </div>
                    <h6 class="mb-0 fw-bold text-dark" style="letter-spacing: 0.5px;">PERINGATAN STOK RENDAH</h6>
                </div>
                <a href="{{ route('product.index') }}" class="btn btn-sm btn-light border-0 text-dark shadow-sm hover-elevate rounded-pill px-3 fw-bold">Lihat Semua</a>
            </div>
            <div class="card-body p-0">
                <div class="row g-0 border-bottom text-center bg-light border-top">
                    <div class="col-3 p-3 border-end">
                        <div class="text-muted small fw-bold text-uppercase" style="font-size: 0.70rem; letter-spacing: 0.5px;">TOTAL</div>
                        <div class="fs-5 fw-bold text-dark">{{ number_format($totalProducts) }}</div>
                    </div>
                    <div class="col-3 p-3 border-end">
                        <div class="text-muted small fw-bold text-uppercase" style="font-size: 0.70rem; letter-spacing: 0.5px;">AKTIF</div>
                        <div class="fs-5 fw-bold text-success">{{ number_format($activeProducts) }}</div>
                    </div>
                    <div class="col-3 p-3 border-end">
                        <div class="text-muted small fw-bold text-uppercase" style="font-size: 0.70rem; letter-spacing: 0.5px;">RENDAH</div>
                        <div class="fs-5 fw-bold text-warning">{{ number_format($lowStockCount) }}</div>
                    </div>
                    <div class="col-3 p-3">
                        <div class="text-muted small fw-bold text-uppercase" style="font-size: 0.70rem; letter-spacing: 0.5px;">HABIS</div>
                        <div class="fs-5 fw-bold text-danger">{{ number_format($outOfStockCount) }}</div>
                    </div>
                </div>
                <div class="table-responsive p-3 pt-0 mt-3">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light text-muted" style="font-size: 0.75rem; letter-spacing: 0.5px;">
                            <tr>
                                <th class="border-0 px-3">PRODUK</th>
                                <th class="border-0">STOCK</th>
                                <th class="border-0">MIN. STOCK</th>
                                <th class="border-0">STATUS</th>
                            </tr>
                        </thead>
                        <tbody class="border-top-0">
                            @foreach($lowStockProducts as $product)
                            <tr>
                                <td class="px-3 py-2">
                                    <div class="fw-bold text-dark">{{ $product->name }}</div>
                                    <small class="text-muted">{{ $product->sku }}</small>
                                </td>
                                <td><span class="fw-bold {{ $product->stock <= 0 ? 'text-danger' : 'text-warning' }}">{{ number_format($product->stock) }}</span></td>
                                <td>{{ number_format($product->minimum_stock) }}</td>
                                <td>
                                    @if($product->stock <= 0)
                                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25">HABIS</span>
                                    @else
                                        <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25">RENDAH</span>
                                    @endif
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

<!-- Modal SOP / Getting Started -->
<div class="modal fade" id="sopOnboardingModal" tabindex="-1" aria-labelledby="sopOnboardingModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-header bg-primary text-white border-0" style="border-radius: 16px 16px 0 0;">
                <h5 class="modal-title fw-bold" id="sopOnboardingModalLabel">
                    <i class="fas fa-rocket me-2"></i> Selamat Datang di Kulashop ERP!
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="alert alert-info border-0 bg-info bg-opacity-10 text-info mb-4" style="border-radius: 12px;">
                    <strong>Panduan Pengguna Baru:</strong> Ikuti langkah-langkah di bawah ini untuk memulai menggunakan sistem secara optimal.
                </div>
                
                <h6 class="fw-bold text-dark"><span class="badge bg-primary me-2">1</span> Konfigurasi Identitas Perusahaan</h6>
                <p class="text-muted small mb-3">Masuk ke menu <strong>Pengaturan &gt; Profil Perusahaan</strong>. Upload Logo dan Background Login agar sistem ini mencerminkan brand toko Anda.</p>
                
                <h6 class="fw-bold text-dark"><span class="badge bg-primary me-2">2</span> Setup Master Akun Toko</h6>
                <p class="text-muted small mb-3">Masuk ke <strong>Master Data &gt; Akun Toko</strong>. Tambahkan daftar nama toko online Anda (contoh: Kulashop Official).</p>

                <h6 class="fw-bold text-dark"><span class="badge bg-primary me-2">3</span> Tarik Transaksi (Import Order)</h6>
                <p class="text-muted small mb-3">Buka menu <strong>Import Data &gt; Import Order Shopee</strong>. Unggah file excel Pesanan Selesai dari Shopee Seller Centre. Sistem otomatis akan mendaftarkan seluruh Pelanggan baru!</p>

                <h6 class="fw-bold text-dark"><span class="badge bg-danger me-2">4</span> Sinkronisasi Produk & Input Modal (PENTING)</h6>
                <p class="text-muted small mb-3">Buka <strong>Master Data &gt; Produk</strong>, klik tombol kuning <strong>SKU Belum Mapping</strong> lalu tekan <strong>Sinkronisasi Otomatis</strong>. Setelah itu, Edit produk tersebut dan isi angka HPP (Modal) agar Laba Kotor bisa terhitung!</p>

                <h6 class="fw-bold text-dark"><span class="badge bg-primary me-2">5</span> Rekam Dana Cair (Import Income)</h6>
                <p class="text-muted small mb-3">Buka <strong>Import Data &gt; Import Penghasilan</strong>. Unggah file Penghasilan Shopee untuk melihat uang yang masuk ke rekening secara riil dan melihat jika ada penahanan dana.</p>
                
                <h6 class="fw-bold text-dark"><span class="badge bg-primary me-2">6</span> Catat Pengeluaran (Expenses)</h6>
                <p class="text-muted small mb-3">Masuk ke <strong>Keuangan &gt; Pengeluaran Bisnis</strong> dan catat semua biaya seperti iklan, lakban, dan gaji agar dipotongkan ke laba.</p>
                
                <div class="mt-4 p-3 bg-light rounded text-center">
                    <p class="mb-0 fw-bold text-dark">Selesai! Anda siap mengecek Laba Bersih di Dashboard.</p>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-primary rounded-pill px-4 fw-bold" data-bs-dismiss="modal">Saya Mengerti, Tutup</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Tampilkan SOP Modal hanya jika belum pernah dilihat (disimpan di localStorage)
        if (!localStorage.getItem('sop_modal_shown')) {
            var sopModal = new bootstrap.Modal(document.getElementById('sopOnboardingModal'));
            sopModal.show();
            // Simpan state agar tidak muncul saat reload
            localStorage.setItem('sop_modal_shown', 'true');
        }
    });

    // 1. Inisialisasi Chart secara mandiri (tidak bergantung pada DataTables)
    document.addEventListener('DOMContentLoaded', function() {
        const ctxSales = document.getElementById('salesTrendChart');
        if (ctxSales && typeof Chart !== 'undefined') {
            try {
                new Chart(ctxSales.getContext('2d'), {
                    type: 'line',
                    data: {
                        labels: {!! json_encode($trendDates ?? []) !!},
                        datasets: [{
                            label: 'Total Penjualan (Rp)',
                            data: {!! json_encode($trendOmzet ?? []) !!},
                            borderColor: '#198754',
                            backgroundColor: 'rgba(25, 135, 84, 0.1)',
                            borderWidth: 3,
                            pointBackgroundColor: '#198754',
                            pointBorderColor: '#fff',
                            pointHoverBackgroundColor: '#fff',
                            pointHoverBorderColor: '#198754',
                            fill: true,
                            tension: 0.3
                        },
                        {
                            label: 'Jumlah Order',
                            data: {!! json_encode($trendOrders ?? []) !!},
                            borderColor: '#0dcaf0',
                            backgroundColor: 'transparent',
                            borderWidth: 2,
                            pointBackgroundColor: '#0dcaf0',
                            pointBorderColor: '#fff',
                            pointHoverBackgroundColor: '#fff',
                            pointHoverBorderColor: '#0dcaf0',
                            fill: false,
                            borderDash: [5, 5],
                            tension: 0.3,
                            yAxisID: 'y1'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                type: 'linear',
                                display: true,
                                position: 'left',
                                beginAtZero: true
                            },
                            y1: {
                                type: 'linear',
                                display: true,
                                position: 'right',
                                beginAtZero: true,
                                grid: {
                                    drawOnChartArea: false,
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                display: true,
                                position: 'top'
                            }
                        },
                        interaction: {
                            intersect: false,
                            mode: 'index',
                        },
                    }
                });
            } catch (e) {
                console.error('Error inisialisasi chart:', e);
                ctxSales.parentElement.innerHTML = '<div class="alert alert-warning">Gagal memuat grafik trend penjualan: ' + e.message + '</div>';
            }
        }

        @if(auth()->user()->isOwner())
            const ctxProfit = document.getElementById('profitTrendChart');
            if (ctxProfit && typeof Chart !== 'undefined') {
                try {
                    new Chart(ctxProfit.getContext('2d'), {
                        type: 'line',
                        data: {
                            labels: {!! json_encode($trendProfitDates ?? []) !!},
                            datasets: [{
                                label: 'Gross Profit (Rp)',
                                data: {!! json_encode($trendGrossProfit ?? []) !!},
                                borderColor: '#fd7e14',
                                backgroundColor: 'transparent',
                                borderWidth: 2,
                                pointBackgroundColor: '#fd7e14',
                                pointBorderColor: '#fff',
                                pointHoverBackgroundColor: '#fff',
                                pointHoverBorderColor: '#fd7e14',
                                fill: false,
                                tension: 0.3
                            },
                            {
                                label: 'Net Profit (Rp)',
                                data: {!! json_encode($trendNetProfit ?? []) !!},
                                borderColor: '#0d6efd',
                                backgroundColor: 'rgba(13, 110, 253, 0.1)',
                                borderWidth: 3,
                                pointBackgroundColor: '#0d6efd',
                                pointBorderColor: '#fff',
                                pointHoverBackgroundColor: '#fff',
                                pointHoverBorderColor: '#0d6efd',
                                fill: true,
                                tension: 0.3
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            scales: {
                                y: {
                                    type: 'linear',
                                    display: true,
                                    position: 'left',
                                    beginAtZero: true
                                }
                            },
                            plugins: {
                                legend: {
                                    position: 'top',
                                    labels: {
                                        usePointStyle: true,
                                        boxWidth: 8
                                    }
                                },
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            let label = context.dataset.label || '';
                                            if (label) {
                                                label += ': ';
                                            }
                                            if (context.parsed.y !== null) {
                                                label += new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(context.parsed.y);
                                            }
                                            return label;
                                        }
                                    }
                                }
                            },
                            interaction: {
                                mode: 'index',
                                intersect: false,
                            },
                        }
                    });
                } catch (e) {
                    console.error('Error inisialisasi profit chart:', e);
                    ctxProfit.parentElement.innerHTML = '<div class="alert alert-warning">Gagal memuat grafik trend profit: ' + e.message + '</div>';
                }
            }
        @endif
    });

    // 2. Inisialisasi DataTables secara aman
    $(document).ready(function() {
        try {
            if (typeof $.fn.DataTable !== 'undefined') {
                // Nonaktifkan alert error datatables dan ubah menjadi console warning
                $.fn.dataTable.ext.errMode = 'none';
                
                $('.datatable').DataTable({
                    "order": [],
                    "language": {
                        "search": "Cari:",
                        "lengthMenu": "Tampilkan _MENU_ data",
                        "info": "Menampilkan _START_ s/d _END_ dari _TOTAL_ data",
                        "infoEmpty": "Menampilkan 0 s/d 0 dari 0 data",
                        "infoFiltered": "(disaring dari _MAX_ total data)",
                        "paginate": {
                            "first": "Pertama",
                            "last": "Terakhir",
                            "next": "Selanjutnya",
                            "previous": "Sebelumnya"
                        }
                    }
                });
                
                $('.datatable-nosort').DataTable({
                    "order": [],
                    "bSort": false,
                    "language": {
                        "search": "Cari:",
                        "lengthMenu": "Tampilkan _MENU_ data",
                        "info": "Menampilkan _START_ s/d _END_ dari _TOTAL_ data",
                        "infoEmpty": "Menampilkan 0 s/d 0 dari 0 data",
                        "infoFiltered": "(disaring dari _MAX_ total data)",
                        "paginate": {
                            "first": "Pertama",
                            "last": "Terakhir",
                            "next": "Selanjutnya",
                            "previous": "Sebelumnya"
                        }
                    }
                });
            }
        } catch (err) {
            console.warn("DataTables notice:", err);
        }
    });
</script>
@endpush
