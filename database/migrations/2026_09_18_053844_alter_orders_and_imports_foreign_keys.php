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
            // Drop old foreign key constraint that references marketplaces
            $fks = Schema::getForeignKeys('orders');
            $fkNames = array_map(function ($fk) {
                return $fk['name'];
            }, $fks);

            if (in_array('orders_marketplace_account_id_foreign', $fkNames)) {
                $table->dropForeign('orders_marketplace_account_id_foreign');
            }

            // Add new foreign key constraint to marketplace_accounts
            $table->foreign('marketplace_account_id')->references('id')->on('marketplace_accounts')->cascadeOnDelete();
        });

        Schema::table('import_batches', function (Blueprint $table) {
            // Drop old foreign key constraint
            $fks = Schema::getForeignKeys('import_batches');
            $fkNames = array_map(function ($fk) {
                return $fk['name'];
            }, $fks);

            if (in_array('import_batches_marketplace_account_id_foreign', $fkNames)) {
                $table->dropForeign('import_batches_marketplace_account_id_foreign');
            }

            // Add new foreign key constraint
            $table->foreign('marketplace_account_id')->references('id')->on('marketplace_accounts')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['marketplace_account_id']);
            $table->foreign('marketplace_account_id')->references('id')->on('marketplaces')->cascadeOnDelete();
        });

        Schema::table('import_batches', function (Blueprint $table) {
            $table->dropForeign(['marketplace_account_id']);
            $table->foreign('marketplace_account_id')->references('id')->on('marketplaces')->cascadeOnDelete();
        });
    }
};
