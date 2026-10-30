<?php

use App\Models\Debtor;
use App\Models\User;
use App\Models\Warung;
use App\Models\WarungMember;
use App\Services\EntryService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function createEntryServiceContext(): array
{
    $owner = User::factory()->create();
    $warung = Warung::factory()->create(['owner_id' => $owner->id]);
    WarungMember::factory()->create([
        'warung_id' => $warung->id,
        'user_id' => $owner->id,
        'role' => 'owner',
        'can_edit_any_entry' => true,
    ]);

    return compact('owner', 'warung');
}

it('reuses an existing debtor with a normalized name', function () {
    ['owner' => $owner, 'warung' => $warung] = createEntryServiceContext();
    $debtor = Debtor::factory()->create([
        'warung_id' => $warung->id,
        'name' => 'Bu Siti',
    ]);

    $result = app(EntryService::class)->create($warung, [
        'debtor_id' => null,
        'new_debtor_name' => '  BU   SITI ',
        'type' => 'debt',
        'item_description' => 'Beras 5kg',
        'amount' => 20000,
    ], $owner);

    expect($result['entry']->debtor_id)->toBe($debtor->id)
        ->and(Debtor::where('warung_id', $warung->id)->count())->toBe(1);
});

it('creates a new normalized debtor when none exists', function () {
    ['owner' => $owner, 'warung' => $warung] = createEntryServiceContext();

    $result = app(EntryService::class)->create($warung, [
        'debtor_id' => null,
        'new_debtor_name' => '  Pak   Budi ',
        'type' => 'debt',
        'item_description' => 'Kopi',
        'amount' => 5000,
    ], $owner);

    $debtor = Debtor::findOrFail($result['entry']->debtor_id);

    expect($debtor->name)->toBe('Pak   Budi')
        ->and($debtor->normalized_name)->toBe('pak budi');
});

it('enforces unique normalized names per warung', function () {
    ['warung' => $warung] = createEntryServiceContext();

    Debtor::factory()->create([
        'warung_id' => $warung->id,
        'name' => 'Bu Siti',
    ]);

    expect(fn () => DB::table('debtors')->insert([
        'warung_id' => $warung->id,
        'name' => 'BU SITI',
        'normalized_name' => 'bu siti',
        'note' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]))->toThrow(QueryException::class);
});
