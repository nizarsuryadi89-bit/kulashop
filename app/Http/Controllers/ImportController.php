<?php

namespace App\Http\Controllers;

use App\Imports\ShopeeOrderImport;
use App\Jobs\ProcessMarketplaceImport;
use App\Models\ImportBatch;
use App\Models\ImportError;
use App\Models\Marketplace;
use App\Models\MarketplaceAccount;
use App\Services\Import\ShopeeIncomeSheetImporter;
use App\Services\Import\TiktokExcelImporter;
use Illuminate\Http\Request;

class ImportController extends Controller
{
    public function index()
    {
        $batches = ImportBatch::with('marketplaceAccount.marketplace')->orderBy('created_at', 'desc')->get();
        $marketplaces = Marketplace::where('is_active', true)->get();

        return view('import.index', compact('batches', 'marketplaces'));
    }

    public function history(Request $request)
    {
        $query = ImportBatch::with('marketplaceAccount.marketplace', 'uploader')->orderBy('created_at', 'desc');

        if ($request->filled('marketplace')) {
            $query->whereHas('marketplaceAccount', function ($q) use ($request) {
                $q->where('marketplace_id', $request->marketplace);
            });
        }
        if ($request->filled('toko')) {
            $query->where('marketplace_account_id', $request->toko);
        }
        if ($request->filled('import_type')) {
            $query->where('import_type', $request->import_type);
        }
        if ($request->filled('tanggal_mulai')) {
            $query->whereDate('created_at', '>=', $request->tanggal_mulai);
        }
        if ($request->filled('tanggal_akhir')) {
            $query->whereDate('created_at', '<=', $request->tanggal_akhir);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('file_name', 'like', '%'.$request->search.'%')
                    ->orWhere('id', $request->search);
            });
        }

        $batches = $query->paginate(20)->appends($request->all());

        $marketplaces = Marketplace::where('is_active', true)->get();
        $accounts = MarketplaceAccount::where('status', 'active')->get();

        return view('import.history', compact('batches', 'marketplaces', 'accounts'));
    }

    public function upload(Request $request)
    {
        $request->validate([
            'marketplace_account_id' => 'required|exists:marketplace_accounts,id',
            'import_type' => 'required|string',
            'file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        $account = MarketplaceAccount::findOrFail($request->marketplace_account_id);

        // Calculate hash for audit purposes
        $fileHash = md5_file($request->file('file')->getRealPath());

        // Store the file
        $path = $request->file('file')->store('imports');

        // Create Import Batch
        $batch = ImportBatch::create([
            'marketplace_account_id' => $account->id,
            'import_type' => $request->import_type,
            'file_name' => $request->file('file')->getClientOriginalName(),
            'file_path' => $path,
            'file_hash' => $fileHash,
            'uploaded_by' => auth()->id(),
            'status' => 'pending',
        ]);

        // Dispatch Job
        ProcessMarketplaceImport::dispatch($batch);

        return back()
            ->with('success', 'File berhasil diupload dan sedang diproses di background.')
            ->with('recent_batch_id', $batch->id);
    }

    public function show($id)
    {
        $batch = ImportBatch::findOrFail($id);
        $errors = $batch->importErrors()->paginate(50);

        return view('import.show', compact('batch', 'errors'));
    }

    public function showError($id)
    {
        $error = ImportError::with('importBatch')->findOrFail($id);

        return view('import.error_detail', compact('error'));
    }

    public function retryError($id)
    {
        $error = ImportError::findOrFail($id);
        $batch = $error->importBatch;

        if (! $error->isRetriable()) {
            return back()->with('error', 'Error ini tidak dapat di-retry karena data mentah hilang atau tipe error ('.class_basename($error->error_type).') membutuhkan perbaikan file source.');
        }

        try {
            // Record original state
            $initialErrorCount = clone $batch;

            // We DO NOT delete the old error yet.
            // If the retry fails, the importer will create a NEW error record automatically.

            $isFailed = false;

            if ($batch->import_type === 'shopee_order') {
                $importer = new ShopeeOrderImport($batch);
                $importer->collection(collect([$error->raw_data]));

                if ($importer->success_rows > 0) {
                    $batch->increment('success_rows', $importer->success_rows);
                }
                if ($importer->updated_rows > 0) {
                    $batch->increment('updated_rows', $importer->updated_rows);
                }
                if ($importer->duplicate_rows > 0) {
                    $batch->increment('duplicate_rows', $importer->duplicate_rows);
                }

                $isFailed = $importer->error_rows > 0;

            } elseif ($batch->import_type === 'shopee_income') {
                $importer = new ShopeeIncomeSheetImporter($batch);
                $importer->collection(collect([$error->raw_data])); // Use collection to match interface

                if ($importer->success_rows > 0) {
                    $batch->increment('success_rows', $importer->success_rows);
                }
                if ($importer->updated_rows > 0) {
                    $batch->increment('updated_rows', $importer->updated_rows);
                }
                if ($importer->duplicate_rows > 0) {
                    $batch->increment('duplicate_rows', $importer->duplicate_rows);
                }

                $isFailed = $importer->error_rows > 0;

            } elseif ($batch->import_type === 'tiktok_order') {
                $importer = new TiktokExcelImporter($batch);
                $importer->model($error->raw_data);

                // TiktokExcelImporter directly increments on the DB batch model, but it also increments total_rows.
                // We revert the total_rows increment because this is a retry, not a new row.
                $batch->decrement('total_rows');

                $batch->refresh();
                // If it failed, Tiktok incremented error_rows back up.
                // So if error_rows is equal to initial (before decrement), it failed.
                $isFailed = $batch->error_rows >= $initialErrorCount->error_rows;
            } else {
                return back()->with('error', 'Tipe import tidak didukung untuk retry.');
            }

            if ($isFailed) {
                // Retry gagal. Importer telah membuat error baru di database.
                // Kita update error lama dengan pesan dari error baru, lalu hapus error baru tersebut agar tidak duplikat.
                $newError = $batch->importErrors()->latest('id')->first();
                if ($newError && $newError->id !== $error->id) {
                    $error->update([
                        'error_type' => $newError->error_type,
                        'error_message' => $newError->error_message,
                    ]);
                    $newError->delete();
                }

                // Revert decrement if any? No, we didn't decrement yet.
                // For Tiktok, we decremented total_rows but it re-incremented total_rows and error_rows.
                // But we deleted the new error! So Tiktok's error_rows is now inflated?
                // Let's just fix the batch counts manually.
                $batch->update([
                    'error_rows' => $batch->importErrors()->where('is_resolved', false)->count(),
                ]);

                return back()->with('error', 'Retry dieksekusi namun gagal. Error terbaru telah dicatat.');
            }

            // Retry sukses!
            $error->update(['is_resolved' => true]);

            // Re-sync error rows count
            $batch->update([
                'error_rows' => $batch->importErrors()->where('is_resolved', false)->count(),
            ]);

            return back()->with('success', 'Retry berhasil diproses.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Retry gagal: '.$e->getMessage());
        }
    }

    public function statusApi($id)
    {
        $batch = ImportBatch::findOrFail($id);

        return response()->json($batch);
    }
}
