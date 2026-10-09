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
        Schema::table('product_hpp_histories', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->renameColumn('user_id', 'changed_by');
            
            // Add effective_at and set the new foreign key
            $table->timestamp('effective_at')->useCurrent()->after('reason');
            $table->foreign('changed_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_hpp_histories', function (Blueprint $table) {
            $table->dropForeign(['changed_by']);
            $table->dropColumn('effective_at');
            $table->renameColumn('changed_by', 'user_id');
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });
    }
};
