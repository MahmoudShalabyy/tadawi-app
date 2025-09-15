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
        Schema::create('config', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type')->default('string'); // string, boolean, integer, json
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Insert default configuration values
        DB::table('config')->insert([
            [
                'key' => 'site_name',
                'value' => 'Tadawi',
                'type' => 'string',
                'description' => 'Site name',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'email_config',
                'value' => 'admin@tadawi.com',
                'type' => 'string',
                'description' => 'Admin email address',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'map_api_key',
                'value' => '',
                'type' => 'string',
                'description' => 'Google Maps API key',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'ocr_api_key',
                'value' => '',
                'type' => 'string',
                'description' => 'OCR API key for prescription scanning',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'site_logo',
                'value' => null,
                'type' => 'string',
                'description' => 'Site logo path',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'site_theme',
                'value' => 'light',
                'type' => 'string',
                'description' => 'Site theme (light/dark)',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'timezone',
                'value' => 'Africa/Cairo',
                'type' => 'string',
                'description' => 'Site timezone',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'currency',
                'value' => 'EGP',
                'type' => 'string',
                'description' => 'Site currency',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'public_key',
                'value' => '',
                'type' => 'string',
                'description' => 'Public API key',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'private_key',
                'value' => '',
                'type' => 'string',
                'description' => 'Private API key',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'ai_link',
                'value' => '',
                'type' => 'string',
                'description' => 'AI service endpoint URL',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('config');
    }
};
