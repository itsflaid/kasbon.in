<?php

namespace App\Livewire;

use App\Models\Warung;
use App\Models\WarungMember;
use Livewire\Component;

class Anggota extends Component
{
    public int $activeWarungId;

    public function getWarungProperty(): Warung
    {
        return Warung::findOrFail($this->activeWarungId);
    }

    public function getMembersProperty()
    {
        return $this->warung->members()->with('user')->get();
    }

    public function getIsOwnerProperty(): bool
    {
        return $this->warung->owner_id === auth()->id();
    }

    public function toggleEditor(WarungMember $member): void
    {
        abort_unless($this->isOwner, 403);
        $member->update(['can_edit_any_entry' => ! $member->can_edit_any_entry]);
    }

    public function kick(WarungMember $member): void
    {
        abort_unless($this->isOwner, 403);
        abort_if($member->user_id === auth()->id(), 400);
        $member->update(['is_active' => false]);
    }

    public function mount(): void
    {
        $this->activeWarungId = session('active_warung_id');
    }

    public function render()
    {
        return view('livewire.anggota', [
            'warung' => $this->warung,
            'members' => $this->members,
            'isOwner' => $this->isOwner,
        ]);
    }
}
