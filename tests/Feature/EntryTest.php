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

it('rejects creating an entry in a warung the user is not a member of', function () {
    ['owner' => $owner, 'warung' => $warung] = createWarungWithOwner();
    $debtor = Debtor::factory()->create(['warung_id' => $warung->id]);

    $outsider = User::factory()->create();
    $outsiderWarung = Warung::factory()->create(['owner_id' => $outsider->id]);
    WarungMember::factory()->create([
        'warung_id' => $outsiderWarung->id,
        'user_id' => $outsider->id,
        'role' => 'owner',
    ]);

    $this->actingAs($outsider)
        ->withSession(['active_warung_id' => $outsiderWarung->id])
        ->postJson(route('entries.store', $warung), [
            'debtor_id' => $debtor->id,
            'type' => 'debt',
            'item_description' => 'Mie Ayam',
            'amount' => 15000,
        ])
        ->assertForbidden();

    $this->assertDatabaseCount('entries', 0);
});

it('rejects creating an entry with a debtor from another warung', function () {
    ['owner' => $owner, 'warung' => $warung] = createWarungWithOwner();

    $otherWarung = Warung::factory()->create();
    $debtor = Debtor::factory()->create(['warung_id' => $otherWarung->id]);

    $this->actingAs($owner)
        ->postJson(route('entries.store', $warung), [
            'debtor_id' => $debtor->id,
            'type' => 'debt',
            'item_description' => 'Mie Ayam',
            'amount' => 15000,
        ])
        ->assertNotFound();

    $this->assertDatabaseCount('entries', 0);
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

it('creates a payment after overpayment confirmation', function () {
    ['owner' => $owner, 'warung' => $warung] = createWarungWithOwner();
    $debtor = Debtor::factory()->create(['warung_id' => $warung->id]);

    Entry::factory()->create([
        'warung_id' => $warung->id,
        'debtor_id' => $debtor->id,
        'type' => 'debt',
        'amount' => 50000,
        'recorded_by_user_id' => $owner->id,
    ]);

    $this->actingAs($owner)
        ->postJson(route('entries.confirm-store', $warung), [
            'debtor_id' => $debtor->id,
            'type' => 'payment',
            'amount' => 100000,
        ])
        ->assertCreated();

    $this->assertDatabaseHas('entries', [
        'warung_id' => $warung->id,
        'debtor_id' => $debtor->id,
        'type' => 'payment',
        'amount' => 100000,
    ]);
});

it('rejects confirming an entry with a debtor from another warung', function () {
    ['owner' => $owner, 'warung' => $warung] = createWarungWithOwner();

    $otherWarung = Warung::factory()->create();
    $debtor = Debtor::factory()->create(['warung_id' => $otherWarung->id]);

    $this->actingAs($owner)
        ->postJson(route('entries.confirm-store', $warung), [
            'debtor_id' => $debtor->id,
            'type' => 'debt',
            'item_description' => 'Mie Ayam',
            'amount' => 15000,
        ])
        ->assertNotFound();

    $this->assertDatabaseCount('entries', 0);
});

it('rejects a debt without an item description', function () {
    ['owner' => $owner, 'warung' => $warung] = createWarungWithOwner();
    $debtor = Debtor::factory()->create(['warung_id' => $warung->id]);

    $this->actingAs($owner)
        ->postJson(route('entries.store', $warung), [
            'debtor_id' => $debtor->id,
            'type' => 'debt',
            'amount' => 15000,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('item_description');
});

it('rejects a payment without an amount', function () {
    ['owner' => $owner, 'warung' => $warung] = createWarungWithOwner();
    $debtor = Debtor::factory()->create(['warung_id' => $warung->id]);

    $this->actingAs($owner)
        ->postJson(route('entries.store', $warung), [
            'debtor_id' => $debtor->id,
            'type' => 'payment',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('amount');
});

it('rejects an update without an amount', function () {
    ['owner' => $owner, 'warung' => $warung] = createWarungWithOwner();
    $debtor = Debtor::factory()->create(['warung_id' => $warung->id]);
    $entry = Entry::factory()->create([
        'warung_id' => $warung->id,
        'debtor_id' => $debtor->id,
        'recorded_by_user_id' => $owner->id,
    ]);

    $this->actingAs($owner)
        ->putJson(route('entries.update', $entry), [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('amount');
});

it('rejects voiding an entry from an ordinary staff member', function () {
    ['owner' => $owner, 'warung' => $warung] = createWarungWithOwner();
    $staff = createStaff($warung);
    $debtor = Debtor::factory()->create(['warung_id' => $warung->id]);
    $entry = Entry::factory()->create([
        'warung_id' => $warung->id,
        'debtor_id' => $debtor->id,
        'recorded_by_user_id' => $owner->id,
    ]);

    $this->actingAs($staff)
        ->postJson(route('entries.void', $entry))
        ->assertForbidden();

    $this->assertDatabaseHas('entries', [
        'id' => $entry->id,
        'is_voided' => false,
    ]);
});

it('logs activity when an entry is updated', function () {
    ['owner' => $owner, 'warung' => $warung] = createWarungWithOwner();
    $debtor = Debtor::factory()->create(['warung_id' => $warung->id]);
    $entry = Entry::factory()->create([
        'warung_id' => $warung->id,
        'debtor_id' => $debtor->id,
        'amount' => 10000,
        'recorded_by_user_id' => $owner->id,
    ]);

    $this->actingAs($owner)
        ->putJson(route('entries.update', $entry), ['amount' => 25000])
        ->assertOk();

    $this->assertDatabaseHas('activity_logs', [
        'warung_id' => $warung->id,
        'entry_id' => $entry->id,
        'user_id' => $owner->id,
        'action' => 'updated_entry',
    ]);
});
