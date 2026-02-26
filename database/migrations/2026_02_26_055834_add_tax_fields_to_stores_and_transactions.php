<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->boolean('tax_enabled')->default(false)->after('store_credit_never_expires');
            $table->decimal('tax_rate', 5, 2)->default(10.00)->after('tax_enabled');
            $table->string('tax_name', 50)->default('PPN')->after('tax_rate');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->decimal('subtotal_before_tax', 12, 2)->nullable()->after('total_splits');
            $table->decimal('tax_amount', 12, 2)->nullable()->after('subtotal_before_tax');
            $table->decimal('tax_rate', 5, 2)->nullable()->after('tax_amount');
            $table->boolean('tax_enabled')->default(false)->after('tax_rate');
        });
    }

    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->dropColumn(['tax_enabled', 'tax_rate', 'tax_name']);
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn(['subtotal_before_tax', 'tax_amount', 'tax_rate', 'tax_enabled']);
        });
    }
};
