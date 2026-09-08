<?php

namespace Database\Factories;

use App\Models\Debtor;
use App\Models\Entry;
use App\Models\User;
use App\Models\Warung;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Entry>
 */
class EntryFactory extends Factory
{
    protected $model = Entry::class;

    public function definition(): array
    {
        return [
            'warung_id' => Warung::factory(),
            'debtor_id' => Debtor::factory(),
            'type' => 'debt',
            'item_description' => fake()->words(3, true),
            'amount' => fake()->numberBetween(10000, 500000),
            'recorded_by_user_id' => User::factory(),
            'is_voided' => false,
        ];
    }

    public function payment(): static
    {
        return $this->state(fn () => [
            'type' => 'payment',
            'item_description' => null,
        ]);
    }

    public function voided(): static
    {
        return $this->state(fn () => [
            'is_voided' => true,
            'voided_by_user_id' => User::factory(),
            'voided_at' => now(),
        ]);
    }
}
