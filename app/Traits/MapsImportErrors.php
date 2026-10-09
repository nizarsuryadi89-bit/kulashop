<?php

namespace App\Traits;

use Illuminate\Database\QueryException;

trait MapsImportErrors
{
    /**
     * Map exception to a standardized error type.
     */
    protected function mapErrorType(\Throwable $e): string
    {
        $message = strtolower($e->getMessage());

        if ($e instanceof QueryException) {
            $sqlState = $e->errorInfo[0] ?? '';
            if ($sqlState === '23000') {
                return 'duplicate_conflict';
            }

            return 'database_error';
        }

        if (str_contains($message, 'header') || str_contains($message, 'heading')) {
            return 'invalid_header';
        }

        if (str_contains($message, 'file') || str_contains($message, 'format') || str_contains($message, 'spreadsheet')) {
            return 'invalid_file';
        }

        if (str_contains($message, 'date') || str_contains($message, 'time') || str_contains($message, 'carbon')) {
            return 'invalid_date';
        }

        if (str_contains($message, 'number') || str_contains($message, 'numeric') || str_contains($message, 'float') || str_contains($message, 'integer') || str_contains($message, 'non well formed numeric')) {
            return 'invalid_number';
        }

        if (str_contains($message, 'order id') || str_contains($message, 'no_pesanan') || str_contains($message, 'nomor pesanan') || str_contains($message, 'missing order_id')) {
            return 'missing_order_id';
        }

        if (str_contains($message, 'required') || str_contains($message, 'missing') || str_contains($message, 'empty')) {
            return 'missing_required_field';
        }

        if (str_contains($message, 'not found') || str_contains($message, 'tidak ditemukan')) {
            return 'order_not_found';
        }

        if ($e instanceof \InvalidArgumentException || $e instanceof \UnexpectedValueException) {
            return 'invalid_data';
        }

        return 'unknown_error';
    }
}
