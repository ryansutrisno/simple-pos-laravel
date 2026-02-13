<?php

namespace Database\Factories;

use App\Models\FinancialRecord;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;

class FinancialRecordFactory extends Factory
{
    protected $model = FinancialRecord::class;

    public function definition(): array
    {
        $amount = fake()->numberBetween(10000, 500000);

        return [
            'type' => 'sales',
            'amount' => $amount,
            'profit' => fake()->numberBetween(1000, (int) ($amount / 2)),
            'transaction_id' => Transaction::factory(),
            'description' => 'Penjualan produk',
            'record_date' => fake()->date(),
        ];
    }

    public function sales(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'sales',
        ]);
    }

    public function expense(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'expense',
            'profit' => null,
            'transaction_id' => null,
            'description' => 'Pengeluaran operasional',
        ]);
    }

    public function purchase(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'purchase',
            'profit' => null,
            'transaction_id' => null,
            'description' => 'Pembelian stok',
        ]);
    }
}
