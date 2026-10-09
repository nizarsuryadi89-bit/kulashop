<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('marketplace_accounts', function (Blueprint $table) {
            $table->string('account_code')->nullable()->after('name');
            $table->string('status')->default('active')->after('is_active');
        });

        // Migrate data
        $accounts = DB::table('marketplace_accounts')->get();
        foreach ($accounts as $acc) {
            $code = Str::slug($acc->name).'-'.$acc->id;
            DB::table('marketplace_accounts')
                ->where('id', $acc->id)
                ->update([
                    'account_code' => $code,
                    'status' => $acc->is_active ? 'active' : 'inactive',
                ]);
        }

        Schema::table('marketplace_accounts', function (Blueprint $table) {
            $table->unique('account_code');
            $table->renameColumn('name', 'account_name');
            $table->dropColumn('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('marketplace_accounts', function (Blueprint $table) {
            $table->dropUnique(['account_code']);
            $table->dropColumn('account_code');
            $table->boolean('is_active')->default(true);
        });

        $accounts = DB::table('marketplace_accounts')->get();
        foreach ($accounts as $acc) {
            DB::table('marketplace_accounts')
                ->where('id', $acc->id)
                ->update([
                    'is_active' => $acc->status === 'active',
                ]);
        }

        Schema::table('marketplace_accounts', function (Blueprint $table) {
            $table->dropColumn('status');
            $table->renameColumn('account_name', 'name');
        });
    }
};
