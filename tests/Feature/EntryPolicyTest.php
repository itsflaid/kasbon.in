<?php

use App\Models\Debtor;
use App\Models\Entry;
use App\Models\User;
use App\Models\Warung;
use App\Models\WarungMember;
use Illuminate\Support\Facades\Gate;

function createEntryPolicyTestContext(): array
{
    $owner = User::factory()->create();
    $warung = Warung::factory()->create(['owner_id' => $owner->id]);
    WarungMember::factory()->create([
        'warung_id' => $warung->id,
        'user_id' => $owner->id,
        'role' => 'owner',
        'can_edit_any_entry' => true,
    ]);

    $recorder = User::factory()->create();
    WarungMember::factory()->create([
        'warung_id' => $warung->id,
        'user_id' => $recorder->id,
        'role' => 'staff',
        'can_edit_any_entry' => false,
    ]);

    $trustedEditor = User::factory()->create();
    $trustedMember = WarungMember::factory()->create([
        'warung_id' => $warung->id,
        'user_id' => $trustedEditor->id,
        'role' => 'staff',
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
        'recorded_by_user_id' => $recorder->id,
    ]);

    return compact(
        'owner',
        'warung',
        'recorder',
        'trustedEditor',
        'trustedMember',
        'staff',
        'entry',
    );
}

it('allows the owner to update any entry', function () {
    ['owner' => $owner, 'entry' => $entry] = createEntryPolicyTestContext();

    expect(Gate::forUser($owner)->allows('update', $entry))->toBeTrue();
});

it('allows the recorder to update their own entry', function () {
    ['recorder' => $recorder, 'entry' => $entry] = createEntryPolicyTestContext();

    expect(Gate::forUser($recorder)->allows('update', $entry))->toBeTrue();
});

it('allows a trusted editor to update another member entry', function () {
    ['trustedEditor' => $trustedEditor, 'entry' => $entry] = createEntryPolicyTestContext();

    expect(Gate::forUser($trustedEditor)->allows('update', $entry))->toBeTrue();
});

it('forbids an ordinary staff member from updating another member entry', function () {
    ['staff' => $staff, 'entry' => $entry] = createEntryPolicyTestContext();

    expect(Gate::forUser($staff)->allows('update', $entry))->toBeFalse();
});

it('forbids a kicked trusted editor from updating an entry', function () {
    ['trustedEditor' => $trustedEditor, 'trustedMember' => $trustedMember, 'entry' => $entry] = createEntryPolicyTestContext();

    $trustedMember->update(['is_active' => false]);

    expect(Gate::forUser($trustedEditor)->allows('update', $entry))->toBeFalse();
});

it('forbids a user outside the warung from updating an entry', function () {
    ['entry' => $entry] = createEntryPolicyTestContext();

    expect(Gate::forUser(User::factory()->create())->allows('update', $entry))->toBeFalse();
});
