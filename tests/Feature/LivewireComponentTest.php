<?php

use App\Livewire\Dashboard;
use App\Models\Debtor;
use App\Models\Entry;
use App\Models\User;
use App\Models\Warung;
use App\Models\WarungMember;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('shows incomplete price badge when amount is null', function () {
    $owner = User::factory()->create();
    $warung = Warung::factory()->create(['owner_id' => $owner->id]);
    WarungMember::factory()->create([
        'warung_id' => $warung->id,
        'user_id' => $owner->id,
        'role' => 'owner',
        'can_edit_any_entry' => true,
    ]);
    session(['active_warung_id' => $warung->id]);

    $debtor = Debtor::factory()->create(['warung_id' => $warung->id]);
    Entry::factory()->create([
        'warung_id' => $warung->id,
        'debtor_id' => $debtor->id,
        'type' => 'debt',
        'amount' => null,
        'recorded_by_user_id' => $owner->id,
    ]);

    Livewire::actingAs($owner)
        ->test(Dashboard::class, ['warung' => $warung])
        ->assertSee('belum diisi');
});

it('shows total when all prices are complete', function () {
    $owner = User::factory()->create();
    $warung = Warung::factory()->create(['owner_id' => $owner->id]);
    WarungMember::factory()->create([
        'warung_id' => $warung->id,
        'user_id' => $owner->id,
        'role' => 'owner',
        'can_edit_any_entry' => true,
    ]);
    session(['active_warung_id' => $warung->id]);

    $debtor = Debtor::factory()->create(['warung_id' => $warung->id]);
    Entry::factory()->create([
        'warung_id' => $warung->id,
        'debtor_id' => $debtor->id,
        'type' => 'debt',
        'amount' => 50000,
        'recorded_by_user_id' => $owner->id,
    ]);

    Livewire::actingAs($owner)
        ->test(Dashboard::class, ['warung' => $warung])
        ->assertSee('Rp')
        ->assertDontSee('belum diisi');
});

it('dashboard filters debtors by search', function () {
    $owner = User::factory()->create();
    $warung = Warung::factory()->create(['owner_id' => $owner->id]);
    WarungMember::factory()->create([
        'warung_id' => $warung->id,
        'user_id' => $owner->id,
        'role' => 'owner',
        'can_edit_any_entry' => true,
    ]);
    session(['active_warung_id' => $warung->id]);

    Debtor::factory()->create(['warung_id' => $warung->id, 'name' => 'Budi']);
    Debtor::factory()->create(['warung_id' => $warung->id, 'name' => 'Andi']);

    Livewire::actingAs($owner)
        ->test(Dashboard::class, ['warung' => $warung])
        ->assertSee('Budi')
        ->assertSee('Andi')
        ->set('search', 'Budi')
        ->assertSee('Budi')
        ->assertDontSee('Andi');
});
