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
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('pharmacy_id')->constrained('pharmacy_profiles')->onDelete('cascade');
            $table->integer('rating');
            $table->text('comment')->nullable();
            $table->timestamps();

            // Unique constraint: one review per user per pharmacy
            $table->unique(['user_id', 'pharmacy_id'], 'uq_reviews_user_pharmacy');

            // Indexes for common queries
            $table->index('rating', 'idx_reviews_rating');
            $table->index(['pharmacy_id', 'rating'], 'idx_reviews_pharmacy_rating');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
