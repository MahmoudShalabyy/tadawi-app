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
        Schema::create('order_medicines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('medicine_id')->constrained()->onDelete('cascade');
            $table->integer('quantity');
            $table->decimal('price_at_time', 8, 2)->nullable(); // إضافة لتخزين سعر الوقت
            
            // Composite unique constraint
            $table->unique(['order_id', 'medicine_id'], 'uq_order_medicines_order_medicine');
            
            // Indexes for common queries
            $table->index('order_id', 'idx_order_medicines_order');
            $table->index('medicine_id', 'idx_order_medicines_medicine');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_medicines');
    }
};
