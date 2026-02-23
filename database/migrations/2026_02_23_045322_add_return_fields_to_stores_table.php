<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->integer('return_deadline_days')->default(7)->after('printer_device_id');
            $table->boolean('enable_store_credit')->default(true)->after('return_deadline_days');
            $table->integer('store_credit_expiry_days')->default(180)->after('enable_store_credit');
            $table->boolean('store_credit_never_expires')->default(false)->after('store_credit_expiry_days');
        });
    }

    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->dropColumn([
                'return_deadline_days',
                'enable_store_credit',
                'store_credit_expiry_days',
                'store_credit_never_expires',
            ]);
        });
    }
};
