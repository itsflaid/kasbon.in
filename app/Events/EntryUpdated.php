<?php

namespace App\Events;

use App\Models\Entry;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EntryUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Entry $entry, public string $action) {}

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel('warung.'.$this->entry->warung_id)];
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'entry_id' => $this->entry->id,
            'warung_id' => $this->entry->warung_id,
            'debtor_id' => $this->entry->debtor_id,
            'type' => $this->entry->type,
            'amount' => $this->entry->amount,
            'action' => $this->action,
        ];
    }
}
