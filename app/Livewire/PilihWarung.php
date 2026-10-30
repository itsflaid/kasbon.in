<?php

namespace App\Livewire;

use App\Models\Warung;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
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

        return Warung::whereHas('members', fn ($query) => $query->where('user_id', $user->id)->where('is_active', true))
            ->withCount('members')
            ->get();
    }

    public function createWarung(): void
    {
        $this->newWarungName = trim($this->newWarungName);

        $this->validate(['newWarungName' => ['required', 'string', 'min:2', 'max:255']]);

        if (! $this->allow('warung-create', 5)) {
            return;
        }

        $userId = auth()->id();

        $warung = DB::transaction(function () use ($userId): Warung {
            $attempts = 0;

            do {
                try {
                    $warung = Warung::create([
                        'name' => $this->newWarungName,
                        'owner_id' => $userId,
                    ]);

                    $warung->members()->create([
                        'user_id' => $userId,
                        'role' => 'owner',
                        'can_edit_any_entry' => true,
                        'is_active' => true,
                    ]);

                    return $warung;
                } catch (UniqueConstraintViolationException $exception) {
                    if (++$attempts >= 3) {
                        throw $exception;
                    }
                }
            } while (true);
        });

        session(['active_warung_id' => $warung->id]);
        $this->redirectRoute('dashboard');
    }

    public function joinWarung(): void
    {
        $this->joinCode = strtoupper(trim($this->joinCode));

        $this->validate(['joinCode' => ['required', 'string', 'size:8']]);

        if (! $this->allow('warung-join', 10)) {
            return;
        }

        $warung = Warung::where('invite_code', $this->joinCode)->first();

        if (! $warung) {
            $this->addError('joinCode', 'Kode undangan tidak ditemukan.');

            return;
        }

        if ($warung->members()->where('user_id', auth()->id())->exists()) {
            $this->addError('joinCode', 'Sudah ikut warung ini.');

            return;
        }

        try {
            $warung->members()->create([
                'user_id' => auth()->id(),
                'role' => 'staff',
                'can_edit_any_entry' => false,
                'is_active' => true,
            ]);
        } catch (UniqueConstraintViolationException) {
            $this->addError('joinCode', 'Sudah ikut warung ini.');

            return;
        }

        session(['active_warung_id' => $warung->id]);
        $this->redirectRoute('dashboard');
    }

    public function selectWarung(int $warungId): void
    {
        $isMember = Warung::whereKey($warungId)
            ->whereHas('members', fn ($query) => $query->where('user_id', auth()->id())->where('is_active', true))
            ->exists();

        abort_unless($isMember, 403);

        session(['active_warung_id' => $warungId]);
        $this->redirectRoute('dashboard');
    }

    public function render(): View
    {
        return view('livewire.pilih-warung', ['warungs' => $this->warungs]);
    }

    private function allow(string $action, int $maxAttempts): bool
    {
        $key = $action.':'.auth()->id();

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $this->addError(
                $action === 'warung-create' ? 'newWarungName' : 'joinCode',
                'Terlalu banyak percobaan. Coba lagi dalam '.RateLimiter::availableIn($key).' detik.',
            );

            return false;
        }

        RateLimiter::hit($key, 3600);

        return true;
    }
}
