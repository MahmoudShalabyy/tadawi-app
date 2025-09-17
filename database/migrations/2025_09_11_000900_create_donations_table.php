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
        Schema::create('donations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('location')->nullable();
            $table->string('contact_info')->nullable();
            $table->boolean('verified')->default(false);
            $table->timestamps();
            $table->softDeletes();

            // Indexes for common queries
            $table->index('verified', 'idx_donations_verified');
            $table->index(['user_id', 'verified'], 'idx_donations_user_verified');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('donations');
    }
};
