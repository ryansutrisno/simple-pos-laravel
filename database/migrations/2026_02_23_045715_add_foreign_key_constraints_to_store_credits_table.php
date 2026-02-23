<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('store_credits', function (Blueprint $table) {
            $table->foreign('product_return_id')
                ->references('id')
                ->on('product_returns')
                ->nullOnDelete();
        });

        Schema::table('product_returns', function (Blueprint $table) {
            $table->foreign('store_credit_id')
                ->references('id')
                ->on('store_credits')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('store_credits', function (Blueprint $table) {
            $table->dropForeign(['product_return_id']);
        });

        Schema::table('product_returns', function (Blueprint $table) {
            $table->dropForeign(['store_credit_id']);
        });
    }
};
