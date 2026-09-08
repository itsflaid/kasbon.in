<?php

namespace App\Livewire;

use App\Models\Warung;
use Livewire\Attributes\On;
use Livewire\Component;

class Dashboard extends Component
{
    public Warung $warung;

    #[On('echo-private:warung.{warung.id},App\\Events\\EntryCreated')]
    public function onEntryCreated(): void
    {
        // trigger re-render — render() query fresh dari DB
    }

    public function render()
    {
        $entries = $this->warung->entries()
            ->active()
            ->with('debtor', 'recordedBy')
            ->latest()
            ->get();

        return view('livewire.dashboard', ['entries' => $entries]);
    }
}
