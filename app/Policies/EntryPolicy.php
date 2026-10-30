<?php

namespace App\Policies;

use App\Models\Entry;
use App\Models\User;
use App\Models\Warung;
use App\Models\WarungMember;

class EntryPolicy
{
    public function create(User $user, Warung $warung): bool
    {
        return $this->membership($user, $warung->id) !== null;
    }

    public function update(User $user, Entry $entry): bool
    {
        if ($entry->is_voided) {
            return false;
        }

        $member = $this->membership($user, $entry->warung_id);

        if (! $member) {
            return false;
        }

        return $entry->recorded_by_user_id === $user->id
            || $member->role === 'owner'
            || $member->isTrustedEditor();
    }

    private function membership(User $user, int $warungId): ?WarungMember
    {
        return WarungMember::where('warung_id', $warungId)
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->first();
    }
}
