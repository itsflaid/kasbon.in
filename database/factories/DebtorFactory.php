<?php

namespace Database\Factories;

use App\Models\Debtor;
use App\Models\Warung;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Debtor>
 */
class DebtorFactory extends Factory
{
    protected $model = Debtor::class;

    public function definition(): array
    {
        $name = fake()->name();

        return [
            'warung_id' => Warung::factory(),
            'name' => $name,
            'normalized_name' => strtolower(trim($name)),
            'note' => null,
        ];
    }
}
