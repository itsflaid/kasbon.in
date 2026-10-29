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

it('dashboard total subtracts payments from debts', function () {
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
        'amount' => 100000,
        'recorded_by_user_id' => $owner->id,
    ]);
    Entry::factory()->payment()->create([
        'warung_id' => $warung->id,
        'debtor_id' => $debtor->id,
        'amount' => 30000,
        'recorded_by_user_id' => $owner->id,
    ]);

    Livewire::actingAs($owner)
        ->test(Dashboard::class, ['warung' => $warung])
        ->assertSee('Rp 70.000');
});

it('closes the entry modal when the child dispatches close-modal', function () {
    $owner = User::factory()->create();
    $warung = Warung::factory()->create(['owner_id' => $owner->id]);
    WarungMember::factory()->create([
        'warung_id' => $warung->id,
        'user_id' => $owner->id,
        'role' => 'owner',
        'can_edit_any_entry' => true,
    ]);
    session(['active_warung_id' => $warung->id]);

    Livewire::actingAs($owner)
        ->test(Dashboard::class, ['warung' => $warung])
        ->set('showModal', true)
        ->dispatch('close-modal')
        ->assertSet('showModal', false);
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

it('dashboard filters debtors by payment status', function () {
    $owner = User::factory()->create();
    $warung = Warung::factory()->create(['owner_id' => $owner->id]);
    WarungMember::factory()->create([
        'warung_id' => $warung->id,
        'user_id' => $owner->id,
        'role' => 'owner',
        'can_edit_any_entry' => true,
    ]);
    session(['active_warung_id' => $warung->id]);

    $unpaid = Debtor::factory()->create(['warung_id' => $warung->id, 'name' => 'Budi']);
    $paid = Debtor::factory()->create(['warung_id' => $warung->id, 'name' => 'Siti']);
    $incomplete = Debtor::factory()->create(['warung_id' => $warung->id, 'name' => 'Andi']);

    Entry::factory()->create([
        'warung_id' => $warung->id,
        'debtor_id' => $unpaid->id,
        'amount' => 50000,
        'recorded_by_user_id' => $owner->id,
    ]);
    Entry::factory()->create([
        'warung_id' => $warung->id,
        'debtor_id' => $paid->id,
        'amount' => 50000,
        'recorded_by_user_id' => $owner->id,
    ]);
    Entry::factory()->payment()->create([
        'warung_id' => $warung->id,
        'debtor_id' => $paid->id,
        'amount' => 50000,
        'recorded_by_user_id' => $owner->id,
    ]);
    Entry::factory()->create([
        'warung_id' => $warung->id,
        'debtor_id' => $incomplete->id,
        'amount' => null,
        'recorded_by_user_id' => $owner->id,
    ]);

    Livewire::actingAs($owner)
        ->test(Dashboard::class, ['warung' => $warung])
        ->set('filter', 'belum_lunas')
        ->assertSee('Budi')
        ->assertDontSee('Siti')
        ->assertDontSee('Andi')
        ->set('filter', 'lunas')
        ->assertSee('Siti')
        ->assertDontSee('Budi')
        ->assertDontSee('Andi');
});

it('dashboard ignores voided entries when calculating the total', function () {
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
        'amount' => 50000,
        'recorded_by_user_id' => $owner->id,
    ]);
    Entry::factory()->voided()->create([
        'warung_id' => $warung->id,
        'debtor_id' => $debtor->id,
        'amount' => 100000,
        'recorded_by_user_id' => $owner->id,
    ]);
    Entry::factory()->payment()->create([
        'warung_id' => $warung->id,
        'debtor_id' => $debtor->id,
        'amount' => 10000,
        'recorded_by_user_id' => $owner->id,
    ]);
    Entry::factory()->payment()->voided()->create([
        'warung_id' => $warung->id,
        'debtor_id' => $debtor->id,
        'amount' => 5000,
        'recorded_by_user_id' => $owner->id,
    ]);

    Livewire::actingAs($owner)
        ->test(Dashboard::class, ['warung' => $warung])
        ->assertSee('Rp 40.000');
});

it('dashboard opens the entry modal', function () {
    $owner = User::factory()->create();
    $warung = Warung::factory()->create(['owner_id' => $owner->id]);
    WarungMember::factory()->create([
        'warung_id' => $warung->id,
        'user_id' => $owner->id,
        'role' => 'owner',
        'can_edit_any_entry' => true,
    ]);
    session(['active_warung_id' => $warung->id]);

    Livewire::actingAs($owner)
        ->test(Dashboard::class, ['warung' => $warung])
        ->call('openModal')
        ->assertSet('showModal', true);
});
