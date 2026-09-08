<?php

namespace App\Policies;

use App\Models\Entry;
use App\Models\User;
use App\Models\WarungMember;

class EntryPolicy
{
    public function update(User $user, Entry $entry): bool
    {
        if ($entry->recorded_by_user_id === $user->id) {
            return true;
        }

        $member = WarungMember::where('warung_id', $entry->warung_id)
            ->where('user_id', $user->id)
            ->first();

        return $member?->role === 'owner'
            || $member?->isTrustedEditor();
    }
}
