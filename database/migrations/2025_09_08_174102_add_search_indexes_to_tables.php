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
        // Add indexes to users table for common search patterns
        Schema::table('users', function (Blueprint $table) {
            // Index for searching by role (find all doctors, patients, etc.)
            $table->index('role', 'idx_users_role');
            
            // Index for searching by status (active, pending, suspended)
            $table->index('status', 'idx_users_status');
            
            // Composite index for role + status queries (e.g., "active doctors")
            $table->index(['role', 'status'], 'idx_users_role_status');
            
            // Index for name searches (partial name matching)
            $table->index('name', 'idx_users_name');
            
            // Index for travel_mode filtering
            $table->index('travel_mode', 'idx_users_travel_mode');
            
            // Composite index for deleted_at (soft deletes) with role
            $table->index(['deleted_at', 'role'], 'idx_users_deleted_role');
        });

        // Add indexes to doctor_profiles table
        Schema::table('doctor_profiles', function (Blueprint $table) {
            // Index for specialization searches (find doctors by specialty)
            $table->index('specialization', 'idx_doctors_specialization');
            
            // Composite index for user_id + specialization (quick doctor lookup)
            $table->index(['user_id', 'specialization'], 'idx_doctors_user_specialization');
        });

        // Add indexes to patient_profiles table  
        Schema::table('patient_profiles', function (Blueprint $table) {
            // Index for gender filtering
            $table->index('gender', 'idx_patients_gender');
            
            // Index for date_of_birth (age-based searches)
            $table->index('date_of_birth', 'idx_patients_dob');
            
            // Composite index for gender + date_of_birth
            $table->index(['gender', 'date_of_birth'], 'idx_patients_gender_dob');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_users_role');
            $table->dropIndex('idx_users_status');
            $table->dropIndex('idx_users_role_status');
            $table->dropIndex('idx_users_name');
            $table->dropIndex('idx_users_travel_mode');
            $table->dropIndex('idx_users_deleted_role');
        });

        Schema::table('doctor_profiles', function (Blueprint $table) {
            $table->dropIndex('idx_doctors_specialization');
            $table->dropIndex('idx_doctors_user_specialization');
        });

        Schema::table('patient_profiles', function (Blueprint $table) {
            $table->dropIndex('idx_patients_gender');
            $table->dropIndex('idx_patients_dob');
            $table->dropIndex('idx_patients_gender_dob');
        });
    }
};
