<?php

namespace App\Observers;

use App\Models\Product;
use Filament\Notifications\Notification;
use Illuminate\Support\Str;

class ProductObserver
{
    public function updated(Product $product)
    {
        if ($product->stock < 5) {
            // Notifikasi stok hampir habis
            Notification::make()
                ->title('Stok Hampir Habis')
                ->body("Stok produk {$product->name} hampir habis. Silakan periksa stok Anda.")
                ->danger()
                ->send();
        }
    }

    public function creating(Product $product): void
    {
        if (empty($product->barcode)) {
            $product->barcode = Str::random(10); // atau gunakan library barcode
        }
    }
}
