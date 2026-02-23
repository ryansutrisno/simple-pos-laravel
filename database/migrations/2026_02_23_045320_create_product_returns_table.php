<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_returns', function (Blueprint $table) {
            $table->id();
            $table->string('return_number')->unique();
            $table->foreignId('transaction_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['full', 'partial', 'exchange']);
            $table->enum('reason_category', ['damaged', 'wrong_item', 'not_as_expected', 'other']);
            $table->text('reason_note')->nullable();
            $table->enum('refund_method', ['cash', 'store_credit', 'original_payment'])->nullable();
            $table->decimal('total_refund', 12, 2)->default(0);
            $table->decimal('total_exchange_value', 12, 2)->default(0);
            $table->decimal('selisih_amount', 12, 2)->default(0);
            $table->enum('selisih_payment_method', ['cash', 'store_credit'])->nullable();
            $table->unsignedBigInteger('store_credit_id')->nullable();
            $table->integer('points_reversed')->default(0);
            $table->integer('points_returned')->default(0);
            $table->dateTime('return_date');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_returns');
    }
};
