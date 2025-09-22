<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Backfill NULL statuses to 'active' to keep existing pharmacies functional
        DB::table('pharmacy_profiles')
            ->whereNull('status')
            ->update(['status' => 'active']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Optionally revert backfilled rows to NULL if they were 'active'
        // Note: This is a best-effort revert and may not perfectly restore prior state.
        DB::table('pharmacy_profiles')
            ->where('status', 'active')
            ->update(['status' => null]);
    }
};


