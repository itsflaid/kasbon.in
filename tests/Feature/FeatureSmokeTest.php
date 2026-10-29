<?php

use App\Models\Debtor;
use App\Models\User;
use App\Models\Warung;
use App\Models\WarungMember;

it('renders every authenticated page without errors', function () {
    $owner = User::factory()->create();
    $warung = Warung::factory()->create(['owner_id' => $owner->id]);
    WarungMember::factory()->create([
        'warung_id' => $warung->id,
        'user_id' => $owner->id,
        'role' => 'owner',
        'can_edit_any_entry' => true,
    ]);
    $debtor = Debtor::factory()->create(['warung_id' => $warung->id]);

    $urls = [
        route('dashboard'),
        route('riwayat'),
        route('anggota'),
        route('profil'),
        route('pilih-warung'),
        route('debtor.detail', $debtor),
    ];

    foreach ($urls as $url) {
        $this->actingAs($owner)
            ->withSession(['active_warung_id' => $warung->id])
            ->get($url)
            ->assertSuccessful();
    }
});

it('protects every application page from guests', function () {
    $owner = User::factory()->create();
    $warung = Warung::factory()->create(['owner_id' => $owner->id]);
    $debtor = Debtor::factory()->create(['warung_id' => $warung->id]);

    $urls = [
        route('dashboard'),
        route('riwayat'),
        route('anggota'),
        route('profil'),
        route('pilih-warung'),
        route('debtor.detail', $debtor),
    ];

    foreach ($urls as $url) {
        $this->get($url)->assertRedirect(route('login'));
    }
});

it('redirects the home and login entry points', function () {
    $this->get(route('home'))->assertRedirect(route('dashboard'));
    $this->get(route('login'))->assertRedirect(route('google.redirect'));
});
