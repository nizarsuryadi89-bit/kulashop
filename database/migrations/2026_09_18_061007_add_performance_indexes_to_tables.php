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
        Schema::table('orders', function (Blueprint $table) {
            $table->index('external_order_id');
            $table->index('order_date');
            $table->index('order_status');
        });

        Schema::table('import_batches', function (Blueprint $table) {
            $table->index('import_type');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['external_order_id']);
            $table->dropIndex(['order_date']);
            $table->dropIndex(['order_status']);
        });

        Schema::table('import_batches', function (Blueprint $table) {
            $table->dropIndex(['import_type']);
            $table->dropIndex(['status']);
        });
    }
};
