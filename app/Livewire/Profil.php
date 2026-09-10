<?php

namespace App\Livewire;

use Livewire\Component;

class Profil extends Component
{
    public function getWarungsProperty()
    {
        $user = auth()->user();

        return $user->warungMembers()->with('warung')->get();
    }

    public function logout(): void
    {
        auth()->logout();
        session()->invalidate();
        session()->regenerateToken();
        $this->redirectRoute('home');
    }

    public function render()
    {
        return view('livewire.profil', [
            'user' => auth()->user(),
            'warungs' => $this->warungs,
        ]);
    }
}
