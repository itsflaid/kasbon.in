<?php

use App\Livewire\EntryModal;
use App\Models\Debtor;
use App\Models\Entry;
use App\Models\User;
use App\Models\Warung;
use App\Models\WarungMember;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('bisa membuka EntryModal dengan debtor dan tipe yang sudah dipilih', function () {
    $owner = User::factory()->create();
    $warung = Warung::factory()->create(['owner_id' => $owner->id]);
    WarungMember::factory()->create([
        'warung_id' => $warung->id,
        'user_id' => $owner->id,
        'role' => 'owner',
        'can_edit_any_entry' => true,
    ]);
    $debtor = Debtor::factory()->create(['warung_id' => $warung->id]);

    Livewire::actingAs($owner)
        ->test(EntryModal::class, [
            'warung' => $warung,
            'debtorId' => $debtor->id,
            'initialType' => 'payment',
        ])
        ->assertSet('debtor_id', $debtor->id)
        ->assertSet('type', 'payment');
});

it('menyimpan pembayaran setelah konfirmasi overpayment', function () {
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

    $component = Livewire::actingAs($owner)
        ->test(EntryModal::class, [
            'warung' => $warung,
            'debtorId' => $debtor->id,
            'initialType' => 'payment',
        ]);

    $component->set('amount', 100000)
        ->call('submit')
        ->assertSet('showConfirm', true);

    $this->assertDatabaseCount('entries', 1);

    $component->call('confirmSubmit')
        ->assertSet('showConfirm', false);

    $this->assertDatabaseHas('entries', [
        'debtor_id' => $debtor->id,
        'type' => 'payment',
        'amount' => 100000,
    ]);
});

it('bisa catat utang baru lewat komponen EntryModal tanpa error', function () {
    $owner = User::factory()->create();
    $warung = Warung::factory()->create(['owner_id' => $owner->id]);
    WarungMember::factory()->create([
        'warung_id' => $warung->id,
        'user_id' => $owner->id,
        'role' => 'owner',
        'can_edit_any_entry' => true,
    ]);
    $debtor = Debtor::factory()->create(['warung_id' => $warung->id]);

    Livewire::actingAs($owner)
        ->test(EntryModal::class, ['warung' => $warung])
        ->set('debtor_id', $debtor->id)
        ->set('items', [
            ['item_description' => 'Mie Ayam', 'amount' => 15000],
        ])
        ->call('submit')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('entries', [
        'debtor_id' => $debtor->id,
        'item_description' => 'Mie Ayam',
        'amount' => 15000,
    ]);
});

it('bisa catat utang dengan pelanggan baru saat warung belum punya debtor', function () {
    $owner = User::factory()->create();
    $warung = Warung::factory()->create(['owner_id' => $owner->id]);
    WarungMember::factory()->create([
        'warung_id' => $warung->id,
        'user_id' => $owner->id,
        'role' => 'owner',
        'can_edit_any_entry' => true,
    ]);

    Livewire::actingAs($owner)
        ->test(EntryModal::class, ['warung' => $warung])
        ->assertSet('useNewDebtor', true)
        ->set('newDebtorName', 'Bu Siti')
        ->set('items', [
            ['item_description' => 'Beras 5kg', 'amount' => 20000],
        ])
        ->call('submit')
        ->assertHasNoErrors();

    $debtor = Debtor::where('warung_id', $warung->id)->where('name', 'Bu Siti')->first();

    expect($debtor)->not->toBeNull();

    $this->assertDatabaseHas('entries', [
        'warung_id' => $warung->id,
        'debtor_id' => $debtor->id,
        'item_description' => 'Beras 5kg',
        'amount' => 20000,
    ]);
});

it('mengabaikan pilihan pelanggan lama saat memakai nama baru', function () {
    $owner = User::factory()->create();
    $warung = Warung::factory()->create(['owner_id' => $owner->id]);
    WarungMember::factory()->create([
        'warung_id' => $warung->id,
        'user_id' => $owner->id,
        'role' => 'owner',
        'can_edit_any_entry' => true,
    ]);
    $debtor = Debtor::factory()->create(['warung_id' => $warung->id]);

    Livewire::actingAs($owner)
        ->test(EntryModal::class, ['warung' => $warung])
        ->set('debtor_id', $debtor->id)
        ->set('useNewDebtor', true)
        ->set('newDebtorName', 'Pelanggan Baru')
        ->set('items', [
            ['item_description' => 'Teh', 'amount' => 3000],
        ])
        ->call('submit')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('entries', [
        'warung_id' => $warung->id,
        'item_description' => 'Teh',
        'amount' => 3000,
    ]);
    $this->assertDatabaseCount('debtors', 2);
});

