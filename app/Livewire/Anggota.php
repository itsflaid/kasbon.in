<?php

namespace App\Livewire;

use App\Livewire\Concerns\EnsuresWarungMembership;
use App\Models\Warung;
use App\Models\WarungMember;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * @property-read Warung $warung
 * @property-read Collection<int, WarungMember> $members
 * @property-read bool $isOwner
 */
class Anggota extends Component
{
    use EnsuresWarungMembership;

    #[Locked]
    public int $activeWarungId;

    public function getWarungProperty(): Warung
    {
        return Warung::query()
            ->whereKey($this->activeWarungId)
            ->whereHas('members', fn ($query) => $query
                ->where('user_id', auth()->id())
                ->where('is_active', true))
            ->firstOrFail();
    }

    /**
     * @return Collection<int, WarungMember>
     */
    public function getMembersProperty(): Collection
    {
        return $this->warung->members()->active()->with('user')->get();
    }

    public function getIsOwnerProperty(): bool
    {
        return $this->warung->owner_id === auth()->id();
    }

    public function toggleEditor(WarungMember $member): void
    {
        Gate::authorize('manageMembers', $this->warung);

        abort_unless($member->warung_id === $this->warung->id && $member->is_active, 404);

        $member->update(['can_edit_any_entry' => ! $member->can_edit_any_entry]);
    }

    public function kick(WarungMember $member): void
    {
        Gate::authorize('manageMembers', $this->warung);

        abort_unless($member->warung_id === $this->warung->id && $member->is_active, 404);
        abort_if($member->user_id === auth()->id(), 400);

        $member->update(['is_active' => false]);
    }

    public function mount(): void
    {
        $this->activeWarungId = (int) session('active_warung_id');
    }

    public function render(): View
    {
        return view('livewire.anggota', [
            'warung' => $this->warung,
            'members' => $this->members,
            'isOwner' => $this->isOwner,
        ]);
    }

    protected function membershipWarungId(): int
    {
        return $this->activeWarungId;
    }
}
