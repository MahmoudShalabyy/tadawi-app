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
        Schema::create('medicine_classes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medicine_id')->constrained('medicines')->onDelete('cascade');
            $table->foreignId('therapeutic_class_id')->constrained('therapeutic_classes')->onDelete('cascade');
            $table->text('note')->nullable();
            
            // Composite primary key and unique constraint
            $table->unique(['medicine_id', 'therapeutic_class_id'], 'uq_medicine_classes_medicine_therapeutic');
            
            // Indexes for common queries
            $table->index('medicine_id', 'idx_medicine_classes_medicine');
            $table->index('therapeutic_class_id', 'idx_medicine_classes_therapeutic');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medicine_classes');
    }
};
