<?php

namespace App\Livewire;

use App\Livewire\Concerns\EnsuresWarungMembership;
use App\Models\Entry;
use App\Models\User;
use App\Models\Warung;
use App\Models\WarungMember;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * @property-read Warung $warung
 * @property-read Collection<int, User> $users
 */
class RiwayatGlobal extends Component
{
    use EnsuresWarungMembership;
    use WithPagination;

    public string $dateFrom = '';

    public string $dateTo = '';

    public ?int $userId = null;

    #[Locked]
    public int $activeWarungId;

    public function mount(): void
    {
        $this->activeWarungId = (int) session('active_warung_id');
    }

    #[On('echo-private:warung.{activeWarungId},EntryCreated')]
    #[On('echo-private:warung.{activeWarungId},EntryUpdated')]
    public function onEntryChanged(): void {}

    public function getWarungProperty(): Warung
    {
        return Warung::query()->whereKey($this->activeWarungId)->firstOrFail();
    }

    /**
     * @return Collection<int, User>
     */
    public function getUsersProperty(): Collection
    {
        $members = $this->warung->members()->active()->with('user')->get();

        return new Collection(
            $members->map(fn (WarungMember $member): User => $member->user)->all(),
        );
    }

    public function updatedUserId(): void
    {
        $this->resetPage();
    }

    public function updatedDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatedDateTo(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $timezone = config('app.timezone');

        $entries = Entry::where('warung_id', $this->activeWarungId)
            ->active()
            ->with('debtor', 'recordedBy')
            ->when($this->userId, fn ($query) => $query->where('recorded_by_user_id', $this->userId))
            ->when($this->dateFrom, fn ($query) => $query->where(
                'created_at',
                '>=',
                CarbonImmutable::parse($this->dateFrom, $timezone)->startOfDay()->utc(),
            ))
            ->when($this->dateTo, fn ($query) => $query->where(
                'created_at',
                '<=',
                CarbonImmutable::parse($this->dateTo, $timezone)->endOfDay()->utc(),
            ))
            ->latest()
            ->paginate(20);

        return view('livewire.riwayat-global', ['entries' => $entries]);
    }

    protected function membershipWarungId(): int
    {
        return $this->activeWarungId;
    }
}
