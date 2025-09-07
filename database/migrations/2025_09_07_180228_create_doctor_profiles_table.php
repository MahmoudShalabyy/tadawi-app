<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    
    public function up(): void
    {
        Schema::create('doctor_profiles', function (Blueprint $table) {
            $table->id();
            // user table link.
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Professional information for verification and display
            $table->string('medical_license_id')->unique(); // For verification
            $table->string('specialization'); 
            $table->text('clinic_address')->nullable();

            $table->timestamps();
        });
    }

   
    public function down(): void
    {
        Schema::dropIfExists('doctor_profiles');
    }
};
