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
        Schema::create('prescription_uploads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->string('file_path');
            $table->text('ocr_text')->nullable();
            $table->boolean('validated_by_doctor')->default(false);
            $table->timestamps();
            $table->softDeletes();

            // Indexes for common queries
            $table->index('order_id', 'idx_prescription_uploads_order');
            $table->index('validated_by_doctor', 'idx_prescription_uploads_validated');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prescription_uploads');
    }
};
