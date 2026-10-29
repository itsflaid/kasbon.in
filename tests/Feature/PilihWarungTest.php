<?php

use App\Livewire\PilihWarung;
use App\Models\User;
use App\Models\Warung;
use App\Models\WarungMember;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('defaults to the create tab', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(PilihWarung::class)
        ->assertSet('activeTab', 'buat')
        ->assertSee('Buku ini masih kosong.');
});

it('creates a warung and makes the creator its owner', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(PilihWarung::class)
        ->set('newWarungName', 'Warung Bu Siti')
        ->call('createWarung')
        ->assertHasNoErrors()
        ->assertRedirect(route('dashboard'));

    $warung = Warung::where('name', 'Warung Bu Siti')->firstOrFail();

    expect($warung->owner_id)->toBe($user->id)
        ->and(session('active_warung_id'))->toBe($warung->id)
        ->and($warung->members()->where('user_id', $user->id)->where('role', 'owner')->exists())->toBeTrue();
});

it('shows a validation error for an unknown invite code', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(PilihWarung::class)
        ->set('activeTab', 'gabung')
        ->set('joinCode', 'ZZZZZZZZ')
        ->call('joinWarung')
        ->assertHasErrors(['joinCode' => 'Kode undangan tidak ditemukan.']);
});

it('joins an existing warung with a lowercase invite code', function () {
    $owner = User::factory()->create();
    $warung = Warung::factory()->create([
        'owner_id' => $owner->id,
        'invite_code' => 'ABCDEFGH',
    ]);
    WarungMember::factory()->create([
        'warung_id' => $warung->id,
        'user_id' => $owner->id,
        'role' => 'owner',
        'can_edit_any_entry' => true,
    ]);

    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(PilihWarung::class)
        ->set('activeTab', 'gabung')
        ->set('joinCode', 'abcdefgh')
        ->call('joinWarung')
        ->assertHasNoErrors()
        ->assertRedirect(route('dashboard'));

    expect($warung->members()->where('user_id', $user->id)->where('role', 'staff')->exists())->toBeTrue();
});

it('validates the new warung name', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(PilihWarung::class)
        ->set('newWarungName', ' ')
        ->call('createWarung')
        ->assertHasErrors(['newWarungName' => 'required']);

    $this->assertDatabaseCount('warungs', 0);
});

it('validates the invite code length', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(PilihWarung::class)
        ->set('activeTab', 'gabung')
        ->set('joinCode', 'PENDEK')
        ->call('joinWarung')
        ->assertHasErrors(['joinCode' => 'size']);
});

it('rejects joining a warung the user already belongs to', function () {
    $owner = User::factory()->create();
    $warung = Warung::factory()->create([
        'owner_id' => $owner->id,
        'invite_code' => 'ABCDEFGH',
    ]);
    WarungMember::factory()->create([
        'warung_id' => $warung->id,
        'user_id' => $owner->id,
        'role' => 'owner',
    ]);

    Livewire::actingAs($owner)
        ->test(PilihWarung::class)
        ->set('activeTab', 'gabung')
        ->set('joinCode', 'ABCDEFGH')
        ->call('joinWarung')
        ->assertHasErrors(['joinCode' => 'Sudah ikut warung ini.']);

    expect($warung->members()->where('user_id', $owner->id)->count())->toBe(1);
});

it('selects a warung the user belongs to', function () {
    $user = User::factory()->create();
    $warung = Warung::factory()->create(['owner_id' => $user->id]);
    WarungMember::factory()->create([
        'warung_id' => $warung->id,
        'user_id' => $user->id,
        'role' => 'owner',
    ]);

    Livewire::actingAs($user)
        ->test(PilihWarung::class)
        ->call('selectWarung', $warung->id)
        ->assertRedirect(route('dashboard'));

    expect(session('active_warung_id'))->toBe($warung->id);
});

it('rejects selecting a warung the user does not belong to', function () {
    $user = User::factory()->create();
    $otherOwner = User::factory()->create();
    $otherWarung = Warung::factory()->create(['owner_id' => $otherOwner->id]);
    WarungMember::factory()->create([
        'warung_id' => $otherWarung->id,
        'user_id' => $otherOwner->id,
        'role' => 'owner',
    ]);

    Livewire::actingAs($user)
        ->test(PilihWarung::class)
        ->call('selectWarung', $otherWarung->id)
        ->assertForbidden();

    expect(session('active_warung_id'))->toBeNull();
});
