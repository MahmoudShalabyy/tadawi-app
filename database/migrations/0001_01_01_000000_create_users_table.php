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
    Schema::create('users', function (Blueprint $table) {
       $table->id();
        $table->string('name');
        $table->string('email')->unique();
        $table->timestamp('email_verified_at')->nullable();
        $table->string('password');
        $table->string('phone_number')->unique();
        $table->string('profile_picture_path')->nullable();
        $table->enum('role', ['patient', 'pharmacy', 'admin', 'doctor'])->default('patient');
        $table->enum('status', ['pending', 'active', 'suspended'])->default('active'); // Patients are active by default
        $table->boolean('travel_mode')->default(false);
        $table->string('google_id')->nullable()->index();
        $table->rememberToken();
        $table->timestamps();
        $table->softDeletes();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
