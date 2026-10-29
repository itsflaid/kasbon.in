<?php

use App\Livewire\Profil;
use App\Models\User;
use App\Models\Warung;
use App\Models\WarungMember;
use Livewire\Livewire;

it('shows the authenticated user and their warungs', function () {
    $user = User::factory()->create([
        'name' => 'Budi Santoso',
        'email' => 'budi@example.com',
    ]);

    $warung = Warung::factory()->create(['owner_id' => $user->id]);
    WarungMember::factory()->create([
        'warung_id' => $warung->id,
        'user_id' => $user->id,
        'role' => 'owner',
        'can_edit_any_entry' => true,
    ]);

    session(['active_warung_id' => $warung->id]);

    Livewire::actingAs($user)
        ->test(Profil::class)
        ->assertSee('Budi Santoso')
        ->assertSee('budi@example.com')
        ->assertSee($warung->name)
        ->assertSee('Aktif');
});

it('does not show warungs the user has not joined', function () {
    $user = User::factory()->create();

    $otherOwner = User::factory()->create();
    $otherWarung = Warung::factory()->create([
        'owner_id' => $otherOwner->id,
        'name' => 'Warung Orang Lain',
    ]);
    WarungMember::factory()->create([
        'warung_id' => $otherWarung->id,
        'user_id' => $otherOwner->id,
        'role' => 'owner',
    ]);

    Livewire::actingAs($user)
        ->test(Profil::class)
        ->assertDontSee('Warung Orang Lain');
});

it('logs the user out and redirects home', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(Profil::class)
        ->call('logout')
        ->assertRedirect(route('home'));

    $this->assertGuest();
});