it('menampilkan error saat pelanggan dan keterangan belum diisi', function () {
    $owner = User::factory()->create();
    $warung = Warung::factory()->create(['owner_id' => $owner->id]);
    WarungMember::factory()->create([
        'warung_id' => $warung->id,
        'user_id' => $owner->id,
        'role' => 'owner',
        'can_edit_any_entry' => true,
    ]);

    Livewire::actingAs($owner)
        ->test(EntryModal::class, ['warung' => $warung])
        ->set('useNewDebtor', false)
        ->call('submit')
        ->assertHasErrors(['debtor_id', 'items.0.item_description']);
});

it('bisa menyimpan beberapa barang sekaligus dalam satu kali simpan', function () {
    $owner = User::factory()->create();
    $warung = Warung::factory()->create(['owner_id' => $owner->id]);
    WarungMember::factory()->create([
        'warung_id' => $warung->id,
        'user_id' => $owner->id,
        'role' => 'owner',
        'can_edit_any_entry' => true,
    ]);
    $debtor = Debtor::factory()->create(['warung_id' => $warung->id]);

    Livewire::actingAs($owner)
        ->test(EntryModal::class, ['warung' => $warung])
        ->set('debtor_id', $debtor->id)
        ->set('items', [
            ['id' => 1, 'item_description' => 'Beras 5kg', 'amount' => 20000],
            ['id' => 2, 'item_description' => 'Gula 1kg', 'amount' => 15000],
        ])
        ->call('submit')
        ->assertHasNoErrors();

    $this->assertDatabaseCount('entries', 2);
    $this->assertDatabaseHas('entries', [
        'debtor_id' => $debtor->id,
        'item_description' => 'Beras 5kg',
        'amount' => 20000,
    ]);
    $this->assertDatabaseHas('entries', [
        'debtor_id' => $debtor->id,
        'item_description' => 'Gula 1kg',
        'amount' => 15000,
    ]);
});

it('menormalkan harga yang dikirim dengan pemisah ribuan', function () {
    $owner = User::factory()->create();
    $warung = Warung::factory()->create(['owner_id' => $owner->id]);
    WarungMember::factory()->create([
        'warung_id' => $warung->id,
        'user_id' => $owner->id,
        'role' => 'owner',
        'can_edit_any_entry' => true,
    ]);
    $debtor = Debtor::factory()->create(['warung_id' => $warung->id]);

    Livewire::actingAs($owner)
        ->test(EntryModal::class, ['warung' => $warung])
        ->set('debtor_id', $debtor->id)
        ->set('items', [
            ['item_description' => 'Kopi', 'amount' => '12.500'],
        ])
        ->call('submit')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('entries', [
        'debtor_id' => $debtor->id,
        'item_description' => 'Kopi',
        'amount' => 12500,
    ]);
});

it('bisa menambah dan menghapus baris barang', function () {
    $owner = User::factory()->create();
    $warung = Warung::factory()->create(['owner_id' => $owner->id]);
    WarungMember::factory()->create([
        'warung_id' => $warung->id,
        'user_id' => $owner->id,
        'role' => 'owner',
        'can_edit_any_entry' => true,
    ]);

    Livewire::actingAs($owner)
        ->test(EntryModal::class, ['warung' => $warung])
        ->assertCount('items', 1)
        ->call('addItem')
        ->assertCount('items', 2)
        ->call('removeItem', 0)
        ->assertCount('items', 1);
});

it('mengabaikan baris barang tambahan yang masih kosong', function () {
    $owner = User::factory()->create();
    $warung = Warung::factory()->create(['owner_id' => $owner->id]);
    WarungMember::factory()->create([
        'warung_id' => $warung->id,
        'user_id' => $owner->id,
        'role' => 'owner',
        'can_edit_any_entry' => true,
    ]);
    $debtor = Debtor::factory()->create(['warung_id' => $warung->id]);

    Livewire::actingAs($owner)
        ->test(EntryModal::class, ['warung' => $warung])
        ->set('debtor_id', $debtor->id)
        ->set('items', [
            ['id' => 1, 'item_description' => 'Beras 5kg', 'amount' => 20000],
            ['id' => 2, 'item_description' => '', 'amount' => null],
        ])
        ->call('submit')
        ->assertHasNoErrors();

    $this->assertDatabaseCount('entries', 1);
});
