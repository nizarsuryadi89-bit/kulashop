<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImportError extends Model
{
    protected $fillable = [
        'import_batch_id',
        'row_number',
        'external_order_id',
        'error_type',
        'error_message',
        'raw_data',
        'is_resolved',
    ];

    protected $casts = [
        'raw_data' => 'array',
    ];

    public function importBatch()
    {
        return $this->belongsTo(ImportBatch::class);
    }

    public function isRetriable(): bool
    {
        // Must have raw_data to process
        if (! is_array($this->raw_data) || isset($this->raw_data['chunk']) || empty($this->raw_data)) {
            return false;
        }

        // List of error types that shouldn't be retried because they require file changes
        $unretriableTypes = [
            'invalid_header',
            'invalid_file',
            'invalid_date',
            'invalid_number',
            'missing_order_id',
            'missing_required_field',
            'invalid_data',
        ];

        return ! in_array($this->error_type, $unretriableTypes);
    }
}
