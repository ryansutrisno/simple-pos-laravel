<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->string('logo_path')->nullable()->after('printer_device_id');
            $table->string('receipt_header_message')->nullable()->after('logo_path');
            $table->string('receipt_footer_message')->nullable()->after('receipt_header_message');
            $table->string('receipt_tagline')->nullable()->after('receipt_footer_message');
            $table->boolean('show_cashier_name')->default(true)->after('receipt_tagline');
            $table->boolean('show_barcode')->default(true)->after('show_cashier_name');
            $table->boolean('show_qr_code')->default(false)->after('show_barcode');
            $table->string('receipt_template_id')->nullable()->after('show_qr_code');
            $table->string('receipt_width')->default('58mm')->after('receipt_template_id');
        });
    }

    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->dropColumn([
                'logo_path',
                'receipt_header_message',
                'receipt_footer_message',
                'receipt_tagline',
                'show_cashier_name',
                'show_barcode',
                'show_qr_code',
                'receipt_template_id',
                'receipt_width',
            ]);
        });
    }
};
