<?php

namespace App\Livewire;

use App\Livewire\Concerns\EnsuresWarungMembership;
use App\Models\Debtor;
use App\Models\Warung;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * @property-read Collection<int, Debtor> $debtors
 * @property-read int $total
 * @property-read bool $hasIncompletePrice
 */
class Dashboard extends Component
{
    use EnsuresWarungMembership;

    #[Locked]
    public Warung $warung;

    public string $search = '';

    public string $filter = 'semua';

    public bool $showModal = false;

    public function mount(): void
    {
        $this->warung = Warung::whereKey(session('active_warung_id'))->firstOrFail();
    }

    #[On('echo-private:warung.{warung.id},EntryCreated')]
    #[On('echo-private:warung.{warung.id},EntryUpdated')]
    public function onEntryChanged(): void {}

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
        $query = $this->warung->debtors()
            ->select(['id', 'warung_id', 'name', 'note', 'created_at'])
            ->withExists([
                'entries as has_incomplete_price' => fn (Builder $query) => $query
                    ->where('type', 'debt')
                    ->where('is_voided', false)
                    ->whereNull('amount'),
            ])
            ->withCount([
                'entries as incomplete_debt_count' => fn (Builder $query) => $query
                    ->where('type', 'debt')
                    ->where('is_voided', false)
                    ->whereNull('amount'),
                'entries as active_entries_count' => fn (Builder $query) => $query
                    ->where('is_voided', false),
            ])
            ->withSum([
                'entries as debt_total' => fn (Builder $query) => $query
                    ->where('type', 'debt')
                    ->where('is_voided', false),
            ], 'amount')
            ->withSum([
                'entries as payment_total' => fn (Builder $query) => $query
                    ->where('type', 'payment')
                    ->where('is_voided', false),
            ], 'amount')
            ->with(['latestEntry' => fn ($query) => $query->with('recordedBy')]);

        if ($this->search !== '') {
            $query->whereRaw("name LIKE ? ESCAPE '!'", ['%'.$this->escapeLike($this->search).'%']);
        }

        return $query
            ->get()
            ->filter(function (Debtor $debtor): bool {
                if ($this->filter === 'belum_lunas') {
                    return ! $debtor->has_incomplete_price && $debtor->balance > 0;
                }

                if ($this->filter === 'lunas') {
                    return ! $debtor->has_incomplete_price
                        && $debtor->balance <= 0
                        && $debtor->active_entries_count > 0;
                }

                return true;
            })
            ->values();
    }

    public function getTotalProperty(): int
    {
        $total = $this->warung->entries()
            ->active()
            ->selectRaw("COALESCE(SUM(CASE WHEN type = 'debt' THEN amount ELSE 0 END), 0) - COALESCE(SUM(CASE WHEN type = 'payment' THEN amount ELSE 0 END), 0) as total")
            ->first()
            ?->getAttribute('total');

        return (int) $total;
    }

    public function getHasIncompletePriceProperty(): bool
    {
        return $this->warung->entries()
            ->debts()
            ->active()
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

    protected function membershipWarungId(): int
    {
        return $this->warung->id;
    }

    private function escapeLike(string $value): string
    {
        return str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $value);
    }
}
