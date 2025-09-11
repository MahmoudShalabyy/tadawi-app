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
        Schema::create('pharmacy_profiles', function (Blueprint $table) {
            $table->id();

            // Link to users table (one-to-one) and ensure uniqueness
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->unique('user_id', 'uq_pharmacies_user_id');

            // Profile details
            $table->string('location')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('contact_info')->nullable();
            $table->boolean('verified')->default(false);
            $table->decimal('rating', 3, 2)->default(0);

            $table->timestamps();

            // Helpful indexes for common queries
            $table->index('verified', 'idx_pharmacies_verified');
            $table->index('rating', 'idx_pharmacies_rating');
            // Composite index for simple geo bounding-box searches
            $table->index(['latitude', 'longitude'], 'idx_pharmacies_lat_lng');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pharmacy_profiles');
    }
};


