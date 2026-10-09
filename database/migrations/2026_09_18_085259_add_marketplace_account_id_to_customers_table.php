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
        Schema::table('customers', function (Blueprint $table) {
            if (! Schema::hasColumn('customers', 'marketplace_account_id')) {
                $table->foreignId('marketplace_account_id')->nullable()->after('id')->constrained()->nullOnDelete();

                // Drop any existing global unique constraint on username if it exists
                $indexes = Schema::getIndexes('customers');
                $indexNames = array_map(function ($idx) {
                    return $idx['name'];
                }, $indexes);
                if (in_array('customers_username_unique', $indexNames)) {
                    $table->dropUnique('customers_username_unique');
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            if (Schema::hasColumn('customers', 'marketplace_account_id')) {
                $table->dropForeign(['marketplace_account_id']);
                $table->dropColumn('marketplace_account_id');
            }
        });
    }
};
