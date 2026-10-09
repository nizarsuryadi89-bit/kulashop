<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Kulashop')</title>
    @if(auth()->check() && auth()->user()->company && auth()->user()->company->logo)
        <link rel="icon" href="{{ asset('storage/' . auth()->user()->company->logo) }}">
    @else
        <link rel="icon" href="{{ asset('img/logo-kulashop.jpg') }}">
    @endif
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- DataTables Bootstrap 5 CSS -->
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
    <!-- Custom Admin Theme -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300..700&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&family=Poppins:wght@300;400;500;600;700&family=Open+Sans:wght@300;400;600;700&family=Montserrat:wght@300;400;500;600;700&family=Lora:ital,wght@0,400;0,500;0,600;1,400&display=swap" rel="stylesheet">
<link href="{{ asset('css/admin-theme.css') }}?v=1.1" rel="stylesheet">
    
    @php
        $customFont = null;
        $customSidebar = null;
        $customSidebarText = null;
        if (auth()->check() && auth()->user()->company) {
            $customFont = auth()->user()->company->font_family;
            $customSidebar = auth()->user()->company->sidebar_theme;
            $customSidebarText = auth()->user()->company->sidebar_text_theme;
        }
    @endphp

    <style>
        @if($customFont)
        body, html {
            font-family: {!! $customFont !!} !important;
        }
        @endif

        @if($customSidebar)
        .sidebar {
            background-color: {{ $customSidebar }} !important;
        }
        @endif

        @if($customSidebarText)
        .sidebar, .sidebar a, .sidebar .nav-link, .sidebar h4, .sidebar span, .sidebar i {
            color: {{ $customSidebarText }} !important;
        }
        .sidebar .text-white, .sidebar .text-white-50 {
            color: {{ $customSidebarText }} !important;
        }
        .sidebar .nav-link.active {
            background-color: rgba(0,0,0,0.1) !important; 
            border-left: 3px solid {{ $customSidebarText }} !important;
        }
        @endif

        .wrapper {
            display: flex;
            flex: 1;
        }

        .content {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow-y: auto;
            height: 100vh;
            background-color: var(--bg-color);
        }

        main {
            padding: 24px;
            flex: 1;
        }

        .sidebar {
            width: 260px;
            min-width: 260px;
            flex-shrink: 0;
            height: 100vh;
            overflow-y: auto;
            overflow-x: hidden;
            display: flex;
            flex-direction: column;
        }
        
        /* Custom Scrollbar for Sidebar */
        .sidebar::-webkit-scrollbar {
            width: 6px;
        }
        .sidebar::-webkit-scrollbar-track {
            background: transparent;
        }
        .sidebar::-webkit-scrollbar-thumb {
            background: rgba(150, 150, 150, 0.3);
            border-radius: 10px;
        }
        .sidebar:hover::-webkit-scrollbar-thumb {
            background: rgba(150, 150, 150, 0.5);
        }

        .sidebar .nav-item {
            margin-bottom: 2px;
        }
        .sidebar .nav-link {
            font-size: 0.8rem !important;
            padding: 0.5rem 1.25rem !important;
            font-weight: 500 !important;
        }
        .sidebar .nav-item small.text-uppercase {
            font-size: 0.65rem !important;
            letter-spacing: 0.5px !important;
            opacity: 0.7 !important;
        }
        .sidebar .nav-link i {
            font-size: 0.85rem !important;
            width: 20px !important;
            text-align: center !important;
        }
    </style>
    @stack('styles')
</head>

