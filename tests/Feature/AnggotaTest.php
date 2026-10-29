<?php

use App\Livewire\Anggota;
use App\Models\User;
use App\Models\Warung;
use App\Models\WarungMember;
use Livewire\Livewire;

function createAnggotaTestContext(): array
{
    $owner = User::factory()->create();
    $warung = Warung::factory()->create(['owner_id' => $owner->id]);
    $ownerMember = WarungMember::factory()->create([
        'warung_id' => $warung->id,
        'user_id' => $owner->id,
        'role' => 'owner',
        'can_edit_any_entry' => true,
    ]);

    $staff = User::factory()->create();
    $staffMember = WarungMember::factory()->create([
        'warung_id' => $warung->id,
        'user_id' => $staff->id,
        'role' => 'staff',
        'can_edit_any_entry' => false,
    ]);

    session(['active_warung_id' => $warung->id]);

    return compact('owner', 'warung', 'ownerMember', 'staff', 'staffMember');
}

it('shows the invite code and all members to the owner', function () {
    ['owner' => $owner, 'warung' => $warung, 'staff' => $staff] = createAnggotaTestContext();

    Livewire::actingAs($owner)
        ->test(Anggota::class)
        ->assertSee($warung->invite_code)
        ->assertSee($staff->name)
        ->assertSee('Owner');
});

it('lets the owner promote and demote an editor', function () {
    ['owner' => $owner, 'staffMember' => $staffMember] = createAnggotaTestContext();

    $component = Livewire::actingAs($owner)->test(Anggota::class);

    $component->call('toggleEditor', $staffMember->id);

    expect($staffMember->fresh()->can_edit_any_entry)->toBeTrue();

    $component->call('toggleEditor', $staffMember->id);

    expect($staffMember->fresh()->can_edit_any_entry)->toBeFalse();
});

it('lets the owner kick another member', function () {
    ['owner' => $owner, 'staffMember' => $staffMember] = createAnggotaTestContext();

    Livewire::actingAs($owner)
        ->test(Anggota::class)
        ->call('kick', $staffMember->id);

    expect($staffMember->fresh()->is_active)->toBeFalse();
});

it('forbids a non-owner from managing members', function () {
    ['staff' => $staff, 'ownerMember' => $ownerMember] = createAnggotaTestContext();

    Livewire::actingAs($staff)
        ->test(Anggota::class)
        ->call('toggleEditor', $ownerMember->id)
        ->assertForbidden();
});

it('prevents the owner from kicking themselves', function () {
    ['owner' => $owner, 'ownerMember' => $ownerMember] = createAnggotaTestContext();

    Livewire::actingAs($owner)
        ->test(Anggota::class)
        ->call('kick', $ownerMember->id)
        ->assertStatus(400);
});

it('rejects managing a member from another warung', function () {
    ['owner' => $owner] = createAnggotaTestContext();

    $otherOwner = User::factory()->create();
    $otherWarung = Warung::factory()->create(['owner_id' => $otherOwner->id]);
    $otherMember = WarungMember::factory()->create([
        'warung_id' => $otherWarung->id,
        'user_id' => $otherOwner->id,
        'role' => 'staff',
    ]);

    Livewire::actingAs($owner)
        ->test(Anggota::class)
        ->call('toggleEditor', $otherMember->id)
        ->assertNotFound();
});
