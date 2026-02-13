<?php

namespace App\Services;

use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\StockHistory;
use Illuminate\Support\Facades\Auth;

class StockService
{
    public function addStock(
        Product $product,
        int $quantity,
        StockMovementType $type = StockMovementType::In,
        ?object $reference = null,
        ?string $note = null
    ): StockHistory {
        $stockBefore = $product->stock;
        $stockAfter = $stockBefore + $quantity;

        $product->update(['stock' => $stockAfter]);

        return $this->createHistory(
            $product,
            $type,
            $quantity,
            $stockBefore,
            $stockAfter,
            $reference,
            $note
        );
    }

    public function subtractStock(
        Product $product,
        int $quantity,
        StockMovementType $type = StockMovementType::Out,
        ?object $reference = null,
        ?string $note = null
    ): StockHistory {
        $stockBefore = $product->stock;
        $stockAfter = $stockBefore - $quantity;

        $product->update(['stock' => $stockAfter]);

        return $this->createHistory(
            $product,
            $type,
            -$quantity,
            $stockBefore,
            $stockAfter,
            $reference,
            $note
        );
    }

    public function setStock(
        Product $product,
        int $newStock,
        StockMovementType $type = StockMovementType::Opname,
        ?object $reference = null,
        ?string $note = null
    ): StockHistory {
        $stockBefore = $product->stock;
        $difference = $newStock - $stockBefore;

        $product->update(['stock' => $newStock]);

        return $this->createHistory(
            $product,
            $type,
            $difference,
            $stockBefore,
            $newStock,
            $reference,
            $note
        );
    }

    public function adjustStock(
        Product $product,
        int $quantity,
        bool $isIncrease,
        ?object $reference = null,
        ?string $note = null
    ): StockHistory {
        if ($isIncrease) {
            return $this->addStock($product, $quantity, StockMovementType::Adjustment, $reference, $note);
        }

        return $this->subtractStock($product, $quantity, StockMovementType::Adjustment, $reference, $note);
    }

    public function isLowStock(Product $product): bool
    {
        return $product->stock <= $product->low_stock_threshold;
    }

    public function getLowStockProducts(): \Illuminate\Database\Eloquent\Collection
    {
        return Product::whereColumn('stock', '<=', 'low_stock_threshold')
            ->where('is_active', true)
            ->orderBy('stock')
            ->get();
    }

    protected function createHistory(
        Product $product,
        StockMovementType $type,
        int $quantity,
        int $stockBefore,
        int $stockAfter,
        ?object $reference = null,
        ?string $note = null
    ): StockHistory {
        return StockHistory::create([
            'product_id' => $product->id,
            'type' => $type,
            'quantity' => $quantity,
            'stock_before' => $stockBefore,
            'stock_after' => $stockAfter,
            'reference_type' => $reference ? get_class($reference) : null,
            'reference_id' => $reference?->id,
            'note' => $note,
            'user_id' => Auth::id(),
        ]);
    }
}
