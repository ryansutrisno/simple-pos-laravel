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
        Schema::table('financial_records', function (Blueprint $table) {
            $table->unsignedBigInteger('product_return_id')->nullable()->after('transaction_id');
            $table->foreign('product_return_id')
                ->references('id')
                ->on('product_returns')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('financial_records', function (Blueprint $table) {
            $table->dropForeign(['product_return_id']);
            $table->dropColumn('product_return_id');
        });
    }
};
