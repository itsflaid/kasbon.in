<?php

use App\Models\Debtor;
use App\Models\Entry;
use App\Models\User;
use App\Models\Warung;
use App\Models\WarungMember;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('hides total when incomplete price exists, shows after filled', function () {
    $owner = User::factory()->create();
    $warung = Warung::factory()->create(['owner_id' => $owner->id]);
    WarungMember::factory()->create([
        'warung_id' => $warung->id,
        'user_id' => $owner->id,
        'role' => 'owner',
        'can_edit_any_entry' => true,
    ]);
    $debtor = Debtor::factory()->create(['warung_id' => $warung->id]);

    Entry::factory()->create([
        'warung_id' => $warung->id,
        'debtor_id' => $debtor->id,
        'type' => 'debt',
        'amount' => null,
        'recorded_by_user_id' => $owner->id,
    ]);

    expect($debtor->hasIncompletePrice())->toBeTrue();

    $entry = $debtor->entries()->first();
    $entry->update(['amount' => 25000]);

    $debtor->forgetTotalCache();
    expect($debtor->fresh()->hasIncompletePrice())->toBeFalse();
    expect($debtor->fresh()->total())->toBe(25000);
});

it('keeps kicked member history visible', function () {
    $owner = User::factory()->create();
    $warung = Warung::factory()->create(['owner_id' => $owner->id]);
    WarungMember::factory()->create([
        'warung_id' => $warung->id,
        'user_id' => $owner->id,
        'role' => 'owner',
        'can_edit_any_entry' => true,
    ]);

    $staff = User::factory()->create();
    WarungMember::factory()->create([
        'warung_id' => $warung->id,
        'user_id' => $staff->id,
        'role' => 'staff',
        'is_active' => true,
    ]);

    $debtor = Debtor::factory()->create(['warung_id' => $warung->id]);
    $entry = Entry::factory()->create([
        'warung_id' => $warung->id,
        'debtor_id' => $debtor->id,
        'recorded_by_user_id' => $staff->id,
    ]);

    $warung->members()->where('user_id', $staff->id)->update(['is_active' => false]);

    expect(Entry::where('recorded_by_user_id', $staff->id)->count())->toBe(1);
    expect($entry->fresh()->recorded_by_user_id)->toBe($staff->id);
});

it('total reflects voided entries correctly', function () {
    $owner = User::factory()->create();
    $warung = Warung::factory()->create(['owner_id' => $owner->id]);
    WarungMember::factory()->create([
        'warung_id' => $warung->id,
        'user_id' => $owner->id,
        'role' => 'owner',
        'can_edit_any_entry' => true,
    ]);
    $debtor = Debtor::factory()->create(['warung_id' => $warung->id]);

    Entry::factory()->create([
        'warung_id' => $warung->id,
        'debtor_id' => $debtor->id,
        'type' => 'debt',
        'amount' => 50000,
        'recorded_by_user_id' => $owner->id,
    ]);

    expect($debtor->fresh()->total())->toBe(50000);

    $entry = $debtor->entries()->first();
    $entry->update(['is_voided' => true]);

    $debtor->forgetTotalCache();
    expect($debtor->fresh()->total())->toBe(0);
});

it('total subtracts payments from debts', function () {
    $owner = User::factory()->create();
    $warung = Warung::factory()->create(['owner_id' => $owner->id]);
    WarungMember::factory()->create([
        'warung_id' => $warung->id,
        'user_id' => $owner->id,
        'role' => 'owner',
        'can_edit_any_entry' => true,
    ]);
    $debtor = Debtor::factory()->create(['warung_id' => $warung->id]);

    Entry::factory()->create([
        'warung_id' => $warung->id,
        'debtor_id' => $debtor->id,
        'type' => 'debt',
        'amount' => 100000,
        'recorded_by_user_id' => $owner->id,
    ]);

    Entry::factory()->create([
        'warung_id' => $warung->id,
        'debtor_id' => $debtor->id,
        'type' => 'payment',
        'amount' => 30000,
        'recorded_by_user_id' => $owner->id,
    ]);

    expect($debtor->fresh()->total())->toBe(70000);
});

it('trusted editor can edit any entry', function () {
    $owner = User::factory()->create();
    $warung = Warung::factory()->create(['owner_id' => $owner->id]);
    WarungMember::factory()->create([
        'warung_id' => $warung->id,
        'user_id' => $owner->id,
        'role' => 'owner',
        'can_edit_any_entry' => true,
    ]);

    $trustedEditor = User::factory()->create();
    WarungMember::factory()->create([
        'warung_id' => $warung->id,
        'user_id' => $trustedEditor->id,
        'role' => 'staff',
        'can_edit_any_entry' => true,
    ]);

    $recorder = User::factory()->create();
    WarungMember::factory()->create([
        'warung_id' => $warung->id,
        'user_id' => $recorder->id,
        'role' => 'staff',
        'can_edit_any_entry' => false,
    ]);

    $debtor = Debtor::factory()->create(['warung_id' => $warung->id]);
    $entry = Entry::factory()->create([
        'warung_id' => $warung->id,
        'debtor_id' => $debtor->id,
        'amount' => 10000,
        'recorded_by_user_id' => $recorder->id,
    ]);

    $response = $this->actingAs($trustedEditor)->putJson(route('entries.update', $entry), [
        'amount' => 20000,
    ]);

    $response->assertOk();
    $this->assertDatabaseHas('entries', [
        'id' => $entry->id,
        'amount' => 20000,
    ]);
});

it('recorder can edit their own entry', function () {
    $owner = User::factory()->create();
    $warung = Warung::factory()->create(['owner_id' => $owner->id]);
    WarungMember::factory()->create([
        'warung_id' => $warung->id,
        'user_id' => $owner->id,
        'role' => 'owner',
        'can_edit_any_entry' => true,
    ]);

    $staff = User::factory()->create();
    WarungMember::factory()->create([
        'warung_id' => $warung->id,
        'user_id' => $staff->id,
        'role' => 'staff',
        'can_edit_any_entry' => false,
    ]);

    $debtor = Debtor::factory()->create(['warung_id' => $warung->id]);
    $entry = Entry::factory()->create([
        'warung_id' => $warung->id,
        'debtor_id' => $debtor->id,
        'amount' => 10000,
        'recorded_by_user_id' => $staff->id,
    ]);

    $response = $this->actingAs($staff)->putJson(route('entries.update', $entry), [
        'amount' => 15000,
    ]);

    $response->assertOk();
    $this->assertDatabaseHas('entries', [
        'id' => $entry->id,
        'amount' => 15000,
    ]);
});
