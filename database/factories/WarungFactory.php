<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Warung;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Warung>
 */
class WarungFactory extends Factory
{
    protected $model = Warung::class;

    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'owner_id' => User::factory(),
            'invite_code' => strtoupper(Str::random(8)),
        ];
    }
}
