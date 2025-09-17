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
        Schema::create('medicines', function (Blueprint $table) {
            $table->id();
            $table->string('brand_name');
            $table->string('form', 100)->nullable();
            $table->string('dosage_strength', 100)->nullable();
            $table->string('manufacturer')->nullable();
            $table->decimal('price', 10, 2)->nullable();

            // Foreign key to active_ingredients
            $table->foreignId('active_ingredient_id')->constrained('active_ingredients')->onDelete('cascade');

            $table->timestamps();
            $table->softDeletes();

            // Indexes for common searches
            $table->index('brand_name', 'idx_medicines_brand');
            $table->index('price', 'idx_medicines_price');
            $table->index(['brand_name', 'form'], 'idx_medicines_brand_form');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medicines');
    }
};


