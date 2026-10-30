<?php

namespace App\Models;

use Database\Factories\DebtorFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

/**
 * @property int|null $debt_total
 * @property int|null $payment_total
 * @property int $has_incomplete_price
 * @property int $incomplete_debt_count
 * @property int $active_entries_count
 * @property-read int $balance
 * @property-read Entry|null $latestEntry
 */
class Debtor extends Model
{
    /** @use HasFactory<DebtorFactory> */
    use HasFactory;

    protected $fillable = ['warung_id', 'name', 'normalized_name', 'note'];

    protected static function booted(): void
    {
        static::saving(function (Debtor $debtor) {
            $debtor->normalized_name = self::normalizeName($debtor->name);
        });
    }

    public static function normalizeName(string $name): string
    {
        return Str::of($name)->squish()->lower()->toString();
    }

    /**
     * @return BelongsTo<Warung, $this>
     */
    public function warung(): BelongsTo
    {
        return $this->belongsTo(Warung::class);
    }

    /**
     * @return HasMany<Entry, $this>
     */
    public function entries(): HasMany
    {
        return $this->hasMany(Entry::class);
    }

    /**
     * @return HasOne<Entry, $this>
     */
    public function latestEntry(): HasOne
    {
        return $this->hasOne(Entry::class)
            ->ofMany(['id' => 'max'], fn (Builder $query) => $query->where('is_voided', false));
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

        return (int) $debt - (int) $paid;
    }

    /**
     * @return Attribute<int, int>
     */
    protected function balance(): Attribute
    {
        return Attribute::get(
            fn (): int => (int) ($this->debt_total ?? 0) - (int) ($this->payment_total ?? 0),
        );
    }
}
