<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

uses(RefreshDatabase::class);

it('redirects to google for authentication', function () {
    Socialite::fake('google');

    $response = $this->get('/auth/google/redirect');

    $response->assertRedirect();
});

it('creates user and logs in on google callback', function () {
    Socialite::fake('google', SocialiteUser::fake([
        'id' => 'google-12345',
        'name' => 'Budi Santoso',
        'email' => 'budi@gmail.com',
        'avatar' => 'https://example.com/avatar.jpg',
    ]));

    $response = $this->get('/auth/google/callback');

    $response->assertRedirect(route('dashboard'));

    $this->assertDatabaseHas('users', [
        'google_id' => 'google-12345',
        'name' => 'Budi Santoso',
        'email' => 'budi@gmail.com',
        'avatar_url' => 'https://example.com/avatar.jpg',
    ]);

    $this->assertAuthenticated();
});

it('updates existing user on subsequent login', function () {
    User::factory()->create([
        'google_id' => 'google-12345',
        'name' => 'Old Name',
        'email' => 'budi@gmail.com',
    ]);

    Socialite::fake('google', SocialiteUser::fake([
        'id' => 'google-12345',
        'name' => 'Budi Santoso Updated',
        'email' => 'budi@gmail.com',
        'avatar' => 'https://example.com/new-avatar.jpg',
    ]));

    $this->get('/auth/google/callback');

    $this->assertDatabaseHas('users', [
        'google_id' => 'google-12345',
        'name' => 'Budi Santoso Updated',
        'avatar_url' => 'https://example.com/new-avatar.jpg',
    ]);

    $this->assertDatabaseCount('users', 1);
});

it('redirects home on socialite failure', function () {
    Socialite::shouldReceive('driver')
        ->once()
        ->with('google')
        ->andThrow(new Exception('OAuth failed'));

    $response = $this->get('/auth/google/callback');

    $response->assertRedirect(route('home'));
});

it('protects dashboard with auth middleware', function () {
    $response = $this->get('/dashboard');

    $response->assertRedirect(route('login'));
});
