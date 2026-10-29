<?php

use App\Livewire\DebtorDetail;
use App\Models\Debtor;
use App\Models\Entry;
use App\Models\User;
use App\Models\Warung;
use App\Models\WarungMember;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function createDebtorDetailContext(): array
{
    $owner = User::factory()->create();
    $warung = Warung::factory()->create(['owner_id' => $owner->id]);
    WarungMember::factory()->create([
        'warung_id' => $warung->id,
        'user_id' => $owner->id,
        'role' => 'owner',
        'can_edit_any_entry' => true,
    ]);
    $debtor = Debtor::factory()->create(['warung_id' => $warung->id]);

    session(['active_warung_id' => $warung->id]);

    return compact('owner', 'warung', 'debtor');
}

it('rejects viewing a debtor from another warung', function () {
    ['debtor' => $debtor] = createDebtorDetailContext();

    $other = User::factory()->create();
    $otherWarung = Warung::factory()->create(['owner_id' => $other->id]);
    WarungMember::factory()->create([
        'warung_id' => $otherWarung->id,
        'user_id' => $other->id,
        'role' => 'owner',
    ]);

    $this->actingAs($other)
        ->withSession(['active_warung_id' => $otherWarung->id])
        ->get(route('debtor.detail', $debtor))
        ->assertNotFound();
});

it('opens entry modal with the debtor and type preselected', function () {
    ['owner' => $owner, 'debtor' => $debtor] = createDebtorDetailContext();

    Livewire::actingAs($owner)
        ->test(DebtorDetail::class, ['debtor' => $debtor])
        ->call('openEntryModal', 'payment')
        ->assertSet('showEntryModal', true)
        ->assertSet('entryType', 'payment');
});

it('allows filling an empty price from the debtor detail page', function () {
    ['owner' => $owner, 'debtor' => $debtor] = createDebtorDetailContext();

    $entry = Entry::factory()->create([
        'warung_id' => $debtor->warung_id,
        'debtor_id' => $debtor->id,
        'amount' => null,
        'recorded_by_user_id' => $owner->id,
    ]);

    Livewire::actingAs($owner)
        ->test(DebtorDetail::class, ['debtor' => $debtor])
        ->call('editEntry', $entry->id)
        ->assertSet('showEditModal', true)
        ->set('editAmount', 25000)
        ->call('saveEntry')
        ->assertHasNoErrors()
        ->assertSet('showEditModal', false);

    $this->assertDatabaseHas('entries', [
        'id' => $entry->id,
        'amount' => 25000,
        'edited_by_user_id' => $owner->id,
    ]);
});

it('voids an entry from the debtor detail page', function () {
    ['owner' => $owner, 'debtor' => $debtor] = createDebtorDetailContext();

    $entry = Entry::factory()->create([
        'warung_id' => $debtor->warung_id,
        'debtor_id' => $debtor->id,
        'recorded_by_user_id' => $owner->id,
    ]);

    Livewire::actingAs($owner)
        ->test(DebtorDetail::class, ['debtor' => $debtor])
        ->call('voidEntry', $entry->id);

    $this->assertDatabaseHas('entries', [
        'id' => $entry->id,
        'is_voided' => true,
        'voided_by_user_id' => $owner->id,
    ]);
});

it('rejects opening an invalid entry type', function () {
    ['owner' => $owner, 'debtor' => $debtor] = createDebtorDetailContext();

    Livewire::actingAs($owner)
        ->test(DebtorDetail::class, ['debtor' => $debtor])
        ->call('openEntryModal', 'refund')
        ->assertStatus(400);
});

it('rejects editing an entry from another debtor', function () {
    ['owner' => $owner, 'debtor' => $debtor] = createDebtorDetailContext();

    $otherDebtor = Debtor::factory()->create(['warung_id' => $debtor->warung_id]);
    $entry = Entry::factory()->create([
        'warung_id' => $debtor->warung_id,
        'debtor_id' => $otherDebtor->id,
        'recorded_by_user_id' => $owner->id,
    ]);

    expect(fn () => Livewire::actingAs($owner)
        ->test(DebtorDetail::class, ['debtor' => $debtor])
        ->call('editEntry', $entry->id))
        ->toThrow(ModelNotFoundException::class);
});

it('forbids an ordinary staff member from editing another member entry', function () {
    ['owner' => $owner, 'debtor' => $debtor] = createDebtorDetailContext();

    $staff = User::factory()->create();
    WarungMember::factory()->create([
        'warung_id' => $debtor->warung_id,
        'user_id' => $staff->id,
        'role' => 'staff',
        'can_edit_any_entry' => false,
    ]);

    $entry = Entry::factory()->create([
        'warung_id' => $debtor->warung_id,
        'debtor_id' => $debtor->id,
        'recorded_by_user_id' => $owner->id,
    ]);

    Livewire::actingAs($staff)
        ->test(DebtorDetail::class, ['debtor' => $debtor])
        ->call('editEntry', $entry->id)
        ->assertForbidden();
});

it('only shows active entries on the debtor detail page', function () {
    ['owner' => $owner, 'debtor' => $debtor] = createDebtorDetailContext();

    Entry::factory()->create([
        'warung_id' => $debtor->warung_id,
        'debtor_id' => $debtor->id,
        'item_description' => 'Catatan Aktif',
        'recorded_by_user_id' => $owner->id,
    ]);
    Entry::factory()->voided()->create([
        'warung_id' => $debtor->warung_id,
        'debtor_id' => $debtor->id,
        'item_description' => 'Catatan Dibatalkan',
        'recorded_by_user_id' => $owner->id,
    ]);

    Livewire::actingAs($owner)
        ->test(DebtorDetail::class, ['debtor' => $debtor])
        ->assertSee('Catatan Aktif')
        ->assertDontSee('Catatan Dibatalkan');
});
