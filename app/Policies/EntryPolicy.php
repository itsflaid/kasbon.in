<?php

namespace App\Policies;

use App\Models\Entry;
use App\Models\User;
use App\Models\WarungMember;

class EntryPolicy
{
    public function update(User $user, Entry $entry): bool
    {
        $member = WarungMember::where('warung_id', $entry->warung_id)
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->first();

        if (! $member) {
            return false;
        }

        return $entry->recorded_by_user_id === $user->id
            || $member->role === 'owner'
            || $member->isTrustedEditor();
    }
}
