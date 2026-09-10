<?php

namespace App\Livewire;

use App\Models\Debtor;
use Livewire\Component;

class DebtorDetail extends Component
{
    public Debtor $debtor;

    public function getEntriesProperty()
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

    public function getFirstEntryAtProperty()
    {
        return $this->debtor->entries()->active()->oldest()->first()?->created_at;
    }

    public function render()
    {
        return view('livewire.debtor-detail', [
            'entries' => $this->entries,
            'total' => $this->total,
            'entryCount' => $this->entryCount,
            'firstEntryAt' => $this->firstEntryAt,
        ]);
    }
}
