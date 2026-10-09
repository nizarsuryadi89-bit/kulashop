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
            if (DB::getDriverName() !== 'sqlite') {
                $table->dropForeign(['customer_id']);
                $table->dropForeign(['marketplace_account_id']);
            }

            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('restrict');
            $table->foreign('marketplace_account_id')->references('id')->on('marketplace_accounts')->onDelete('restrict');
        });

        Schema::table('order_items', function (Blueprint $table) {
            if (DB::getDriverName() !== 'sqlite') {
                $table->dropForeign(['product_id']);
            }
            $table->foreign('product_id')->references('id')->on('products')->onDelete('restrict');
        });

        Schema::table('import_batches', function (Blueprint $table) {
            if (DB::getDriverName() !== 'sqlite') {
                $table->dropForeign('import_batches_marketplace_id_foreign'); // Fix bug from old migration
                $table->dropForeign(['marketplace_account_id']);
            }

            $table->foreign('marketplace_account_id')->references('id')->on('marketplace_accounts')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
            $table->dropForeign(['marketplace_account_id']);

            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
            $table->foreign('marketplace_account_id')->references('id')->on('marketplace_accounts')->onDelete('cascade');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->foreign('product_id')->references('id')->on('products')->onDelete('set null');
        });

        Schema::table('import_batches', function (Blueprint $table) {
            $table->dropForeign(['marketplace_account_id']);

            $table->foreign('marketplace_account_id')->references('id')->on('marketplace_accounts')->onDelete('cascade');
            // Deliberately not adding back the buggy constraint import_batches_marketplace_id_foreign
        });
    }
};
