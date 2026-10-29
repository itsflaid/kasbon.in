<?php

namespace App\Models;

use Database\Factories\WarungFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Warung extends Model
{
    /** @use HasFactory<WarungFactory> */
    use HasFactory;

    protected $fillable = ['name', 'owner_id', 'invite_code'];

    protected static function booted(): void
    {
        static::creating(function (Warung $warung) {
            if (empty($warung->invite_code)) {
                $warung->invite_code = strtoupper(Str::random(8));
            }
        });
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * @return HasMany<WarungMember, $this>
     */
    public function members(): HasMany
    {
        return $this->hasMany(WarungMember::class);
    }

    /**
     * @return HasMany<Debtor, $this>
     */
    public function debtors(): HasMany
    {
        return $this->hasMany(Debtor::class);
    }

    /**
     * @return HasMany<Entry, $this>
     */
    public function entries(): HasMany
    {
        return $this->hasMany(Entry::class);
    }

    /**
     * @return HasMany<ActivityLog, $this>
     */
    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }
}
