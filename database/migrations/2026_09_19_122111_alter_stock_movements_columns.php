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
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->renameColumn('quantity', 'qty');
            $table->renameColumn('previous_stock', 'stock_before');
            $table->renameColumn('current_stock', 'stock_after');
            $table->renameColumn('notes', 'note');
            $table->renameColumn('user_id', 'created_by');
        });
    }

    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->renameColumn('qty', 'quantity');
            $table->renameColumn('stock_before', 'previous_stock');
            $table->renameColumn('stock_after', 'current_stock');
            $table->renameColumn('note', 'notes');
            $table->renameColumn('created_by', 'user_id');
        });
    }
};
