<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignId('discount_id')->nullable()->after('customer_id')->constrained()->onDelete('set null');
            $table->decimal('subtotal_before_discount', 15, 2)->default(0)->after('total');
            $table->decimal('discount_amount', 15, 2)->default(0)->after('subtotal_before_discount');
            $table->string('voucher_code')->nullable()->after('discount_amount');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['discount_id']);
            $table->dropColumn(['discount_id', 'subtotal_before_discount', 'discount_amount', 'voucher_code']);
        });
    }
};
