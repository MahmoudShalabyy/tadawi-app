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
        Schema::create('stock_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pharmacy_id')->constrained('pharmacy_profiles')->onDelete('cascade');
            $table->foreignId('medicine_id')->constrained('medicines')->onDelete('cascade');
            $table->string('batch_num', 100)->nullable();
            $table->date('exp_date')->nullable();
            $table->integer('quantity');
            $table->timestamps();
            $table->softDeletes();

            // Unique constraint: prevent duplicate batches (same pharmacy + medicine + batch)
            $table->unique(['pharmacy_id', 'medicine_id', 'batch_num'], 'uq_stock_pharmacy_medicine_batch');

            // Indexes to support lookups and expiry checks
            $table->index(['pharmacy_id', 'medicine_id'], 'idx_stock_pharmacy_medicine');
            $table->index('exp_date', 'idx_stock_exp_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_batches');
    }
};


