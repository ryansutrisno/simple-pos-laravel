<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ReceiptTemplate;

class ReceiptTemplateSeeder extends Seeder
{
    public function run(): void
    {
        // Default Standard Template
        $standardTemplate = [
            'name' => 'Standard Receipt',
            'description' => 'Standard receipt template with store information and basic formatting',
            'template_data' => [
                'header' => [
                    'show_logo' => true,
                    'show_store_name' => true,
                    'show_store_address' => true,
                    'show_store_phone' => true,
                    'show_tagline' => true,
                    'custom_header_message' => '',
                ],
                'body' => [
                    'show_transaction_id' => true,
                    'show_date' => true,
                    'show_cashier_name' => true,
                    'show_items_header' => true,
                    'item_format' => 'name_price_quantity', // name_price_quantity | name_only | price_only
                    'show_subtotal' => true,
                    'show_tax' => false,
                    'show_discount' => false,
                ],
                'footer' => [
                    'show_payment_method' => true,
                    'show_cash_received' => true,
                    'show_change' => true,
                    'custom_footer_message' => 'Terima kasih atas kunjungan Anda!',
                    'show_barcode' => true,
                    'show_qr_code' => false,
                    'show_signature' => false,
                ],
                'styling' => [
                    'font_size' => 'normal',
                    'text_alignment' => 'left',
                    'bold_headers' => true,
                    'separator_style' => 'dashes', // dashes | dots | line
                ],
            ],
            'is_default' => true,
            'is_active' => true,
            'store_id' => null, // Global template
        ];

        ReceiptTemplate::create($standardTemplate);

        // Compact Template
        $compactTemplate = [
            'name' => 'Compact Receipt',
            'description' => 'Minimal receipt template for small paper sizes',
            'template_data' => [
                'header' => [
                    'show_logo' => true,
                    'show_store_name' => true,
                    'show_store_address' => false,
                    'show_store_phone' => false,
                    'show_tagline' => false,
                    'custom_header_message' => '',
                ],
                'body' => [
                    'show_transaction_id' => true,
                    'show_date' => true,
                    'show_cashier_name' => false,
                    'show_items_header' => false,
                    'item_format' => 'name_only',
                    'show_subtotal' => true,
                    'show_tax' => false,
                    'show_discount' => false,
                ],
                'footer' => [
                    'show_payment_method' => true,
                    'show_cash_received' => false,
                    'show_change' => false,
                    'custom_footer_message' => 'Thanks!',
                    'show_barcode' => false,
                    'show_qr_code' => false,
                    'show_signature' => false,
                ],
                'styling' => [
                    'font_size' => 'small',
                    'text_alignment' => 'center',
                    'bold_headers' => false,
                    'separator_style' => 'line',
                ],
            ],
            'is_default' => false,
            'is_active' => true,
            'store_id' => null,
        ];

        ReceiptTemplate::create($compactTemplate);

        // Detailed Template
        $detailedTemplate = [
            'name' => 'Detailed Receipt',
            'description' => 'Comprehensive receipt template with full information',
            'template_data' => [
                'header' => [
                    'show_logo' => true,
                    'show_store_name' => true,
                    'show_store_address' => true,
                    'show_store_phone' => true,
                    'show_tagline' => true,
                    'custom_header_message' => 'Welcome to our store!',
                ],
                'body' => [
                    'show_transaction_id' => true,
                    'show_date' => true,
                    'show_cashier_name' => true,
                    'show_items_header' => true,
                    'item_format' => 'name_price_quantity',
                    'show_subtotal' => true,
                    'show_tax' => true,
                    'show_discount' => true,
                ],
                'footer' => [
                    'show_payment_method' => true,
                    'show_cash_received' => true,
                    'show_change' => true,
                    'custom_footer_message' => 'Thank you for your purchase! Follow us on social media.',
                    'show_barcode' => true,
                    'show_qr_code' => true,
                    'show_signature' => false,
                ],
                'styling' => [
                    'font_size' => 'normal',
                    'text_alignment' => 'left',
                    'bold_headers' => true,
                    'separator_style' => 'dashes',
                ],
            ],
            'is_default' => false,
            'is_active' => true,
            'store_id' => null,
        ];

        ReceiptTemplate::create($detailedTemplate);
    }
}
