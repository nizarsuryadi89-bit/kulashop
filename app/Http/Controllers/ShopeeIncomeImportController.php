<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessMarketplaceImport;
use App\Models\ImportBatch;
use App\Models\MarketplaceAccount;
use App\Services\ShopeeIncomeImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ShopeeIncomeImportController extends Controller
{
    protected $importService;

    public function __construct(ShopeeIncomeImportService $importService)
    {
        $this->importService = $importService;
    }

    public function index()
    {
        // Hanya ambil toko yang marketplace-nya shopee
        $accounts = MarketplaceAccount::whereHas('marketplace', function ($q) {
            $q->where('code', 'shopee');
        })->where('status', 'active')->get();

        return view('import.shopee_income', compact('accounts'));
    }

    public function preview(Request $request)
    {
        $request->validate([
            'marketplace_account_id' => [
                'required',
                'exists:marketplace_accounts,id',
                function ($attribute, $value, $fail) {
                    $account = MarketplaceAccount::with('marketplace')->find($value);
                    if (! $account || $account->marketplace->code !== 'shopee') {
                        $fail('Akun yang dipilih bukan merupakan akun Shopee.');
                    }
                },
            ],
            'file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        try {
            // Simpan file sementara di storage/app/private/temp
            $path = $request->file('file')->store('temp', 'private');
            $originalName = $request->file('file')->getClientOriginalName();

            $result = $this->importService->processPreview($path, $originalName, $request->marketplace_account_id);
            $result['temp_path'] = $path;

            return response()->json($result);

        } catch (\Exception $e) {
            \Log::error('ShopeeIncomeImport preview error: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function process(Request $request)
    {
        $request->validate([
            'marketplace_account_id' => [
                'required',
                'exists:marketplace_accounts,id',
                function ($attribute, $value, $fail) {
                    $account = MarketplaceAccount::with('marketplace')->find($value);
                    if (! $account || $account->marketplace->code !== 'shopee') {
                        $fail('Akun yang dipilih bukan merupakan akun Shopee.');
                    }
                },
            ],
            'temp_file_path' => 'required|string',
            'original_file_name' => 'required|string',
        ]);

        $tempPath = $request->temp_file_path;

        // Pindahkan file dari temp ke imports
        if (! Storage::disk('private')->exists($tempPath)) {
            return response()->json(['error' => 'File temporary tidak ditemukan. Silakan upload ulang.'], 400);
        }

        $newPath = 'imports/'.basename($tempPath);
        Storage::disk('private')->move($tempPath, $newPath);

        $batch = ImportBatch::create([
            'marketplace_account_id' => $request->marketplace_account_id,
            'import_type' => 'shopee_income',
            'file_path' => $newPath,
            'file_name' => $request->original_file_name,
            'status' => 'pending',
            'uploaded_by' => auth()->id(),
        ]);

        // Dispatch Job menggunakan ProcessMarketplaceImport yang sama
        ProcessMarketplaceImport::dispatch($batch);

        return response()->json([
            'success' => true,
            'message' => 'File sedang diproses di latar belakang',
            'batch_id' => $batch->id,
        ]);
    }
}
