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
            $table->string('customer_code')->nullable()->after('id')->unique();
            $table->string('username')->nullable()->after('customer_code');
            $table->renameColumn('name', 'customer_name');
            $table->string('postal_code')->nullable()->after('province');

            if (Schema::hasColumn('customers', 'village')) {
                $table->dropColumn('village');
            }
        });

        // Update existing customer codes
        DB::statement("UPDATE customers SET customer_code = CONCAT('CUST-', id) WHERE customer_code IS NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropUnique(['customer_code']);
            $table->dropColumn(['customer_code', 'username', 'postal_code']);
            $table->renameColumn('customer_name', 'name');
            $table->string('village')->nullable();
        });
    }
};