<body class="d-flex flex-column" style="min-height: 100vh; overflow: hidden;">

    <div class="wrapper">
        <div class="sidebar-overlay" id="sidebarOverlay"></div>
        <!-- Sidebar -->
        <nav class="sidebar flex-column">
            <div class="p-3 text-center border-bottom border-secondary">
                @if(auth()->check() && auth()->user()->company && auth()->user()->company->logo)
                    <img src="{{ asset('storage/' . auth()->user()->company->logo) }}" alt="Logo" style="max-height: 40px; max-width: 100%;" class="bg-white p-1 rounded">
                @else
                    <h4 class="text-white m-0">{{ auth()->check() && auth()->user()->company ? auth()->user()->company->name : 'Kulashop' }}</h4>
                @endif
            </div>
            <ul class="nav flex-column mt-3">
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}"
                        href="{{ route('dashboard') }}"><i class="fas fa-home me-2"></i> Dashboard</a>
                </li>
                @if(auth()->check() && auth()->user()->isDeveloper())
                <li class="nav-item">
                    <a class="nav-link text-primary fw-bold {{ request()->routeIs('developer.index') ? 'active' : '' }}"
                        href="{{ route('developer.index') }}"><i class="fas fa-code me-2"></i> Developer Panel</a>
                </li>
                @endif
                <li class="nav-item mt-3 mb-1 px-3 text-muted">
                    <small class="text-uppercase fw-bold">Master Data</small>
                </li>
                
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('marketplaces.*') || request()->routeIs('marketplace-accounts.*') ? 'active' : '' }}" data-bs-toggle="collapse" href="#marketplaceCollapse" role="button" aria-expanded="{{ request()->routeIs('marketplaces.*') || request()->routeIs('marketplace-accounts.*') ? 'true' : 'false' }}" aria-controls="marketplaceCollapse">
                        <i class="fas fa-store me-2"></i> Marketplace
                        <i class="fas fa-chevron-down ms-auto" style="font-size: 0.8rem; float: right; margin-top: 5px;"></i>
                    </a>
                    <div class="collapse {{ request()->routeIs('marketplaces.*') || request()->routeIs('marketplace-accounts.*') ? 'show' : '' }}" id="marketplaceCollapse">
                        <ul class="nav flex-column ms-3">
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('marketplaces.index') || request()->routeIs('marketplaces.show') ? 'active fw-bold' : '' }}" href="{{ route('marketplaces.index') }}" style="font-size: 0.9rem;">
                                    <i class="fas fa-circle ms-1 me-2" style="font-size: 0.4rem;"></i> Platform Marketplace
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('marketplace-accounts.*') ? 'active fw-bold' : '' }}" href="{{ route('marketplace-accounts.index') }}" style="font-size: 0.9rem;">
                                    <i class="fas fa-circle ms-1 me-2" style="font-size: 0.4rem;"></i> Akun / Toko
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>

                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('product.*') ? 'active' : '' }}"
                        href="{{ route('product.index') }}"><i class="fas fa-box me-2"></i> Produk</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('customers.*') ? 'active' : '' }}"
                        href="{{ route('customers.index') }}"><i class="fas fa-users me-2"></i> Customers</a>
                </li>

                <!-- Inventory -->
                <li class="nav-item mt-3 mb-1 px-3 text-muted">
                    <small class="text-uppercase fw-bold">Inventory</small>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('warehouses.*') || request()->routeIs('inventory.*') ? 'active' : '' }}" data-bs-toggle="collapse" href="#inventoryCollapse" role="button" aria-expanded="{{ request()->routeIs('warehouses.*') || request()->routeIs('inventory.*') ? 'true' : 'false' }}" aria-controls="inventoryCollapse">
                        <i class="fas fa-boxes me-2"></i> Inventory
                        <i class="fas fa-chevron-down ms-auto" style="font-size: 0.8rem; float: right; margin-top: 5px;"></i>
                    </a>
                    <div class="collapse {{ request()->routeIs('warehouses.*') || request()->routeIs('inventory.*') ? 'show' : '' }}" id="inventoryCollapse">
                        <ul class="nav flex-column ms-3">
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('warehouses.*') ? 'active fw-bold' : '' }}" href="{{ route('warehouses.index') }}" style="font-size: 0.9rem;">
                                    <i class="fas fa-circle ms-1 me-2" style="font-size: 0.4rem;"></i> Gudang
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('inventory.stock-summary') ? 'active fw-bold' : '' }}" href="{{ route('inventory.stock-summary') }}" style="font-size: 0.9rem;">
                                    <i class="fas fa-circle ms-1 me-2" style="font-size: 0.4rem;"></i> Ringkasan Stok
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('inventory.index') || request()->routeIs('inventory.low-stock') || request()->routeIs('inventory.stock-card') ? 'active fw-bold' : '' }}" href="{{ route('inventory.index') }}" style="font-size: 0.9rem;">
                                    <i class="fas fa-circle ms-1 me-2" style="font-size: 0.4rem;"></i> Stok per Produk
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('inventory.stock-in') || request()->routeIs('inventory.stock-out') ? 'active fw-bold' : '' }}" href="{{ route('inventory.stock-in') }}" style="font-size: 0.9rem;">
                                    <i class="fas fa-circle ms-1 me-2" style="font-size: 0.4rem;"></i> Stok Masuk
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('inventory.adjustment') ? 'active fw-bold' : '' }}" href="{{ route('inventory.adjustment') }}" style="font-size: 0.9rem;">
                                    <i class="fas fa-circle ms-1 me-2" style="font-size: 0.4rem;"></i> Penyesuaian Stok
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('inventory.stock-movements') ? 'active fw-bold' : '' }}" href="{{ route('inventory.stock-movements') }}" style="font-size: 0.9rem;">
                                    <i class="fas fa-circle ms-1 me-2" style="font-size: 0.4rem;"></i> Mutasi Stok
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('inventory.valuation') ? 'active fw-bold' : '' }}" href="{{ route('inventory.valuation') }}" style="font-size: 0.9rem;">
                                    <i class="fas fa-circle ms-1 me-2" style="font-size: 0.4rem;"></i> Nilai Persediaan
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('inventory.costing.report') ? 'active fw-bold' : '' }}" href="{{ route('inventory.costing.report') }}" style="font-size: 0.9rem;">
                                    <i class="fas fa-circle ms-1 me-2" style="font-size: 0.4rem;"></i> Laporan HPP
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('inventory.costing.reconciliation') ? 'active fw-bold' : '' }}" href="{{ route('inventory.costing.reconciliation') }}" style="font-size: 0.9rem;">
                                    <i class="fas fa-circle ms-1 me-2" style="font-size: 0.4rem;"></i> Cost Reconciliation
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>

                @if(!auth()->user()->company || auth()->user()->company->hasFeature('purchase') || auth()->user()->isDeveloper())
                <!-- Purchase / Pembelian -->
                <li class="nav-item mt-3 mb-1 px-3 text-muted">
                    <small class="text-uppercase fw-bold">Pembelian</small>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('purchase.*') ? 'active' : '' }}" data-bs-toggle="collapse" href="#purchaseCollapse" role="button" aria-expanded="{{ request()->routeIs('purchase.*') ? 'true' : 'false' }}" aria-controls="purchaseCollapse">
                        <i class="fas fa-shopping-cart me-2"></i> Purchase
                        <i class="fas fa-chevron-down ms-auto" style="font-size: 0.8rem; float: right; margin-top: 5px;"></i>
                    </a>
                    <div class="collapse {{ request()->routeIs('purchase.*') ? 'show' : '' }}" id="purchaseCollapse">
                        <ul class="nav flex-column ms-3">
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('purchase.suppliers.*') ? 'active fw-bold' : '' }}" href="{{ route('purchase.suppliers.index') }}" style="font-size: 0.9rem;">
                                    <i class="fas fa-circle ms-1 me-2" style="font-size: 0.4rem;"></i> Master Supplier
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('purchase.orders.*') ? 'active fw-bold' : '' }}" href="{{ route('purchase.orders.index') }}" style="font-size: 0.9rem;">
                                    <i class="fas fa-circle ms-1 me-2" style="font-size: 0.4rem;"></i> Purchase Order (PO)
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('purchase.receipts.*') ? 'active fw-bold' : '' }}" href="{{ route('purchase.receipts.index') }}" style="font-size: 0.9rem;">
                                    <i class="fas fa-circle ms-1 me-2" style="font-size: 0.4rem;"></i> Goods Receipt (GR)
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('purchase.payments.*') ? 'active fw-bold' : '' }}" href="{{ route('purchase.payments.index') }}" style="font-size: 0.9rem;">
                                    <i class="fas fa-circle ms-1 me-2" style="font-size: 0.4rem;"></i> Pembayaran PO
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('purchase.history') ? 'active fw-bold' : '' }}" href="{{ route('purchase.history') }}" style="font-size: 0.9rem;">
                                    <i class="fas fa-circle ms-1 me-2" style="font-size: 0.4rem;"></i> Histori Harga Beli
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('purchase.report') ? 'active fw-bold' : '' }}" href="{{ route('purchase.report') }}" style="font-size: 0.9rem;">
                                    <i class="fas fa-circle ms-1 me-2" style="font-size: 0.4rem;"></i> Laporan Pembelian
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>
                @endif
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('import.*') ? 'active' : '' }}" data-bs-toggle="collapse" href="#importCollapse" role="button" aria-expanded="{{ request()->routeIs('import.*') ? 'true' : 'false' }}" aria-controls="importCollapse">
                        <i class="fas fa-upload me-2"></i> Import Data
                        <i class="fas fa-chevron-down ms-auto" style="font-size: 0.8rem; float: right; margin-top: 5px;"></i>
                    </a>
                    <div class="collapse {{ request()->routeIs('import.*') ? 'show' : '' }}" id="importCollapse">
                        <ul class="nav flex-column ms-3">
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('import.index') ? 'active fw-bold' : '' }}" href="{{ route('import.index') }}" style="font-size: 0.9rem;">
                                    <i class="fas fa-circle ms-1 me-2" style="font-size: 0.4rem;"></i> Import Status
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('import.shopee.*') ? 'active fw-bold' : '' }}" href="{{ route('import.shopee.index') }}" style="font-size: 0.9rem;">
                                    <i class="fas fa-circle ms-1 me-2" style="font-size: 0.4rem;"></i> Shopee Order
                                </a>
                            </li>
                            @if(!auth()->user()->company || auth()->user()->company->hasFeature('finance') || auth()->user()->isDeveloper())
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('import.shopee_income.*') ? 'active fw-bold' : '' }}" href="{{ route('import.shopee_income.index') }}" style="font-size: 0.9rem;">
                                    <i class="fas fa-circle ms-1 me-2" style="font-size: 0.4rem;"></i> Shopee Income
                                </a>
                            </li>
                            @endif
                        </ul>
                    </div>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('order.*') ? 'active' : '' }}" href="{{ route('order.index') }}"><i class="fas fa-shopping-cart me-2"></i> Transaksi / Orders</a>
                </li>
                @if(auth()->check() && auth()->user()->isOwner())
                    @if(!auth()->user()->company || auth()->user()->company->hasFeature('profit') || auth()->user()->isDeveloper())
                    <li class="nav-item mt-3 mb-1 px-3 text-muted">
                        <small class="text-uppercase fw-bold">Keuangan</small>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('expenses.*') || request()->routeIs('expense-categories.*') ? 'active' : '' }}" href="{{ route('expenses.index') }}"><i class="fas fa-file-invoice-dollar me-2"></i> Pengeluaran Bisnis</a>
                    </li>
                    @endif
                    <li class="nav-item mt-3 mb-1 px-3 text-muted">
                        <small class="text-uppercase fw-bold">Laporan</small>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('report.*') || request()->routeIs('reconciliation.*') ? 'active' : '' }}" data-bs-toggle="collapse" href="#reportCollapse" role="button" aria-expanded="{{ request()->routeIs('report.*') || request()->routeIs('reconciliation.*') ? 'true' : 'false' }}" aria-controls="reportCollapse">
                            <i class="fas fa-chart-line me-2"></i> Laporan
                            <i class="fas fa-chevron-down ms-auto" style="font-size: 0.8rem; float: right; margin-top: 5px;"></i>
                        </a>
                        <div class="collapse {{ request()->routeIs('report.*') || request()->routeIs('reconciliation.*') ? 'show' : '' }}" id="reportCollapse">
                            <ul class="nav flex-column ms-3">
                                <li class="nav-item">
                                    <a class="nav-link {{ request()->routeIs('report.financial') ? 'active fw-bold' : '' }}" href="{{ route('report.financial') }}" style="font-size: 0.9rem;">
                                        <i class="fas fa-circle ms-1 me-2" style="font-size: 0.4rem;"></i> Laporan Keuangan
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link {{ request()->routeIs('report.annual') ? 'active fw-bold' : '' }}" href="{{ route('report.annual') }}" style="font-size: 0.9rem;">
                                        <i class="fas fa-circle ms-1 me-2" style="font-size: 0.4rem;"></i> Laporan Tahunan
                                    </a>
                                </li>
                                @if(!auth()->user()->company || auth()->user()->company->hasFeature('profit') || auth()->user()->isDeveloper())
                                <li class="nav-item">
                                    <a class="nav-link {{ request()->routeIs('report.hpp') ? 'active fw-bold' : '' }}" href="{{ route('report.hpp') }}" style="font-size: 0.9rem;">
                                        <i class="fas fa-circle ms-1 me-2" style="font-size: 0.4rem;"></i> Laporan HPP
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link {{ request()->routeIs('report.profit-margin') ? 'active fw-bold' : '' }}" href="{{ route('report.profit-margin') }}" style="font-size: 0.9rem;">
                                        <i class="fas fa-circle ms-1 me-2" style="font-size: 0.4rem;"></i> Profit & Margin
                                    </a>
                                </li>
                                @endif
                                @if(!auth()->user()->company || auth()->user()->company->hasFeature('finance') || auth()->user()->isDeveloper())
                                <li class="nav-item">
                                    <a class="nav-link {{ request()->routeIs('reconciliation.shopee.*') ? 'active fw-bold' : '' }}" href="{{ route('reconciliation.shopee.index') }}" style="font-size: 0.9rem;">
                                        <i class="fas fa-circle ms-1 me-2" style="font-size: 0.4rem;"></i> Rekonsiliasi Marketplace
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link {{ request()->routeIs('reconciliation.settlement*') ? 'active fw-bold' : '' }}" href="{{ route('reconciliation.settlement') }}" style="font-size: 0.9rem;">
                                        <i class="fas fa-circle ms-1 me-2" style="font-size: 0.4rem;"></i> Analisis Pencairan
                                    </a>
                                </li>
                                @endif
                            </ul>
                        </div>
                    </li>
                    <li class="nav-item mt-3 mb-1 px-3 text-muted">
                        <small class="text-uppercase fw-bold">Pengaturan</small>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('settings.company') ? 'active' : '' }}"
                            href="{{ route('settings.company') }}"><i class="fas fa-building me-2"></i> Profil Perusahaan</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}"
                            href="{{ route('users.index') }}"><i class="fas fa-users-cog me-2"></i> Akun Pengguna</a>
                    </li>
                @endif
                @if(auth()->check() && auth()->user()->isDeveloper())
                    <li class="nav-item mt-3 mb-1 px-3 text-muted">
                        <small class="text-uppercase fw-bold">Developer Tools</small>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('developer.users.*') ? 'active' : '' }}"
                            href="{{ route('developer.users.index') }}"><i class="fas fa-users-cog me-2"></i> Akun Pengguna</a>
                    </li>
                @endif
                <li class="nav-item mt-3 mb-1 px-3 text-muted">
                    <small class="text-uppercase fw-bold">Bantuan</small>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('manual-book.*') ? 'active' : '' }}"
                        href="{{ route('manual-book.index') }}"><i class="fas fa-book-open me-2"></i> Buku Panduan</a>
                </li>
            </ul>
        </nav>

        <!-- Main Content -->
        <div class="content">
            <!-- Top Header -->
            <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom px-4 py-2 d-flex justify-content-between align-items-center"
                style="position: relative; z-index: 20;">
                <div class="d-flex align-items-center gap-3">
                    <button class="btn btn-link text-dark p-0 text-decoration-none d-md-none" id="sidebarToggle">
                        <i class="fas fa-bars fs-5"></i>
                    </button>
                    <div class="d-flex align-items-center gap-2">
                        <div class="bg-primary text-white rounded p-1 d-flex align-items-center justify-content-center shadow-sm"
                            style="width:28px; height:28px;">
                            <i class="fas fa-box-open" style="font-size: 12px;"></i>
                        </div>
                        <span class="fw-bold fs-5 mb-0 navbar-brand-text"
                            style="letter-spacing:-0.5px; color: #1e293b;">{{ auth()->check() && auth()->user()->company ? strtoupper(auth()->user()->company->name) : 'KULASHOP' }}</span>
                        <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-2 py-1"
                            style="font-size:0.7em;">v1.0</span>
                        @if(auth()->check() && auth()->user()->company)
                            <div class="dropdown ms-2 d-none d-md-block">
                                <button class="btn btn-sm btn-light border-0 shadow-sm rounded-pill d-flex align-items-center gap-1 text-muted hover-elevate" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="font-size: 0.75rem; padding: 0.2rem 0.6rem;">
                                    <i class="fas fa-shield-alt text-success" style="font-size: 0.8rem;"></i> <span class="fw-bold">Lisensi</span>
                                </button>
                                <ul class="dropdown-menu shadow-sm border-0 rounded-4 p-2 mt-2" style="font-size: 0.8rem; min-width: 220px;">
                                    <li class="dropdown-header fw-bold text-dark px-2 mb-1"><i class="fas fa-cubes me-1 text-primary"></i> Status Modul Lisensi</li>
                                    <li>
                                        <div class="d-flex justify-content-between align-items-center px-2 py-1 {{ auth()->user()->company->hasFeature('basic') ? 'text-dark fw-medium' : 'text-muted' }}">
                                            <span>Modul Basic</span>
                                            <i class="fas {{ auth()->user()->company->hasFeature('basic') ? 'fa-check-circle text-success' : 'fa-times-circle text-black-50' }}"></i>
                                        </div>
                                    </li>
                                    <li>
                                        <div class="d-flex justify-content-between align-items-center px-2 py-1 {{ auth()->user()->company->hasFeature('finance') ? 'text-dark fw-medium' : 'text-muted' }}">
                                            <span>Finance & Rekonsiliasi</span>
                                            <i class="fas {{ auth()->user()->company->hasFeature('finance') ? 'fa-check-circle text-success' : 'fa-times-circle text-black-50' }}"></i>
                                        </div>
                                    </li>
                                    <li>
                                        <div class="d-flex justify-content-between align-items-center px-2 py-1 {{ auth()->user()->company->hasFeature('profit') ? 'text-dark fw-medium' : 'text-muted' }}">
                                            <span>HPP & Profit</span>
                                            <i class="fas {{ auth()->user()->company->hasFeature('profit') ? 'fa-check-circle text-success' : 'fa-times-circle text-black-50' }}"></i>
                                        </div>
                                    </li>
                                </ul>
                            </div>
                        @endif
                    </div>
                    <div class="ms-4 border-start ps-4 text-muted fw-medium d-none d-md-block" style="font-size: 15px;">
                        @yield('page_title', 'Dashboard')
                    </div>
                </div>

                <div class="d-flex align-items-center gap-3">


                    <div class="dropdown ms-2">
                        <div class="d-flex align-items-center gap-2 cursor-pointer" data-bs-toggle="dropdown"
                            style="cursor: pointer;">
                            <img src="https://ui-avatars.com/api/?name={{ urlencode(auth()->user()->name ?? 'User') }}&background=e2e8f0&color=475569"
                                class="rounded-circle shadow-sm border border-2 border-white" width="36" height="36"
                                alt="User">
                            <div class="d-none d-md-flex flex-column" style="line-height: 1.2;">
                                <span class="fw-bold text-dark"
                                    style="font-size:0.9rem;">{{ auth()->user()->name ?? 'User' }}</span>
                                <span class="text-muted"
                                    style="font-size:0.75rem;">{{ ucfirst(auth()->user()->role ?? 'Admin') }}</span>
                            </div>
                            <i class="fas fa-chevron-down ms-1 text-muted" style="font-size: 10px;"></i>
                        </div>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2 rounded-3">
                            <li><a class="dropdown-item py-2" href="{{ route('profile.edit') }}"><i
                                        class="fas fa-user-circle me-2 text-muted"></i> Profil Saya</a></li>
                            <li><a class="dropdown-item py-2" href="{{ route('settings.company') }}"><i class="fas fa-cog me-2 text-muted"></i>
                                    Pengaturan Perusahaan</a></li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li>
                                <form action="{{ route('logout') }}" method="POST">
                                    @csrf
                                    <button type="submit" class="dropdown-item py-2 text-danger"><i
                                            class="fas fa-sign-out-alt me-2"></i> Keluar</button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </div>
            </nav>

            <!-- Sub Header / Breadcrumb -->
            <div class="bg-white border-bottom px-4 py-3 d-flex justify-content-between align-items-center shadow-sm"
                style="position: sticky; top: 0; z-index: 10; background: rgba(255, 255, 255, 0.9) !important; backdrop-filter: blur(12px);">
                <div>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-1" style="font-size: 0.8rem;">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"
                                    class="text-decoration-none text-muted"><i class="fas fa-home"></i> Home</a></li>
                            <li class="breadcrumb-item active fw-medium" aria-current="page"
                                style="color: var(--primary-color);">@hasSection('page_title') @yield('page_title') @else @yield('title', 'Dashboard') @endif</li>
                        </ol>
                    </nav>
                    <h4 class="mb-0 fw-bold text-dark" style="letter-spacing: -0.5px;">@hasSection('page_title') @yield('page_title') @else @yield('title', 'Dashboard') @endif
                    </h4>
                </div>
                <div class="d-flex gap-2">
                    <button
                        class="btn btn-white border border-2 fw-medium px-3 shadow-sm d-flex align-items-center gap-2 rounded-3"
                        onclick="history.back()">
                        <i class="fas fa-arrow-left text-muted"></i> Kembali
                    </button>
                    <div
                        class="btn btn-white border border-2 fw-medium px-3 shadow-sm d-flex align-items-center gap-2 text-success rounded-3">
                        <i class="far fa-clock"></i> <span id="realtime-clock">--:--</span>
                    </div>
                </div>
            </div>

            <main>
                @yield('content')
            </main>
        </div>
    </div>

    <!-- jQuery (loaded first for plugins) -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        function updateClock() {
            const now = new Date();
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            document.getElementById('realtime-clock').textContent = hours + ':' + minutes;
        }
        setInterval(updateClock, 1000);
        updateClock();

        // Mobile Sidebar Toggle
        document.getElementById('sidebarToggle').addEventListener('click', function () {
            document.body.classList.toggle('sidebar-open');
        });
        document.getElementById('sidebarOverlay').addEventListener('click', function () {
            document.body.classList.remove('sidebar-open');
        });
    </script>
    @stack('scripts')
</body>

</html>