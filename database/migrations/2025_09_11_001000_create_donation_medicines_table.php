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
        Schema::create('donation_medicines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('donation_id')->constrained()->onDelete('cascade');
            $table->foreignId('medicine_id')->constrained()->onDelete('cascade');
            $table->integer('quantity');
            $table->date('expiry_date')->nullable();
            $table->string('batch_num', 100)->nullable();
            
            // Composite unique constraint
            $table->unique(['donation_id', 'medicine_id'], 'uq_donation_medicines_donation_medicine');
            
            // Indexes for common queries
            $table->index('donation_id', 'idx_donation_medicines_donation');
            $table->index('medicine_id', 'idx_donation_medicines_medicine');
            $table->index('expiry_date', 'idx_donation_medicines_expiry');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('donation_medicines');
    }
};
