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
    Schema::create('patient_profiles', function (Blueprint $table) {
        $table->id();
        //link to users table
        $table->foreignId('user_id')->constrained()->onDelete('cascade');

        //profile details
        $table->date('date_of_birth')->nullable();
        $table->enum('gender', ['male', 'female'])->nullable();
        $table->string('national_id')->unique()->nullable();
        $table->text('medical_history_summary')->nullable(); //chronic conditions, allergies, etc.

        //for delivery system in future
        $table->text('default_address')->nullable();
        
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patient_profiles');
    }
};
