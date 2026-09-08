<?php

use App\Models\WarungMember;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('warung.{warungId}', function ($user, $warungId) {
    return WarungMember::where('warung_id', $warungId)
        ->where('user_id', $user->id)
        ->where('is_active', true)
        ->exists();
});
