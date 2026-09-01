<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Debtor extends Model
{
    protected $fillable = ['warung_id', 'name', 'normalized_name', 'note'];

    protected static function booted(): void
    {
        static::saving(function (Debtor $debtor) {
            $debtor->normalized_name = strtolower(trim($debtor->name));
        });
    }

    public function warung(): BelongsTo
    {
        return $this->belongsTo(Warung::class);
    }

    public function entries(): HasMany
    {
        return $this->hasMany(Entry::class);
    }
}
