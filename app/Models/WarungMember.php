<?php

namespace App\Models;

use Database\Factories\WarungMemberFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WarungMember extends Model
{
    /** @use HasFactory<WarungMemberFactory> */
    use HasFactory;

    protected $fillable = ['warung_id', 'user_id', 'role', 'can_edit_any_entry', 'is_active'];

    protected function casts(): array
    {
        return [
            'can_edit_any_entry' => 'boolean',
            'is_active' => 'boolean',
            'joined_at' => 'datetime',
        ];
    }

    public function warung(): BelongsTo
    {
        return $this->belongsTo(Warung::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function isOwner(): bool
    {
        return $this->role === 'owner';
    }

    public function isTrustedEditor(): bool
    {
        return $this->can_edit_any_entry;
    }
}
