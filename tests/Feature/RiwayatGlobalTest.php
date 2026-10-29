<?php

use App\Livewire\RiwayatGlobal;
use App\Models\Debtor;
use App\Models\Entry;
use App\Models\User;
use App\Models\Warung;
use App\Models\WarungMember;
use Livewire\Livewire;

function createRiwayatTestContext(): array
{
    $owner = User::factory()->create();
    $warung = Warung::factory()->create(['owner_id' => $owner->id]);
    WarungMember::factory()->create([
        'warung_id' => $warung->id,
        'user_id' => $owner->id,
        'role' => 'owner',
        'can_edit_any_entry' => true,
    ]);

    session(['active_warung_id' => $warung->id]);

    return compact('owner', 'warung');
}

it('only shows entries from the active warung', function () {
    ['owner' => $owner, 'warung' => $warung] = createRiwayatTestContext();

    $debtor = Debtor::factory()->create([
        'warung_id' => $warung->id,
        'name' => 'Pelanggan Warung Aktif',
    ]);
    Entry::factory()->create([
        'warung_id' => $warung->id,
        'debtor_id' => $debtor->id,
        'recorded_by_user_id' => $owner->id,
    ]);

    $otherWarung = Warung::factory()->create();
    $otherDebtor = Debtor::factory()->create([
        'warung_id' => $otherWarung->id,
        'name' => 'Pelanggan Warung Lain',
    ]);
    Entry::factory()->create([
        'warung_id' => $otherWarung->id,
        'debtor_id' => $otherDebtor->id,
    ]);

    Livewire::actingAs($owner)
        ->test(RiwayatGlobal::class)
        ->assertSee('Pelanggan Warung Aktif')
        ->assertDontSee('Pelanggan Warung Lain');
});

it('filters entries by the member who recorded them', function () {
    ['owner' => $owner, 'warung' => $warung] = createRiwayatTestContext();

    $staff = User::factory()->create(['name' => 'Petugas Satu']);
    WarungMember::factory()->create([
        'warung_id' => $warung->id,
        'user_id' => $staff->id,
        'role' => 'staff',
    ]);

    $firstDebtor = Debtor::factory()->create([
        'warung_id' => $warung->id,
        'name' => 'Pelanggan Pertama',
    ]);
    $secondDebtor = Debtor::factory()->create([
        'warung_id' => $warung->id,
        'name' => 'Pelanggan Kedua',
    ]);

    Entry::factory()->create([
        'warung_id' => $warung->id,
        'debtor_id' => $firstDebtor->id,
        'recorded_by_user_id' => $owner->id,
    ]);
    Entry::factory()->create([
        'warung_id' => $warung->id,
        'debtor_id' => $secondDebtor->id,
        'recorded_by_user_id' => $staff->id,
    ]);

    Livewire::actingAs($owner)
        ->test(RiwayatGlobal::class)
        ->set('userId', $staff->id)
        ->assertSee('Pelanggan Kedua')
        ->assertDontSee('Pelanggan Pertama');
});

it('filters entries by date range', function () {
    ['owner' => $owner, 'warung' => $warung] = createRiwayatTestContext();

    $oldDebtor = Debtor::factory()->create([
        'warung_id' => $warung->id,
        'name' => 'Pelanggan Lama',
    ]);
    $newDebtor = Debtor::factory()->create([
        'warung_id' => $warung->id,
        'name' => 'Pelanggan Baru',
    ]);

    $oldEntry = Entry::factory()->create([
        'warung_id' => $warung->id,
        'debtor_id' => $oldDebtor->id,
        'recorded_by_user_id' => $owner->id,
    ]);
    $oldEntry->forceFill(['created_at' => now()->subDays(10)])->save();

    Entry::factory()->create([
        'warung_id' => $warung->id,
        'debtor_id' => $newDebtor->id,
        'recorded_by_user_id' => $owner->id,
    ]);

    Livewire::actingAs($owner)
        ->test(RiwayatGlobal::class)
        ->set('dateFrom', now()->subDays(2)->toDateString())
        ->assertSee('Pelanggan Baru')
        ->assertDontSee('Pelanggan Lama');
});

it('hides voided entries', function () {
    ['owner' => $owner, 'warung' => $warung] = createRiwayatTestContext();

    $debtor = Debtor::factory()->create([
        'warung_id' => $warung->id,
        'name' => 'Pelanggan Dibatalkan',
    ]);
    Entry::factory()->voided()->create([
        'warung_id' => $warung->id,
        'debtor_id' => $debtor->id,
        'recorded_by_user_id' => $owner->id,
    ]);

    Livewire::actingAs($owner)
        ->test(RiwayatGlobal::class)
        ->assertDontSee('Pelanggan Dibatalkan');
});
