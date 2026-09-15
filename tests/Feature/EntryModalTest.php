<?php

use App\Livewire\EntryModal;
use App\Models\Debtor;
use App\Models\User;
use App\Models\Warung;
use App\Models\WarungMember;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

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
        ->set('item_description', 'Mie Ayam')
        ->set('amount', 15000)
        ->call('submit')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('entries', [
        'debtor_id' => $debtor->id,
        'item_description' => 'Mie Ayam',
        'amount' => 15000,
    ]);
});
