<?php

namespace App\Livewire;

use App\Models\Warung;
use Livewire\Component;

class PilihWarung extends Component
{
    public string $newWarungName = '';

    public string $joinCode = '';

    public string $activeTab = 'pilih';

    public function getWarungsProperty()
    {
        $user = auth()->user();

        return Warung::whereHas('members', fn ($q) => $q->where('user_id', $user->id)->active())
            ->withCount('members')
            ->get();
    }

    public function createWarung(): void
    {
        $this->validate(['newWarungName' => 'required|string|min:2|max:255']);

        $warung = Warung::create(['name' => $this->newWarungName]);

        $warung->members()->create([
            'user_id' => auth()->id(),
            'role' => 'owner',
            'can_edit_any_entry' => true,
            'is_active' => true,
        ]);

        session(['active_warung_id' => $warung->id]);
        $this->redirectRoute('dashboard');
    }

    public function joinWarung(): void
    {
        $this->validate(['joinCode' => 'required|string|size:8']);

        $warung = Warung::where('invite_code', strtoupper($this->joinCode))->firstOrFail();

        if ($warung->members()->where('user_id', auth()->id())->exists()) {
            $this->addError('joinCode', 'Sudah ikut warung ini.');

            return;
        }

        $warung->members()->create([
            'user_id' => auth()->id(),
            'role' => 'staff',
            'can_edit_any_entry' => false,
            'is_active' => true,
        ]);

        session(['active_warung_id' => $warung->id]);
        $this->redirectRoute('dashboard');
    }

    public function selectWarung(int $warungId): void
    {
        session(['active_warung_id' => $warungId]);
        $this->redirectRoute('dashboard');
    }

    public function render()
    {
        return view('livewire.pilih-warung', ['warungs' => $this->warungs]);
    }
}
