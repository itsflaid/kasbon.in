<?php

namespace App\Livewire;

use App\Models\Warung;
use Livewire\Attributes\On;
use Livewire\Component;

class Dashboard extends Component
{
    public Warung $warung;

    public string $search = '';

    public function mount(): void
    {
        $this->warung = Warung::findOrFail(session('active_warung_id'));
    }

    public string $filter = 'semua';

    public bool $showModal = false;

    #[On('echo-private:warung.{warung.id},App\\Events\\EntryCreated')]
    public function onEntryCreated(): void {}

    public function openModal(): void
    {
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
    }

    public function getDebtorsProperty()
    {
        return $this->warung->debtors()
            ->with(['entries' => fn ($q) => $q->active()->with('recordedBy')])
            ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->get()
            ->filter(function ($debtor) {
                if ($this->filter === 'belum_lunas') {
                    return ! $debtor->hasIncompletePrice() && $debtor->total() > 0;
                }
                if ($this->filter === 'lunas') {
                    return ! $debtor->hasIncompletePrice() && $debtor->total() <= 0
                        && $debtor->entries->isNotEmpty();
                }

                return true;
            })
            ->values();
    }

    public function getTotalProperty(): int
    {
        return $this->warung->entries()->active()->sum('amount');
    }

    public function getHasIncompletePriceProperty(): bool
    {
        return $this->warung->entries()
            ->debts()
            ->active()
            ->whereNull('amount')
            ->exists();
    }

    public function render()
    {
        return view('livewire.dashboard', [
            'debtors' => $this->debtors,
            'total' => $this->total,
            'hasIncompletePrice' => $this->hasIncompletePrice,
        ]);
    }
}
