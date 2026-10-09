<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\MarketplaceAccountController;
use App\Http\Controllers\MarketplaceController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductMarketplaceController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ShopeeIncomeImportController;
use App\Http\Controllers\ShopeeOrderImportController;
use App\Http\Controllers\ShopeeReconciliationController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Auth Routes
Route::get('login', [LoginController::class, 'showLoginForm'])->name('login')->middleware('guest');
Route::post('login', [LoginController::class, 'login'])->name('login.post')->middleware('guest');
Route::post('logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');
Route::get('logout', [LoginController::class, 'logout'])->middleware('auth');

Route::middleware(['auth'])->group(function () {
    Route::get('/', function () {
        return redirect()->route('dashboard');
    });

    Route::get('/profile', [\App\Http\Controllers\ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile/password', [\App\Http\Controllers\ProfileController::class, 'updatePassword'])->name('profile.password.update');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::resource('marketplaces', MarketplaceController::class)
        ->middleware('role:admin,owner');

    Route::get('/marketplaces/{id}/accounts', [MarketplaceAccountController::class, 'getByMarketplace'])
        ->name('marketplaces.accounts.api');

    Route::resource('marketplace-accounts', MarketplaceAccountController::class)
        ->middleware('role:admin,owner');

    // Expense Management
    Route::middleware(['role:owner', 'feature:profit'])->group(function () {
        Route::resource('expense-categories', App\Http\Controllers\ExpenseCategoryController::class)->except(['show']);
        Route::resource('expenses', App\Http\Controllers\ExpenseController::class)->except(['show']);
    });

    Route::prefix('settings')->name('settings.')->middleware('role:owner')->group(function () {
        Route::get('/company', [CompanyController::class, 'edit'])->name('company');
        Route::put('/company', [CompanyController::class, 'update'])->name('company.update');
    });

    Route::resource('users', UserController::class)->middleware('role:owner');

    Route::prefix('import')->name('import.')->middleware('role:admin,owner,developer')->group(function () {
        Route::get('/', [ImportController::class, 'index'])->name('index');
        Route::get('/history', [ImportController::class, 'history'])->name('history');
        Route::post('/upload', [ImportController::class, 'upload'])->name('upload');
        Route::prefix('shopee')->name('shopee.')->group(function () {
            Route::get('/', [ShopeeOrderImportController::class, 'index'])->name('index');
            Route::post('/preview', [ShopeeOrderImportController::class, 'preview'])->name('preview');
            Route::post('/process', [ShopeeOrderImportController::class, 'process'])->name('process');
        });

        Route::prefix('shopee-income')->name('shopee_income.')->middleware('feature:finance')->group(function () {
            Route::get('/', [ShopeeIncomeImportController::class, 'index'])->name('index');
            Route::post('/preview', [ShopeeIncomeImportController::class, 'preview'])->name('preview');
            Route::post('/process', [ShopeeIncomeImportController::class, 'process'])->name('process');
        });

        Route::get('/{id}', [ImportController::class, 'show'])->name('show');
        Route::post('/errors/{id}/retry', [ImportController::class, 'retryError'])->name('retry');
        Route::get('/errors/{id}', [ImportController::class, 'showError'])->name('error.show');
        Route::get('/{id}/status', [ImportController::class, 'statusApi'])->name('status_api');
    });

    // Reconciliation Routes (Requires finance module)
    Route::prefix('reconciliation')->name('reconciliation.')->middleware('feature:finance')->group(function () {
        Route::get('/shopee', [ShopeeReconciliationController::class, 'index'])->name('shopee.index');
        Route::get('/shopee/{id}', [ShopeeReconciliationController::class, 'show'])->name('shopee.show');
        
        // Step 15: Settlement Analysis (v2.0)
        Route::get('/settlement', [App\Http\Controllers\SettlementAnalysisController::class, 'index'])->name('settlement');
        Route::get('/settlement/{date}', [App\Http\Controllers\SettlementAnalysisController::class, 'show'])->name('settlement.show');
    });

    // Purchase & Supplier Management
    Route::prefix('purchase')->name('purchase.')->middleware('role:admin,owner')->group(function () {
        Route::resource('suppliers', \App\Http\Controllers\SupplierController::class)->except(['show', 'destroy']);
        Route::resource('orders', \App\Http\Controllers\PurchaseOrderController::class)->except(['destroy']);
        Route::resource('receipts', \App\Http\Controllers\GoodsReceiptController::class)->except(['edit', 'update', 'destroy']);
        Route::get('history', [\App\Http\Controllers\PurchaseHistoryController::class, 'index'])->name('history');
        Route::resource('payments', \App\Http\Controllers\PurchasePaymentController::class)->only(['index', 'create', 'store', 'show']);
        Route::get('report', [\App\Http\Controllers\PurchaseReportController::class, 'index'])->name('report');
    });

    // ======== INVENTORY COSTING & VALUATION ======== //
    // Modul ini sementara kita gabung di auth. Jika ada batasan lisensi, akan diatur di layout
    Route::prefix('inventory/costing')->group(function () {
        Route::get('/valuation', [\App\Http\Controllers\InventoryCostController::class, 'valuation'])->name('inventory.valuation');
        Route::get('/report', [\App\Http\Controllers\InventoryCostController::class, 'report'])->name('inventory.costing.report');
        Route::get('/reconciliation', [\App\Http\Controllers\InventoryCostController::class, 'reconciliation'])->name('inventory.costing.reconciliation');
        
        Route::get('/opening-stock', [\App\Http\Controllers\InventoryCostController::class, 'createOpeningStock'])->name('inventory.costing.opening_stock');
        Route::post('/opening-stock', [\App\Http\Controllers\InventoryCostController::class, 'storeOpeningStock'])->name('inventory.costing.opening_stock.store');
    });

    // Orders / Transaksi Manual
    Route::resource('orders', OrderController::class)->names('order');

    Route::prefix('report')->name('report.')->middleware('role:owner')->group(function () {
        Route::get('/financial', [ReportController::class, 'financial'])->name('financial');
        Route::get('/annual', [ReportController::class, 'annual'])->name('annual');
        
        Route::middleware('feature:profit')->group(function() {
            Route::post('/financial/sync-hpp', [ReportController::class, 'syncHpp'])->name('financial.sync_hpp');
            Route::get('/hpp', [ReportController::class, 'hppSummary'])->name('hpp');
        });

        Route::middleware('feature:finance')->group(function() {
            Route::get('/financial/shopee/{marketplace}', [ReportController::class, 'shopeeIncome'])->name('shopee_income');
        });
    });

    Route::get('/profit', [App\Http\Controllers\ProfitMarginController::class, 'index'])->name('report.profit-margin')->middleware(['role:owner', 'feature:profit']);

    // Products
    Route::prefix('products')->middleware('role:admin,owner')->group(function () {
        Route::get('/unmapped', [ProductController::class, 'unmapped'])->name('product.unmapped');
        Route::post('/unmapped/sync', [ProductController::class, 'syncUnmapped'])->name('product.unmapped.sync');
        Route::get('/{product}/marketplace', [ProductMarketplaceController::class, 'index'])->name('product.mapping');
        Route::post('/{product}/marketplace', [ProductMarketplaceController::class, 'store'])->name('product.mapping.store');
        Route::delete('/{product}/marketplace/{mapping}', [ProductMarketplaceController::class, 'destroy'])->name('product.mapping.destroy');
        Route::patch('/{product}/status', [ProductController::class, 'toggleStatus'])->name('product.toggle-status');
        Route::put('/{product}/hpp', [ProductController::class, 'updateHpp'])->name('product.update-hpp');
    });
    Route::resource('products', ProductController::class)->names('product')->middleware('role:admin,owner');

    // Customers
    Route::resource('customers', CustomerController::class)->names('customers');

    // Warehouses & Inventory
    Route::middleware('role:admin,owner')->group(function () {
        Route::patch('/gudangs/{warehouse}/set-default', [\App\Http\Controllers\WarehouseController::class, 'setDefault'])->name('warehouses.set-default');
        Route::resource('gudangs', \App\Http\Controllers\WarehouseController::class)->names('warehouses')->parameters(['gudangs' => 'warehouse'])->except(['show', 'create', 'edit']);

        Route::prefix('inventory')->name('inventory.')->group(function () {
            Route::get('/', [\App\Http\Controllers\InventoryController::class, 'index'])->name('index');
            Route::get('/stock', [\App\Http\Controllers\InventoryController::class, 'stockSummary'])->name('stock-summary');
            Route::get('/stock/in', [\App\Http\Controllers\InventoryController::class, 'stockIn'])->name('stock-in');
            Route::post('/stock/in', [\App\Http\Controllers\InventoryController::class, 'storeStockIn'])->name('stock-in.store');
            Route::get('/stock/out', [\App\Http\Controllers\InventoryController::class, 'stockOut'])->name('stock-out');
            Route::post('/stock/out', [\App\Http\Controllers\InventoryController::class, 'storeStockOut'])->name('stock-out.store');
            Route::get('/stock/adjustment', [\App\Http\Controllers\InventoryController::class, 'adjustment'])->name('adjustment');
            Route::post('/stock/adjustment', [\App\Http\Controllers\InventoryController::class, 'storeAdjustment'])->name('adjustment.store');
            Route::get('/stock/movements', [\App\Http\Controllers\InventoryController::class, 'stockMovements'])->name('stock-movements');
            Route::get('/stock-card/{product}', [\App\Http\Controllers\InventoryController::class, 'stockCard'])->name('stock-card');
            Route::get('/low-stock', [\App\Http\Controllers\InventoryController::class, 'lowStock'])->name('low-stock');
        });
    });

    // Buku Panduan / Manual Book
    Route::get('/manual-book', [\App\Http\Controllers\ManualBookController::class, 'index'])->name('manual-book.index');

    // --- DEVELOPER ROUTES ---
    Route::middleware(['developer'])->prefix('developer')->name('developer.')->group(function () {
        Route::get('/', [\App\Http\Controllers\DeveloperController::class, 'index'])->name('index');
        Route::post('/modules/{company}', [\App\Http\Controllers\DeveloperController::class, 'updateModules'])->name('modules.update');
        Route::post('/factory-reset', [\App\Http\Controllers\DeveloperController::class, 'factoryReset'])->name('factory-reset');
        Route::post('/api-token', [\App\Http\Controllers\DeveloperController::class, 'updateApiToken'])->name('api-token.update');
        
        // Developer User Management
        Route::get('/users', [\App\Http\Controllers\DeveloperController::class, 'usersIndex'])->name('users.index');
        Route::post('/users', [\App\Http\Controllers\DeveloperController::class, 'usersStore'])->name('users.store');
        Route::delete('/users/{user}', [\App\Http\Controllers\DeveloperController::class, 'usersDestroy'])->name('users.destroy');
        Route::post('/users/{user}/reset-password', [\App\Http\Controllers\DeveloperController::class, 'usersResetPassword'])->name('users.reset-password');
    });
});
