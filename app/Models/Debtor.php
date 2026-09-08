<?php

namespace App\Models;

use Database\Factories\DebtorFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Debtor extends Model
{
    /** @use HasFactory<DebtorFactory> */
    use HasFactory;

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

    public function hasIncompletePrice(): bool
    {
        return $this->entries()
            ->where('type', 'debt')
            ->where('is_voided', false)
            ->whereNull('amount')
            ->exists();
    }

    public function total(): int
    {
        $debt = $this->entries()->debts()->active()->sum('amount');
        $paid = $this->entries()->payments()->active()->sum('amount');

        return $debt - $paid;
    }
}
