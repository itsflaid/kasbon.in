<?php

namespace App\Livewire;

use App\Livewire\Concerns\EnsuresWarungMembership;
use App\Models\Debtor;
use App\Models\Entry;
use App\Models\WarungMember;
use App\Services\EntryService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
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
    use EnsuresWarungMembership;

    #[Locked]
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

    #[On('echo-private:warung.{debtor.warung_id},EntryCreated')]
    #[On('echo-private:warung.{debtor.warung_id},EntryUpdated')]
    public function onEntryChanged(): void {}

    /**
     * @return Collection<int, Entry>
     */
    public function getEntriesProperty(): Collection
    {
        return $this->debtor->entries()
            ->active()
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
        return $this->debtor->entries()->active()->count();
    }

    public function getFirstEntryAtProperty(): ?CarbonImmutable
    {
        return $this->debtor->entries()
            ->active()
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
        $entry = $this->debtor->entries()->active()->findOrFail($entryId);

        Gate::authorize('update', $entry);

        $this->editingEntryId = $entry->id;
        $this->editingType = $entry->type;
        $this->editAmount = $entry->amount;
        $this->editItemDescription = $entry->item_description ?? '';
        $this->showEditModal = true;
    }

    public function saveEntry(): void
    {
        $entry = $this->debtor->entries()->active()->findOrFail($this->editingEntryId);

        Gate::authorize('update', $entry);

        $validated = $this->validate([
            'editAmount' => ['required', 'integer', 'min:1', 'max:'.Entry::MAX_AMOUNT],
            'editItemDescription' => ['nullable', 'string', 'max:'.Entry::MAX_DESCRIPTION],
        ]);

        app(EntryService::class)->update($entry, [
            'amount' => $validated['editAmount'],
            'item_description' => $validated['editItemDescription'] ?: null,
        ], auth()->user());

        $this->closeEditModal();
    }

    public function voidEntry(int $entryId): void
    {
        $entry = $this->debtor->entries()->active()->findOrFail($entryId);

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
        $entries = $this->entries;

        return view('livewire.debtor-detail', [
            'entries' => $entries,
            'total' => $this->total,
            'entryCount' => $this->entryCount,
            'firstEntryAt' => $this->firstEntryAt,
            'editableEntryIds' => $this->editableEntryIds($entries),
        ]);
    }

    protected function membershipWarungId(): int
    {
        return $this->debtor->warung_id;
    }

    /**
     * @param  Collection<int, Entry>  $entries
     * @return array<int, int>
     */
    private function editableEntryIds(Collection $entries): array
    {
        $user = auth()->user();

        $member = WarungMember::query()
            ->where('warung_id', $this->debtor->warung_id)
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->first();

        $canEditAny = $member !== null
            && ($member->role === 'owner' || $member->isTrustedEditor());

        return $entries
            ->filter(fn (Entry $entry): bool => $canEditAny || $entry->recorded_by_user_id === $user->id)
            ->pluck('id')
            ->all();
    }
}
