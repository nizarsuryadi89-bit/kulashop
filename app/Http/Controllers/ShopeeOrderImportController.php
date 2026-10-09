<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessMarketplaceImport;
use App\Models\ImportBatch;
use App\Models\MarketplaceAccount;
use App\Services\ShopeeOrderImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ShopeeOrderImportController extends Controller
{
    protected $importService;

    public function __construct(ShopeeOrderImportService $importService)
    {
        $this->importService = $importService;
    }

    public function index()
    {
        // Hanya ambil toko yang marketplace-nya shopee
        $accounts = MarketplaceAccount::whereHas('marketplace', function ($q) {
            $q->where('code', 'shopee');
        })->where('status', 'active')->get();

        return view('import.shopee', compact('accounts'));
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
            'file' => 'required|file|mimes:xlsx,xls',
        ]);

        try {
            // Simpan file sementara di storage/app/private/temp
            $path = $request->file('file')->store('temp', 'private');
            $originalName = $request->file('file')->getClientOriginalName();

            $result = $this->importService->processPreview($path, $originalName, $request->marketplace_account_id);

            return response()->json($result);

        } catch (\Exception $e) {
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

        // Buat batch
        $batch = ImportBatch::create([
            'marketplace_account_id' => $request->marketplace_account_id,
            'import_type' => 'shopee_order',
            'file_name' => $request->original_file_name,
            'file_path' => $newPath,
            'uploaded_by' => auth()->id(),
            'status' => 'pending',
        ]);

        try {
            // Get exact calculation summary from service
            $summary = $this->importService->processPreview($newPath, $request->original_file_name, $request->marketplace_account_id);

            // Set batch summary
            $batch->update([
                'summary' => [
                    'total_rows' => $summary['total_rows'],
                    'unique_orders' => $summary['total_unique_orders'],
                    'new_orders' => $summary['new_orders'],
                    'updated_orders' => $summary['updated_orders'],
                    'unmapped_skus' => $summary['unmapped_skus'],
                ],
            ]);

            // Execute Synchronously (as permitted for stage 1)
            ProcessMarketplaceImport::dispatchSync($batch);

            $batch->refresh();

            $statusText = $batch->status === 'completed' ? 'Completed' : 'Completed with Errors';

            return response()->json([
                'success' => true,
                'message' => 'IMPORT SELESAI',
                'summary' => [
                    'total_rows' => $batch->total_rows,
                    'success_rows' => $batch->success_rows,
                    'updated_rows' => $batch->updated_rows,
                    'duplicate_rows' => $batch->duplicate_rows,
                    'error_rows' => $batch->error_rows,
                    'status' => $statusText,
                    'batch_id' => $batch->id,
                ],
            ]);
        } catch (\Exception $e) {
            $batch->update(['status' => 'failed', 'error_log' => $e->getMessage()]);

            return response()->json(['error' => 'Gagal memproses import: '.$e->getMessage()], 500);
        }
    }
}
