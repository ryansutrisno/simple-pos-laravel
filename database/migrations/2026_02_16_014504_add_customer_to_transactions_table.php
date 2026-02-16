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
        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignId('customer_id')->nullable()->after('user_id')->constrained()->onDelete('set null');
            $table->unsignedInteger('points_earned')->default(0)->after('change_amount');
            $table->unsignedInteger('points_redeemed')->default(0)->after('points_earned');
            $table->decimal('discount_from_points', 15, 2)->default(0)->after('points_redeemed');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
            $table->dropColumn(['customer_id', 'points_earned', 'points_redeemed', 'discount_from_points']);
        });
    }
};
