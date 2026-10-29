<?php

use App\Models\User;
use App\Models\Warung;
use App\Models\WarungMember;

function createWarungForUser(User $user, string $role = 'owner'): Warung
{
    $warung = Warung::factory()->create([
        'owner_id' => $role === 'owner' ? $user->id : User::factory()->create()->id,
    ]);

    WarungMember::factory()->create([
        'warung_id' => $warung->id,
        'user_id' => $user->id,
        'role' => $role,
        'can_edit_any_entry' => $role === 'owner',
    ]);

    return $warung;
}

it('redirects to choose a warung when the user has none', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('pilih-warung'));
});

it('automatically selects the only warung the user belongs to', function () {
    $user = User::factory()->create();
    $warung = createWarungForUser($user);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertSuccessful();

    expect(session('active_warung_id'))->toBe($warung->id);
});

it('asks the user to choose when they belong to multiple warungs without an active one', function () {
    $user = User::factory()->create();
    createWarungForUser($user);
    createWarungForUser($user, 'staff');

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('pilih-warung'));
});

it('keeps a valid active warung', function () {
    $user = User::factory()->create();
    $warung = createWarungForUser($user);

    $this->actingAs($user)
        ->withSession(['active_warung_id' => $warung->id])
        ->get(route('dashboard'))
        ->assertSuccessful();

    expect(session('active_warung_id'))->toBe($warung->id);
});

it('clears an active warung the user no longer belongs to', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $otherWarung = createWarungForUser($otherUser);

    $this->actingAs($user)
        ->withSession(['active_warung_id' => $otherWarung->id])
        ->get(route('dashboard'))
        ->assertRedirect(route('pilih-warung'));

    expect(session('active_warung_id'))->toBeNull();
});

it('redirects a kicked member out of the active warung', function () {
    $user = User::factory()->create();
    $warung = createWarungForUser($user);

    $warung->members()
        ->where('user_id', $user->id)
        ->update(['is_active' => false]);

    $this->actingAs($user)
        ->withSession(['active_warung_id' => $warung->id])
        ->get(route('dashboard'))
        ->assertRedirect(route('pilih-warung'));

    expect(session('active_warung_id'))->toBeNull();
});

it('allows an authenticated user to open the warung picker', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('pilih-warung'))
        ->assertSuccessful();
});
