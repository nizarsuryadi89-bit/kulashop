<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Company;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DeveloperController extends Controller
{
    public function index()
    {
        $companies = Company::all();
        
        // System Health Checks
        $dbStatus = true;
        try {
            DB::connection()->getPdo();
        } catch (\Exception $e) {
            $dbStatus = false;
        }

        $queueCount = DB::table('jobs')->count();
        $failedQueueCount = DB::table('failed_jobs')->count();

        $systemInfo = [
            'php_version' => phpversion(),
            'laravel_version' => app()->version(),
            'environment' => app()->environment(),
            'debug_mode' => config('app.debug') ? 'Enabled' : 'Disabled',
            'db_status' => $dbStatus ? 'Connected' : 'Disconnected',
            'queue_driver' => config('queue.default'),
            'pending_jobs' => $queueCount,
            'failed_jobs' => $failedQueueCount,
            'cache_driver' => config('cache.default'),
            'session_driver' => config('session.driver'),
        ];

        return view('developer.index', compact('companies', 'systemInfo'));
    }

    public function updateModules(Request $request, Company $company)
    {
        $request->validate([
            'features' => 'nullable|array',
        ]);

        $company->features = $request->features ?? [];
        $company->save();

        return redirect()->back()->with('success', 'Modul berhasil diperbarui untuk company: ' . $company->name);
    }

    public function factoryReset(Request $request)
    {
        // Peringatan: ini akan membersihkan data transaksi operasional, namun tidak menghapus Company, User, Marketplace, Product dan pengaturan dasar lainnya jika diinginkan.
        // Berdasarkan permintaan user: "menghapus data agar bisa digunakan di tempat lain"
        // Maka kita akan kosongkan tabel operasional.

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // Reset Data Transaksi
        DB::table('order_financials')->truncate();
        DB::table('order_items')->truncate();
        DB::table('stock_movements')->truncate();
        DB::table('orders')->truncate();
        
        // Reset Data Import & Error
        DB::table('import_errors')->truncate();
        DB::table('import_batches')->truncate();

        // Reset Keuangan & Expense
        DB::table('expenses')->truncate();
        DB::table('expense_categories')->truncate();
        
        // Reset Customer
        DB::table('customers')->truncate();
        
        // Reset Product Data
        DB::table('product_hpp_histories')->truncate();
        DB::table('product_marketplace')->truncate();
        DB::table('warehouse_stocks')->truncate();
        DB::table('products')->truncate();

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        return redirect()->back()->with('success', 'Factory Reset berhasil. Seluruh data transaksi dan operasional telah dikosongkan.');
    }

    public function updateApiToken(Request $request)
    {
        $request->validate([
            'shopee_api_token' => 'nullable|string',
        ]);

        $path = base_path('.env');

        if (file_exists($path)) {
            $env = file_get_contents($path);
            $token = $request->shopee_api_token;
            
            // Periksa apakah sudah ada key SHOPEE_API_TOKEN
            if (strpos($env, 'SHOPEE_API_TOKEN=') !== false) {
                // Replace yang sudah ada
                $env = preg_replace('/^SHOPEE_API_TOKEN=.*$/m', 'SHOPEE_API_TOKEN="' . $token . '"', $env);
            } else {
                // Tambahkan di akhir file
                $env .= "\nSHOPEE_API_TOKEN=\"" . $token . "\"\n";
            }
            
            file_put_contents($path, $env);
        }

        return redirect()->back()->with('success', 'API Token berhasil disimpan ke environment (.env).');
    }

    public function usersIndex()
    {
        $users = \App\Models\User::orderBy('role')->orderBy('name')->get();
        return view('developer.users', compact('users'));
    }

    public function usersStore(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
            'role' => 'required|in:developer,owner,admin',
        ]);

        $validated['password'] = \Illuminate\Support\Facades\Hash::make($validated['password']);
        
        $firstCompany = Company::first();
        if ($firstCompany) {
            $validated['company_id'] = $firstCompany->id;
        }

        \App\Models\User::create($validated);

        return redirect()->back()->with('success', 'User berhasil ditambahkan.');
    }

    public function usersDestroy(\App\Models\User $user)
    {
        if ($user->id === auth()->id()) {
            return redirect()->back()->with('error', 'Tidak bisa menghapus akun sendiri.');
        }

        $user->delete();
        return redirect()->back()->with('success', 'User berhasil dihapus.');
    }

    public function usersResetPassword(\App\Models\User $user, Request $request)
    {
        $request->validate([
            'new_password' => 'required|string|min:6',
        ]);

        $user->update([
            'password' => \Illuminate\Support\Facades\Hash::make($request->new_password)
        ]);

        return redirect()->back()->with('success', 'Password untuk ' . $user->name . ' berhasil direset.');
    }
}
