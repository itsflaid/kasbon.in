<?php

namespace App\Livewire;

use App\Models\Warung;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * @property-read Collection<int, Warung> $warungs
 */
#[Title('Pilih Warung')]
class PilihWarung extends Component
{
    public string $newWarungName = '';

    public string $joinCode = '';

    public string $activeTab = 'buat';

    /**
     * @return Collection<int, Warung>
     */
    public function getWarungsProperty(): Collection
    {
        $user = auth()->user();

        return Warung::whereHas('members', fn ($q) => $q->where('user_id', $user->id)->where('is_active', true))
            ->withCount('members')
            ->get();
    }

    public function createWarung(): void
    {
        $this->newWarungName = trim($this->newWarungName);

        $this->validate(['newWarungName' => 'required|string|min:2|max:255']);

        $warung = Warung::create([
            'name' => $this->newWarungName,
            'owner_id' => auth()->id(),
        ]);

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
        $this->joinCode = strtoupper(trim($this->joinCode));

        $this->validate(['joinCode' => 'required|string|size:8']);

        $warung = Warung::where('invite_code', $this->joinCode)->first();

        if (! $warung) {
            $this->addError('joinCode', 'Kode undangan tidak ditemukan.');

            return;
        }

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
        $isMember = Warung::whereKey($warungId)
            ->whereHas('members', fn ($q) => $q->where('user_id', auth()->id())->where('is_active', true))
            ->exists();

        abort_unless($isMember, 403);

        session(['active_warung_id' => $warungId]);
        $this->redirectRoute('dashboard');
    }

    public function render(): View
    {
        return view('livewire.pilih-warung', ['warungs' => $this->warungs]);
    }
}
