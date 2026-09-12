<?php

use App\Models\User;
use App\Models\Warung;
use App\Models\WarungMember;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('allows active warung member on broadcast channel', function () {
    $user = User::factory()->create();
    $warung = Warung::factory()->create(['owner_id' => $user->id]);
    WarungMember::factory()->create([
        'warung_id' => $warung->id,
        'user_id' => $user->id,
        'role' => 'owner',
    ]);

    $exists = WarungMember::where('warung_id', $warung->id)
        ->where('user_id', $user->id)
        ->where('is_active', true)
        ->exists();

    expect($exists)->toBeTrue();
});

it('rejects non-member from broadcast channel', function () {
    $user = User::factory()->create();
    $warung = Warung::factory()->create();

    $exists = WarungMember::where('warung_id', $warung->id)
        ->where('user_id', $user->id)
        ->where('is_active', true)
        ->exists();

    expect($exists)->toBeFalse();
});

it('rejects kicked member from broadcast channel', function () {
    $user = User::factory()->create();
    $warung = Warung::factory()->create(['owner_id' => $user->id]);
    WarungMember::factory()->create([
        'warung_id' => $warung->id,
        'user_id' => $user->id,
        'role' => 'staff',
        'is_active' => false,
    ]);

    $exists = WarungMember::where('warung_id', $warung->id)
        ->where('user_id', $user->id)
        ->where('is_active', true)
        ->exists();

    expect($exists)->toBeFalse();
});
