<?php

use App\Models\Debtor;
use App\Models\Entry;
use App\Models\User;
use App\Models\Warung;
use App\Models\WarungMember;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createWarungWithOwner(): array
{
    $owner = User::factory()->create();
    $warung = Warung::factory()->create(['owner_id' => $owner->id]);
    $member = WarungMember::factory()->create([
        'warung_id' => $warung->id,
        'user_id' => $owner->id,
        'role' => 'owner',
        'can_edit_any_entry' => true,
    ]);

    return compact('owner', 'warung', 'member');
}

function createStaff(Warung $warung, bool $canEdit = false): User
{
    $staff = User::factory()->create();
    WarungMember::factory()->create([
        'warung_id' => $warung->id,
        'user_id' => $staff->id,
        'role' => 'staff',
        'can_edit_any_entry' => $canEdit,
    ]);

    return $staff;
}

it('stores debt without amount', function () {
    $owner = User::factory()->create();
    $warung = Warung::factory()->create(['owner_id' => $owner->id]);
    WarungMember::factory()->create([
        'warung_id' => $warung->id,
        'user_id' => $owner->id,
        'role' => 'owner',
        'can_edit_any_entry' => true,
    ]);
    $debtor = Debtor::factory()->create(['warung_id' => $warung->id]);

    $response = $this->actingAs($owner)->postJson(route('entries.store', $warung), [
        'debtor_id' => $debtor->id,
        'type' => 'debt',
        'item_description' => 'Mie Ayam',
    ]);

    $response->assertCreated();
    $this->assertDatabaseHas('entries', [
        'warung_id' => $warung->id,
        'debtor_id' => $debtor->id,
        'type' => 'debt',
        'item_description' => 'Mie Ayam',
        'amount' => null,
    ]);
});

it('fills in empty price from another user', function () {
    $owner = User::factory()->create();
    $warung = Warung::factory()->create(['owner_id' => $owner->id]);
    WarungMember::factory()->create([
        'warung_id' => $warung->id,
        'user_id' => $owner->id,
        'role' => 'owner',
        'can_edit_any_entry' => true,
    ]);
    $staff = createStaff($warung, true);
    $debtor = Debtor::factory()->create(['warung_id' => $warung->id]);

    $recorder = User::factory()->create();
    WarungMember::factory()->create([
        'warung_id' => $warung->id,
        'user_id' => $recorder->id,
        'role' => 'staff',
        'can_edit_any_entry' => false,
    ]);

    $entry = Entry::factory()->create([
        'warung_id' => $warung->id,
        'debtor_id' => $debtor->id,
        'amount' => null,
        'recorded_by_user_id' => $recorder->id,
    ]);

    $response = $this->actingAs($staff)->putJson(route('entries.update', $entry), [
        'amount' => 25000,
    ]);

    $response->assertOk();
    $this->assertDatabaseHas('entries', [
        'id' => $entry->id,
        'amount' => 25000,
        'edited_by_user_id' => $staff->id,
    ]);
});

it('rejects edit from unauthorized user with 403', function () {
    $owner = User::factory()->create();
    $warung = Warung::factory()->create(['owner_id' => $owner->id]);
    WarungMember::factory()->create([
        'warung_id' => $warung->id,
        'user_id' => $owner->id,
        'role' => 'owner',
        'can_edit_any_entry' => true,
    ]);

    $staff = createStaff($warung, false);
    $debtor = Debtor::factory()->create(['warung_id' => $warung->id]);

    $recorder = User::factory()->create();
    WarungMember::factory()->create([
        'warung_id' => $warung->id,
        'user_id' => $recorder->id,
        'role' => 'staff',
        'can_edit_any_entry' => false,
    ]);

    $entry = Entry::factory()->create([
        'warung_id' => $warung->id,
        'debtor_id' => $debtor->id,
        'amount' => 10000,
        'recorded_by_user_id' => $recorder->id,
    ]);

    $response = $this->actingAs($staff)->putJson(route('entries.update', $entry), [
        'amount' => 99999,
    ]);

    $response->assertForbidden();
});

it('warns on overpayment before saving', function () {
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

    $response = $this->actingAs($owner)->postJson(route('entries.store', $warung), [
        'debtor_id' => $debtor->id,
        'type' => 'payment',
        'amount' => 100000,
    ]);

    $response->assertOk();
    $response->assertJsonFragment(['warning' => 'Jumlah bayar melebihi sisa utang. Konfirmasi untuk tetap melanjutkan.']);
    $response->assertJsonFragment(['overpayment' => 50000]);
    $this->assertDatabaseCount('entries', 1);
});

it('logs activity on entry creation', function () {
    $owner = User::factory()->create();
    $warung = Warung::factory()->create(['owner_id' => $owner->id]);
    WarungMember::factory()->create([
        'warung_id' => $warung->id,
        'user_id' => $owner->id,
        'role' => 'owner',
        'can_edit_any_entry' => true,
    ]);
    $debtor = Debtor::factory()->create(['warung_id' => $warung->id]);

    $response = $this->actingAs($owner)->postJson(route('entries.store', $warung), [
        'debtor_id' => $debtor->id,
        'type' => 'debt',
        'item_description' => 'Nasi Goreng',
        'amount' => 30000,
    ]);

    $response->assertCreated();
    $this->assertDatabaseHas('activity_logs', [
        'warung_id' => $warung->id,
        'user_id' => $owner->id,
        'action' => 'created_debt',
    ]);
});

it('logs activity on void', function () {
    $owner = User::factory()->create();
    $warung = Warung::factory()->create(['owner_id' => $owner->id]);
    WarungMember::factory()->create([
        'warung_id' => $warung->id,
        'user_id' => $owner->id,
        'role' => 'owner',
        'can_edit_any_entry' => true,
    ]);
    $debtor = Debtor::factory()->create(['warung_id' => $warung->id]);
    $entry = Entry::factory()->create([
        'warung_id' => $warung->id,
        'debtor_id' => $debtor->id,
        'recorded_by_user_id' => $owner->id,
    ]);

    $response = $this->actingAs($owner)->postJson(route('entries.void', $entry));

    $response->assertOk();
    $this->assertDatabaseHas('entries', [
        'id' => $entry->id,
        'is_voided' => true,
        'voided_by_user_id' => $owner->id,
    ]);
    $this->assertDatabaseHas('activity_logs', [
        'warung_id' => $warung->id,
        'user_id' => $owner->id,
        'action' => 'voided_entry',
    ]);
});
