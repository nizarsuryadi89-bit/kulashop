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
        Schema::table('order_items', function (Blueprint $table) {
            $table->decimal('hpp_snapshot', 15, 2)->nullable()->default(null)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            // Revert it back to default(0) and not nullable
            // Using DB::statement because doctrine/dbal sometimes struggles with reverting nullable to non-nullable
            $table->decimal('hpp_snapshot', 15, 2)->nullable(false)->default(0)->change();
        });
    }
};
