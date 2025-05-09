<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financial_records', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['sales', 'purchase', 'expense', 'other']);
            $table->decimal('amount', 15, 2);
            $table->decimal('profit', 15, 2)->nullable(); // Untuk mencatat laba dari penjualan
            $table->foreignId('transaction_id')->nullable()->constrained(); // Referensi ke transaksi
            $table->text('description');
            $table->date('record_date');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('financial_records');
    }
};
