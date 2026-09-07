<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Warung;

class WarungPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Warung $warung): bool
    {
        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Warung $warung): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Warung $warung): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Warung $warung): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Warung $warung): bool
    {
        return false;
    }

    /**
     * Determine whether the user can manage members.
     */
    public function manageMembers(User $user, Warung $warung): bool
    {
        return $warung->owner_id === $user->id;
    }
}
