<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImportBatch extends Model
{
    protected $fillable = [
        'marketplace_account_id',
        'import_type',
        'file_name',
        'file_path',
        'status',
        'total_rows',
        'processed_rows',
        'error_log',
        'period_start',
        'period_end',
        'success_rows',
        'duplicate_rows',
        'updated_rows',
        'error_rows',
        'uploaded_by',
        'started_at',
        'completed_at',
    ];

    public function marketplaceAccount()
    {
        return $this->belongsTo(MarketplaceAccount::class, 'marketplace_account_id');
    }

    public function importErrors()
    {
        return $this->hasMany(ImportError::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
