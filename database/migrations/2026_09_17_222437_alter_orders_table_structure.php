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
        // 1. Drop foreign key if it exists under old name
        if (Schema::hasColumn('orders', 'marketplace_id')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropForeign(['marketplace_id']);
                $table->renameColumn('marketplace_id', 'marketplace_account_id');
            });
        }

        // 2. Drop unique if exists and rename
        if (Schema::hasColumn('orders', 'order_id')) {
            Schema::table('orders', function (Blueprint $table) {
                // Ignore error if index doesn't exist
                $indexes = Schema::getIndexes('orders');
                $indexNames = array_map(function ($idx) {
                    return $idx['name'];
                }, $indexes);
                if (in_array('orders_order_id_unique', $indexNames)) {
                    $table->dropUnique('orders_order_id_unique');
                }
                $table->renameColumn('order_id', 'external_order_id');
            });
        }

        // 3. Rename other columns
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'status')) {
                $table->renameColumn('status', 'order_status');
            }
            if (Schema::hasColumn('orders', 'paid_date')) {
                $table->renameColumn('paid_date', 'paid_at');
            }
            if (Schema::hasColumn('orders', 'completed_date')) {
                $table->renameColumn('completed_date', 'completed_at');
            }
            if (Schema::hasColumn('orders', 'grand_total')) {
                $table->renameColumn('grand_total', 'total_payment');
            }
        });

        // 4. Re-add foreign key if needed
        $fks = Schema::getForeignKeys('orders');
        $fkNames = array_map(function ($fk) {
            return $fk['name'];
        }, $fks);

        if (! in_array('orders_marketplace_account_id_foreign', $fkNames)) {
            Schema::table('orders', function (Blueprint $table) {
                $table->foreign('marketplace_account_id')->references('id')->on('marketplaces')->cascadeOnDelete();
            });
        }

        // 5. Add new columns one by one
        if (! Schema::hasColumn('orders', 'order_substatus')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->string('order_substatus')->nullable()->after('order_status');
            });
        }
        if (! Schema::hasColumn('orders', 'cancelled_at')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dateTime('cancelled_at')->nullable()->after('completed_at');
            });
        }
        if (! Schema::hasColumn('orders', 'currency')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->string('currency')->default('IDR')->after('total_payment');
            });
        }
        if (! Schema::hasColumn('orders', 'source_type')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->string('source_type')->default('marketplace')->after('currency');
            });
        }
        if (! Schema::hasColumn('orders', 'raw_data')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->json('raw_data')->nullable()->after('source_type');
            });
        }

        // 6. Add unique index
        $indexes = Schema::getIndexes('orders');
        $indexNames = array_map(function ($idx) {
            return $idx['name'];
        }, $indexes);
        if (! in_array('orders_marketplace_account_id_external_order_id_unique', $indexNames)) {
            Schema::table('orders', function (Blueprint $table) {
                $table->unique(['marketplace_account_id', 'external_order_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {}
};
