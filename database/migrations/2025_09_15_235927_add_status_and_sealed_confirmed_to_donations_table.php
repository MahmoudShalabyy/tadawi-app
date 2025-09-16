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
        Schema::table('donations', function (Blueprint $table) {
            $table->string('status')->default('proposed')->after('verified');
            $table->boolean('sealed_confirmed')->default(false)->after('status');
            
            // Index for status queries
            $table->index('status', 'idx_donations_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('donations', function (Blueprint $table) {
            $table->dropIndex('idx_donations_status');
            $table->dropColumn(['status', 'sealed_confirmed']);
        });
    }
};
