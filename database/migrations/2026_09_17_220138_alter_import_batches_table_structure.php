<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('import_batches', function (Blueprint $table) {
            if (Schema::hasColumn('import_batches', 'marketplace_id')) {
                $table->renameColumn('marketplace_id', 'marketplace_account_id');
            }
            if (Schema::hasColumn('import_batches', 'filename')) {
                $table->renameColumn('filename', 'file_path');
            }
        });

        Schema::table('import_batches', function (Blueprint $table) {
            $table->string('import_type')->nullable()->after('marketplace_account_id');
            $table->string('file_name')->nullable()->after('import_type');

            $table->dateTime('period_start')->nullable();
            $table->dateTime('period_end')->nullable();

            $table->integer('success_rows')->default(0);
            $table->integer('duplicate_rows')->default(0);
            $table->integer('updated_rows')->default(0);
            $table->integer('error_rows')->default(0);

            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('started_at')->nullable();
            $table->dateTime('completed_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('import_batches', function (Blueprint $table) {
            $table->dropForeign(['uploaded_by']);
            $table->dropColumn([
                'import_type',
                'file_name',
                'period_start',
                'period_end',
                'success_rows',
                'duplicate_rows',
                'updated_rows',
                'error_rows',
                'uploaded_by',
                'started_at',
                'completed_at',
            ]);
        });

        Schema::table('import_batches', function (Blueprint $table) {
            $table->renameColumn('marketplace_account_id', 'marketplace_id');
            $table->renameColumn('file_path', 'filename');
        });
    }
};
