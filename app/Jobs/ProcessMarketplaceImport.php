<?php

namespace App\Jobs;

use App\Imports\ShopeeOrderImport;
use App\Models\ImportBatch;
use App\Services\Import\ShopeeIncomeSheetImporter;
use App\Services\Import\TiktokExcelImporter;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Maatwebsite\Excel\Facades\Excel;

class ProcessMarketplaceImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 1800; // 30 minutes for large files

    public $tries = 1; // Don't retry automatically on timeout

    protected $batch;

    public function middleware(): array
    {
        // Mencegah job import berjalan bersamaan untuk akun yang sama (Concurrency Protection)
        // Job akan di-release kembali ke antrean jika sedang ada job lain yang berjalan
        return [new WithoutOverlapping($this->batch->marketplace_account_id)];
    }

    /**
     * Create a new job instance.
     */
    public function __construct(ImportBatch $batch)
    {
        $this->batch = $batch;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $this->batch->update([
                'status' => 'processing',
                'started_at' => now(),
            ]);

            $marketplaceAccount = $this->batch->marketplaceAccount;
            $marketplace = $marketplaceAccount->marketplace;
            $importer = null;

            if ($marketplace->code === 'shopee') {
                if ($this->batch->import_type === 'shopee_income') {
                    $importer = new ShopeeIncomeSheetImporter($this->batch);
                } else {
                    $importer = new ShopeeOrderImport($this->batch);
                }
            } elseif ($marketplace->code === 'tiktok') {
                $importer = new TiktokExcelImporter($this->batch);
            }

            if ($importer) {
                // Get absolute path
                $filePath = storage_path('app/private/'.$this->batch->file_path);
                // Also check storage/app/ if private is not used depending on laravel version
                if (! file_exists($filePath)) {
                    $filePath = storage_path('app/'.$this->batch->file_path);
                }

                Excel::import($importer, $filePath);

                $this->batch->refresh(); // Ambil nilai row counts terbaru dari database

                $this->batch->update([
                    'status' => $this->batch->error_rows > 0 ? 'completed_with_errors' : 'completed',
                    'completed_at' => now(),
                ]);
            } else {
                throw new \Exception("Marketplace Importer not found for code: {$marketplace->code}");
            }

        } catch (\Throwable $e) {
            \Log::error('ProcessMarketplaceImport Global Error: '.$e->getMessage(), [
                'batch_id' => $this->batch->id,
                'trace' => $e->getTraceAsString(),
            ]);

            $this->batch->update([
                'status' => 'failed',
                'completed_at' => now(),
                'error_log' => 'Import gagal diproses. Terjadi kesalahan sistem (Global Error). Silakan hubungi developer untuk memeriksa detail error pada log sistem.',
            ]);
        }
    }
}
