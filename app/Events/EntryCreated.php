<?php

namespace App\Events;

use App\Models\Entry;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EntryCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Entry $entry) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('warung.'.$this->entry->warung_id)];
    }

    public function broadcastWith(): array
    {
        return ['entry' => $this->entry->toArray()];
    }
}
