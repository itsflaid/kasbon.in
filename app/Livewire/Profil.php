<?php

namespace App\Livewire;

use App\Models\WarungMember;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Component;

/**
 * @property-read Collection<int, WarungMember> $warungs
 */
class Profil extends Component
{
    /**
     * @return Collection<int, WarungMember>
     */
    public function getWarungsProperty(): Collection
    {
        $user = auth()->user();

        return $user->warungMembers()->active()->with('warung')->get();
    }

    public function logout(): void
    {
        auth()->logout();
        session()->invalidate();
        session()->regenerateToken();
        $this->redirectRoute('login');
    }

    public function render(): View
    {
        return view('livewire.profil', [
            'user' => auth()->user(),
            'warungs' => $this->warungs,
        ]);
    }
}
