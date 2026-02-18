<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('split_bills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained()->onDelete('cascade');
            $table->unsignedInteger('split_number');
            $table->decimal('subtotal', 15, 2);
            $table->enum('payment_method', ['cash', 'transfer', 'qris']);
            $table->decimal('amount_paid', 15, 2);
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['transaction_id', 'split_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('split_bills');
    }
};
