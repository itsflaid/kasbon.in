<?php

namespace App\Models;

use Database\Factories\EntryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Entry extends Model
{
    /** @use HasFactory<EntryFactory> */
    use HasFactory;

    protected $fillable = [
        'warung_id',
        'debtor_id',
        'type',
        'item_description',
        'amount',
        'recorded_by_user_id',
        'edited_by_user_id',
        'is_voided',
        'voided_by_user_id',
        'voided_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'is_voided' => 'boolean',
            'voided_at' => 'datetime',
        ];
    }

    public function warung(): BelongsTo
    {
        return $this->belongsTo(Warung::class);
    }

    public function debtor(): BelongsTo
    {
        return $this->belongsTo(Debtor::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }

    public function editedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'edited_by_user_id');
    }

    public function voidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by_user_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_voided', false);
    }

    public function scopeDebts($query)
    {
        return $query->where('type', 'debt');
    }

    public function scopePayments($query)
    {
        return $query->where('type', 'payment');
    }
}
