<?php

use App\Events\EntryCreated;
use App\Models\Entry;
use App\Models\User;
use App\Models\Warung;
use App\Models\WarungMember;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('broadcasts on private warung channel', function () {
    $user = User::factory()->create();
    $warung = Warung::factory()->create(['owner_id' => $user->id]);
    WarungMember::factory()->create([
        'warung_id' => $warung->id,
        'user_id' => $user->id,
        'role' => 'owner',
    ]);
    $entry = Entry::factory()->create(['warung_id' => $warung->id]);

    $event = new EntryCreated($entry);

    $channels = $event->broadcastOn();

    expect($channels)->toHaveCount(1);
    expect($channels[0])->toBeInstanceOf(PrivateChannel::class);
    expect($channels[0]->name)->toBe("private-warung.{$warung->id}");
});

it('implements ShouldBroadcast', function () {
    $entry = Entry::factory()->make();

    $event = new EntryCreated($entry);

    expect($event)->toBeInstanceOf(ShouldBroadcast::class);
});

it('exposes entry via broadcastWith', function () {
    $entry = Entry::factory()->create();

    $event = new EntryCreated($entry);
    $data = $event->broadcastWith();

    expect($data)->toHaveKey('entry');
    expect($data['entry']['id'])->toBe($entry->id);
});
