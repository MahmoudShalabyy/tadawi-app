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
        Schema::table('orders', function (Blueprint $table) {
            $table->string('order_number')->nullable()->after('id');
            $table->string('shipping_address')->nullable()->after('billing_address');
            $table->string('currency', 3)->default('EGP')->after('total_amount');
            $table->unique('order_number', 'uq_orders_order_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropUnique('uq_orders_order_number');
            $table->dropColumn(['order_number', 'shipping_address', 'currency']);
        });
    }
};


