<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaction_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained()->onDelete('cascade');
            $table->enum('payment_method', ['cash', 'transfer', 'qris']);
            $table->decimal('amount', 15, 2);
            $table->string('reference')->nullable();
            $table->timestamps();

            $table->index(['transaction_id', 'payment_method']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaction_payments');
    }
};
