<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('discount_category', function (Blueprint $table) {
            $table->foreignId('discount_id')->constrained()->onDelete('cascade');
            $table->foreignId('category_id')->constrained()->onDelete('cascade');
            $table->primary(['discount_id', 'category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discount_category');
    }
};
