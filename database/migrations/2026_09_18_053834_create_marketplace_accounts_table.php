<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('marketplace_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marketplace_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->decimal('tax_percentage', 5, 2)->default(0);
            $table->timestamps();
        });

        // Migrate existing marketplaces to marketplace_accounts and adjust marketplaces table
        // We will move data from marketplaces to marketplace_accounts (assuming 1-to-1 for existing)
        $existingMarketplaces = DB::table('marketplaces')->get();
        foreach ($existingMarketplaces as $mp) {
            DB::table('marketplace_accounts')->insert([
                'id' => $mp->id, // keep the same ID so foreign keys in orders still match temporarily
                'marketplace_id' => $mp->id,
                'name' => $mp->name,
                'is_active' => true,
                'tax_percentage' => $mp->tax_percentage ?? 0,
                'created_at' => $mp->created_at,
                'updated_at' => $mp->updated_at,
            ]);
        }

        // Now remove tax_percentage from marketplaces since it belongs to accounts
        Schema::table('marketplaces', function (Blueprint $table) {
            $table->dropColumn('tax_percentage');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('marketplaces', function (Blueprint $table) {
            $table->decimal('tax_percentage', 5, 2)->default(0)->after('code');
        });

        $accounts = DB::table('marketplace_accounts')->get();
        foreach ($accounts as $acc) {
            DB::table('marketplaces')
                ->where('id', $acc->marketplace_id)
                ->update(['tax_percentage' => $acc->tax_percentage]);
        }

        Schema::dropIfExists('marketplace_accounts');
    }
};
