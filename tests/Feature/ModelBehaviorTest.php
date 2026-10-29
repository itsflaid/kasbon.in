<?php

use App\Models\Debtor;
use App\Models\Entry;
use App\Models\Warung;
use App\Models\WarungMember;

it('generates an eight character uppercase invite code when missing', function () {
    $warung = Warung::factory()->create(['invite_code' => null]);

    expect($warung->invite_code)->toMatch('/^[A-Z0-9]{8}$/');
});

it('normalizes the debtor name when saving', function () {
    $debtor = Debtor::factory()->create(['name' => '  Bu Siti  ']);

    expect($debtor->normalized_name)->toBe('bu siti');
});

it('filters entries by active, debt, and payment scopes', function () {
    $warung = Warung::factory()->create();
    $debtor = Debtor::factory()->create(['warung_id' => $warung->id]);

    Entry::factory()->create([
        'warung_id' => $warung->id,
        'debtor_id' => $debtor->id,
        'type' => 'debt',
    ]);
    Entry::factory()->payment()->create([
        'warung_id' => $warung->id,
        'debtor_id' => $debtor->id,
    ]);
    Entry::factory()->voided()->create([
        'warung_id' => $warung->id,
        'debtor_id' => $debtor->id,
    ]);

    expect(Entry::active()->count())->toBe(2)
        ->and(Entry::debts()->count())->toBe(2)
        ->and(Entry::payments()->count())->toBe(1);
});

it('exposes warung member role helpers', function () {
    $owner = WarungMember::factory()->make([
        'role' => 'owner',
        'can_edit_any_entry' => true,
    ]);
    $staff = WarungMember::factory()->make([
        'role' => 'staff',
        'can_edit_any_entry' => false,
    ]);

    expect($owner->isOwner())->toBeTrue()
        ->and($owner->isTrustedEditor())->toBeTrue()
        ->and($staff->isOwner())->toBeFalse()
        ->and($staff->isTrustedEditor())->toBeFalse();
});

it('only returns active members from the active scope', function () {
    $warung = Warung::factory()->create();

    WarungMember::factory()->create([
        'warung_id' => $warung->id,
        'is_active' => true,
    ]);
    WarungMember::factory()->create([
        'warung_id' => $warung->id,
        'is_active' => false,
    ]);

    expect($warung->members()->active()->count())->toBe(1);
});
