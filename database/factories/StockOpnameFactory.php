<?php

namespace Database\Factories;

use App\Enums\OpnameStatus;
use App\Models\StockOpname;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class StockOpnameFactory extends Factory
{
    protected $model = StockOpname::class;

    public function definition(): array
    {
        return [
            'opname_number' => 'OPN-'.str_pad(fake()->unique()->numberBetween(1, 99999), 5, '0', STR_PAD_LEFT),
            'status' => OpnameStatus::Draft,
            'opname_date' => fake()->date(),
            'notes' => fake()->optional()->sentence(),
            'user_id' => User::factory(),
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OpnameStatus::Completed,
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OpnameStatus::Cancelled,
        ]);
    }
}
