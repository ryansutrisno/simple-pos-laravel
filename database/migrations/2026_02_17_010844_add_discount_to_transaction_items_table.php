<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transaction_items', function (Blueprint $table) {
            $table->decimal('original_price', 15, 2)->default(0)->after('selling_price');
            $table->decimal('discount_amount', 15, 2)->default(0)->after('original_price');
            $table->foreignId('discount_id')->nullable()->after('discount_amount')->constrained()->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('transaction_items', function (Blueprint $table) {
            $table->dropForeign(['discount_id']);
            $table->dropColumn(['original_price', 'discount_amount', 'discount_id']);
        });
    }
};
