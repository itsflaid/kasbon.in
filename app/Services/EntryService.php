<?php

namespace App\Services;

use App\Events\EntryCreated;
use App\Events\EntryUpdated;
use App\Models\ActivityLog;
use App\Models\Debtor;
use App\Models\Entry;
use App\Models\User;
use App\Models\Warung;
use App\Models\WarungMember;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EntryService
{
    /**
     * @param  array<string, mixed>  $data
     * @return array{status: 'warning', message: string, overpayment: int, data: array<string, mixed>}|array{status: 'created', entry: Entry}
     */
    public function create(Warung $warung, array $data, User $user): array
    {
        $this->ensureMember($warung, $user);

        return DB::transaction(function () use ($warung, $data, $user): array {
            $debtor = $this->resolveDebtor(
                $warung,
                isset($data['debtor_id']) ? (int) $data['debtor_id'] : null,
                isset($data['new_debtor_name']) ? (string) $data['new_debtor_name'] : null,
                ($data['type'] ?? null) === 'payment',
            );

            if ($data['type'] === 'payment' && isset($data['amount']) && $data['amount'] > $debtor->total()) {
                return [
                    'status' => 'warning',
                    'message' => 'Jumlah bayar melebihi sisa utang. Konfirmasi untuk tetap melanjutkan.',
                    'overpayment' => (int) $data['amount'] - $debtor->total(),
                    'data' => $data,
                ];
            }

            return [
                'status' => 'created',
                'entry' => $this->persist($warung, $debtor, $data, $user),
            ];
        });
    }

    /**
     * @param  array<int, array{item_description: string, amount: int|null}>  $items
     * @return Collection<int, Entry>
     */
    public function createMany(Warung $warung, ?int $debtorId, array $items, User $user, ?string $newDebtorName = null): Collection
    {
        $this->ensureMember($warung, $user);

        return DB::transaction(function () use ($warung, $debtorId, $items, $user, $newDebtorName): Collection {
            $debtor = $this->resolveDebtor($warung, $debtorId, $newDebtorName);

            $entries = collect($items)->map(function (array $item) use ($warung, $debtor, $user): Entry {
                return $this->persist($warung, $debtor, [
                    'debtor_id' => $debtor->id,
                    'type' => 'debt',
                    'item_description' => $item['item_description'],
                    'amount' => $item['amount'] ?? null,
                ], $user);
            });

            return new Collection($entries->all());
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function confirmCreate(Warung $warung, array $data, User $user): Entry
    {
        $this->ensureMember($warung, $user);

        return DB::transaction(function () use ($warung, $data, $user): Entry {
            $debtor = $this->resolveDebtor($warung, (int) $data['debtor_id'], null, true);

            return $this->persist($warung, $debtor, $data, $user);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Entry $entry, array $data, User $user): Entry
    {
        return DB::transaction(function () use ($entry, $data, $user): Entry {
            $locked = Entry::query()->whereKey($entry->id)->lockForUpdate()->firstOrFail();

            $this->ensureMemberForWarungId($locked->warung_id, $user);

            if ($locked->is_voided) {
                throw ValidationException::withMessages([
                    'entry' => 'Entry yang sudah dibatalkan tidak dapat diubah.',
                ]);
            }

            $oldValue = $locked->toArray();

            $locked->forceFill([
                'amount' => array_key_exists('amount', $data) ? $data['amount'] : $locked->amount,
                'item_description' => array_key_exists('item_description', $data)
                    ? ($data['item_description'] ?: null)
                    : $locked->item_description,
                'edited_by_user_id' => $user->id,
            ])->save();

            ActivityLog::create([
                'warung_id' => $locked->warung_id,
                'entry_id' => $locked->id,
                'user_id' => $user->id,
                'action' => 'updated_entry',
                'old_value' => $oldValue,
                'new_value' => $locked->fresh()->toArray(),
            ]);

            DB::afterCommit(fn () => event(new EntryUpdated($locked, 'updated')));

            return $locked;
        });
    }

    public function void(Entry $entry, User $user): Entry
    {
        return DB::transaction(function () use ($entry, $user): Entry {
            $locked = Entry::query()->whereKey($entry->id)->lockForUpdate()->firstOrFail();

            $this->ensureMemberForWarungId($locked->warung_id, $user);

            if ($locked->is_voided) {
                throw ValidationException::withMessages([
                    'entry' => 'Entry sudah dibatalkan.',
                ]);
            }

            $oldValue = $locked->toArray();

            $locked->forceFill([
                'is_voided' => true,
                'voided_by_user_id' => $user->id,
                'voided_at' => now(),
            ])->save();

            ActivityLog::create([
                'warung_id' => $locked->warung_id,
                'entry_id' => $locked->id,
                'user_id' => $user->id,
                'action' => 'voided_entry',
                'old_value' => $oldValue,
                'new_value' => $locked->fresh()->toArray(),
            ]);

            DB::afterCommit(fn () => event(new EntryUpdated($locked, 'voided')));

            return $locked;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function persist(Warung $warung, Debtor $debtor, array $data, User $user): Entry
    {
        $entry = new Entry([
            'debtor_id' => $debtor->id,
            'type' => $data['type'],
            'item_description' => $data['item_description'] ?? null,
            'amount' => $data['amount'] ?? null,
        ]);

        $entry->warung()->associate($warung);
        $entry->recordedBy()->associate($user);
        $entry->is_voided = false;
        $entry->save();

        ActivityLog::create([
            'warung_id' => $warung->id,
            'entry_id' => $entry->id,
            'user_id' => $user->id,
            'action' => $data['type'] === 'debt' ? 'created_debt' : 'created_payment',
            'new_value' => $entry->toArray(),
        ]);

        DB::afterCommit(fn () => event(new EntryCreated($entry)));

        return $entry;
    }

    private function resolveDebtor(Warung $warung, ?int $debtorId, ?string $newDebtorName, bool $lock = false): Debtor
    {
        if ($debtorId !== null) {
            return $this->debtorForWarung($warung, $debtorId, $lock);
        }

        $name = trim((string) $newDebtorName);

        if ($name === '') {
            throw ValidationException::withMessages([
                'debtor_id' => 'Pilih pelanggan atau isi nama baru.',
            ]);
        }

        $normalizedName = Debtor::normalizeName($name);

        try {
            return $warung->debtors()->firstOrCreate(
                ['normalized_name' => $normalizedName],
                ['name' => $name],
            );
        } catch (UniqueConstraintViolationException) {
            return $warung->debtors()
                ->where('normalized_name', $normalizedName)
                ->firstOrFail();
        }
    }

    private function debtorForWarung(Warung $warung, int $debtorId, bool $lock = false): Debtor
    {
        $query = $warung->debtors();

        if ($lock) {
            $query->lockForUpdate();
        }

        return $query->findOrFail($debtorId);
    }

    private function ensureMember(Warung $warung, User $user): void
    {
        $this->ensureMemberForWarungId($warung->id, $user);
    }

    private function ensureMemberForWarungId(int $warungId, User $user): void
    {
        abort_unless(
            WarungMember::query()
                ->where('warung_id', $warungId)
                ->where('user_id', $user->id)
                ->where('is_active', true)
                ->exists(),
            403,
        );
    }
}
