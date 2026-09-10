<?php

namespace App\Livewire;

use App\Models\Entry;
use App\Models\Warung;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class RiwayatGlobal extends Component
{
    use WithPagination;

    public string $dateFrom = '';

    public string $dateTo = '';

    public ?int $userId = null;

    #[On('echo-private:warung.{activeWarungId},App\\Events\\EntryCreated')]
    public function onEntryCreated(): void {}

    public function getActiveWarungIdProperty(): int
    {
        return session('active_warung_id');
    }

    public function getUsersProperty()
    {
        return Warung::find($this->activeWarungId)
            ->members()->active()->with('user')->get()
            ->pluck('user');
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

    public function render()
    {
        $warungId = $this->activeWarungId;

        $entries = Entry::where('warung_id', $warungId)
            ->active()
            ->with('debtor', 'recordedBy')
            ->when($this->userId, fn ($q) => $q->where('recorded_by_user_id', $this->userId))
            ->when($this->dateFrom, fn ($q) => $q->whereDate('created_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($q) => $q->whereDate('created_at', '<=', $this->dateTo))
            ->latest()
            ->paginate(20);

        return view('livewire.riwayat-global', ['entries' => $entries]);
    }
}
