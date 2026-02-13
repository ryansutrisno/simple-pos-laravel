<?php

namespace Database\Factories;

use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

class StoreFactory extends Factory
{
    protected $model = Store::class;

    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'address' => fake()->address(),
            'phone' => fake()->phoneNumber(),
            'printer_device_id' => null,
            'logo_path' => null,
            'receipt_header_message' => 'Selamat Datang di '.fake()->company(),
            'receipt_footer_message' => 'Terima Kasih!',
            'receipt_tagline' => fake()->catchPhrase(),
            'show_cashier_name' => true,
            'show_barcode' => false,
            'show_qr_code' => false,
            'receipt_template_id' => null,
            'receipt_width' => 58,
        ];
    }
}
