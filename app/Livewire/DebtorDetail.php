<?php

namespace App\Livewire;

use App\Models\Debtor;
use App\Models\Entry;
use App\Services\EntryService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * @property-read Collection<int, Entry> $entries
 * @property-read int $total
 * @property-read int $entryCount
 * @property-read CarbonImmutable|null $firstEntryAt
 */
class DebtorDetail extends Component
{
    public Debtor $debtor;

    public bool $showEntryModal = false;

    public string $entryType = 'debt';

    public bool $showEditModal = false;

    public ?int $editingEntryId = null;

    public string $editingType = 'debt';

    public ?int $editAmount = null;

    public string $editItemDescription = '';

    public function mount(): void
    {
        abort_unless($this->debtor->warung_id === (int) session('active_warung_id'), 404);
    }

    #[On('echo-private:warung.{debtor.warung_id},App\\Events\\EntryCreated')]
    public function onEntryCreated(): void {}

    /**
     * @return Collection<int, Entry>
     */
    public function getEntriesProperty(): Collection
    {
        return $this->debtor->entries()
            ->where('is_voided', false)
            ->with('recordedBy')
            ->latest()
            ->get();
    }

    public function getTotalProperty(): int
    {
        return $this->debtor->total();
    }

    public function getEntryCountProperty(): int
    {
        return $this->debtor->entries()->where('is_voided', false)->count();
    }

    public function getFirstEntryAtProperty(): ?CarbonImmutable
    {
        return $this->debtor->entries()
            ->where('is_voided', false)
            ->oldest()
            ->first()?->created_at;
    }

    public function openEntryModal(string $type): void
    {
        abort_unless(in_array($type, ['debt', 'payment'], true), 400);

        $this->entryType = $type;
        $this->showEntryModal = true;
    }

    #[On('close-modal')]
    public function closeEntryModal(): void
    {
        $this->showEntryModal = false;
    }

    public function editEntry(int $entryId): void
    {
        $entry = $this->debtor->entries()->findOrFail($entryId);

        Gate::authorize('update', $entry);

        $this->editingEntryId = $entry->id;
        $this->editingType = $entry->type;
        $this->editAmount = $entry->amount;
        $this->editItemDescription = $entry->item_description ?? '';
        $this->showEditModal = true;
    }

    public function saveEntry(): void
    {
        $entry = $this->debtor->entries()->findOrFail($this->editingEntryId);

        Gate::authorize('update', $entry);

        $validated = $this->validate([
            'editAmount' => ['required', 'integer', 'min:1'],
            'editItemDescription' => ['nullable', 'string', 'max:255'],
        ]);

        app(EntryService::class)->update($entry, [
            'amount' => $validated['editAmount'],
            'item_description' => $validated['editItemDescription'] ?: null,
        ], auth()->user());

        $this->closeEditModal();
    }

    public function voidEntry(int $entryId): void
    {
        $entry = $this->debtor->entries()->findOrFail($entryId);

        Gate::authorize('update', $entry);

        app(EntryService::class)->void($entry, auth()->user());

        $this->closeEditModal();
    }

    public function closeEditModal(): void
    {
        $this->reset(['showEditModal', 'editingEntryId', 'editingType', 'editAmount', 'editItemDescription']);
    }

    public function render(): View
    {
        return view('livewire.debtor-detail', [
            'entries' => $this->entries,
            'total' => $this->total,
            'entryCount' => $this->entryCount,
            'firstEntryAt' => $this->firstEntryAt,
        ]);
    }
}
