<?php

namespace App\Livewire;

use App\Models\Debtor;
use App\Models\Warung;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * @property-read Collection<int, Debtor> $debtors
 * @property-read int $total
 * @property-read bool $hasIncompletePrice
 */
class Dashboard extends Component
{
    public Warung $warung;

    public string $search = '';

    public function mount(): void
    {
        $this->warung = Warung::whereKey(session('active_warung_id'))->firstOrFail();
    }

    public string $filter = 'semua';

    public bool $showModal = false;

    #[On('echo-private:warung.{warung.id},App\\Events\\EntryCreated')]
    public function onEntryCreated(): void {}

    public function openModal(): void
    {
        $this->showModal = true;
    }

    #[On('close-modal')]
    public function closeModal(): void
    {
        $this->showModal = false;
    }

    /**
     * @return Collection<int, Debtor>
     */
    public function getDebtorsProperty(): Collection
    {
        return $this->warung->debtors()
            ->with(['entries' => fn ($q) => $q->where('is_voided', false)->with('recordedBy')])
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
        $debt = $this->warung->entries()
            ->where('is_voided', false)
            ->where('type', 'debt')
            ->sum('amount');
        $paid = $this->warung->entries()
            ->where('is_voided', false)
            ->where('type', 'payment')
            ->sum('amount');

        return (int) $debt - (int) $paid;
    }

    public function getHasIncompletePriceProperty(): bool
    {
        return $this->warung->entries()
            ->where('type', 'debt')
            ->where('is_voided', false)
            ->whereNull('amount')
            ->exists();
    }

    public function render(): View
    {
        return view('livewire.dashboard', [
            'debtors' => $this->debtors,
            'total' => $this->total,
            'hasIncompletePrice' => $this->hasIncompletePrice,
        ]);
    }
}
