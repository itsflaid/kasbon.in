<?php

namespace App\Livewire\Concerns;

use App\Models\WarungMember;
use Illuminate\Support\Facades\Auth;

trait EnsuresWarungMembership
{
    public function mountEnsuresWarungMembership(): void
    {
        $this->ensureWarungMembership();
    }

    public function hydrateEnsuresWarungMembership(): void
    {
        $this->ensureWarungMembership();
    }

    abstract protected function membershipWarungId(): int;

    protected function ensureWarungMembership(): void
    {
        $user = Auth::user();

        abort_unless($user !== null, 403);

        $isMember = WarungMember::query()
            ->where('warung_id', $this->membershipWarungId())
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->exists();

        abort_unless($isMember, 403);
    }
}
