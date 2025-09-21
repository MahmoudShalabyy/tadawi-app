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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('pharmacy_id')->constrained('pharmacy_profiles')->onDelete('cascade');
            $table->enum('status', ['cart','pending', 'processing', 'completed', 'cancelled'])->default('pending');
            $table->enum('payment_method', ['cash', 'paypal']);
            $table->string('billing_address')->nullable();
            $table->integer('total_items')->default(0); 
            $table->decimal('total_amount', 8, 2)->default(0.00); 
            $table->timestamps();
            $table->softDeletes();

            // Indexes for common queries
            $table->index('status', 'idx_orders_status');
            $table->index(['user_id', 'status'], 'idx_orders_user_status');
            $table->index(['pharmacy_id', 'status'], 'idx_orders_pharmacy_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
