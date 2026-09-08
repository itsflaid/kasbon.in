<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Warung;
use App\Models\WarungMember;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WarungMember>
 */
class WarungMemberFactory extends Factory
{
    protected $model = WarungMember::class;

    public function definition(): array
    {
        return [
            'warung_id' => Warung::factory(),
            'user_id' => User::factory(),
            'role' => 'staff',
            'can_edit_any_entry' => false,
            'is_active' => true,
        ];
    }
}
