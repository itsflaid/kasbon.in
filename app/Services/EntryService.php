<?php

namespace App\Services;

use App\Events\EntryCreated;
use App\Models\ActivityLog;
use App\Models\Debtor;
use App\Models\Entry;
use App\Models\User;
use App\Models\Warung;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class EntryService
{
    /**
     * Coba buat entry baru. Kalau type=payment dan amount melebihi sisa utang,
     * TIDAK jadi disimpan — return status 'warning' supaya caller bisa minta konfirmasi dulu.
     *
     * @param  array<string, mixed>  $data
     * @return array{status: 'warning', message: string, overpayment: int, data: array<string, mixed>}|array{status: 'created', entry: Entry}
     */
    public function create(Warung $warung, array $data, User $user): array
    {
        $this->ensureMember($warung, $user);

        $debtor = $this->debtorForWarung($warung, (int) $data['debtor_id']);

        $data['warung_id'] = $warung->id;
        $data['recorded_by_user_id'] = $user->id;

        if ($data['type'] === 'payment' && isset($data['amount']) && $data['amount'] > $debtor->total()) {
            return [
                'status' => 'warning',
                'message' => 'Jumlah bayar melebihi sisa utang. Konfirmasi untuk tetap melanjutkan.',
                'overpayment' => $data['amount'] - $debtor->total(),
                'data' => $data,
            ];
        }

        return [
            'status' => 'created',
            'entry' => $this->persist($warung, $debtor, $data, $user),
        ];
    }

    /**
     * Simpan beberapa barang sekaligus sebagai entry utang terpisah.
     *
     * @param  array<int, array{item_description: string, amount: int|null}>  $items
     * @return Collection<int, Entry>
     */
    public function createMany(Warung $warung, int $debtorId, array $items, User $user): Collection
    {
        $this->ensureMember($warung, $user);

        $debtor = $this->debtorForWarung($warung, $debtorId);

        return DB::transaction(function () use ($warung, $debtor, $items, $user): Collection {
            $entries = collect($items)->map(function (array $item) use ($warung, $debtor, $user): Entry {
                return $this->persist($warung, $debtor, [
                    'warung_id' => $warung->id,
                    'recorded_by_user_id' => $user->id,
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
     * Simpan entry setelah user konfirmasi lewat peringatan overpayment.
     * Tidak ngecek ulang overpayment — sudah dikonfirmasi user.
     *
     * @param  array<string, mixed>  $data
     */
    public function confirmCreate(Warung $warung, array $data, User $user): Entry
    {
        $this->ensureMember($warung, $user);

        $debtor = $this->debtorForWarung($warung, (int) $data['debtor_id']);

        $data['warung_id'] = $warung->id;
        $data['recorded_by_user_id'] = $user->id;

        return $this->persist($warung, $debtor, $data, $user);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Entry $entry, array $data, User $user): Entry
    {
        $oldValue = $entry->toArray();

        $entry->update($data + ['edited_by_user_id' => $user->id]);

        ActivityLog::create([
            'warung_id' => $entry->warung_id,
            'entry_id' => $entry->id,
            'user_id' => $user->id,
            'action' => 'updated_entry',
            'old_value' => $oldValue,
            'new_value' => $entry->fresh()->toArray(),
        ]);

        $entry->debtor?->forgetTotalCache();

        return $entry;
    }

    public function void(Entry $entry, User $user): Entry
    {
        $oldValue = $entry->toArray();

        $entry->update([
            'is_voided' => true,
            'voided_by_user_id' => $user->id,
            'voided_at' => now(),
        ]);

        ActivityLog::create([
            'warung_id' => $entry->warung_id,
            'entry_id' => $entry->id,
            'user_id' => $user->id,
            'action' => 'voided_entry',
            'old_value' => $oldValue,
            'new_value' => $entry->fresh()->toArray(),
        ]);

        $entry->debtor?->forgetTotalCache();

        return $entry;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function persist(Warung $warung, Debtor $debtor, array $data, User $user): Entry
    {
        $entry = Entry::create($data);

        event(new EntryCreated($entry));

        ActivityLog::create([
            'warung_id' => $warung->id,
            'entry_id' => $entry->id,
            'user_id' => $user->id,
            'action' => $data['type'] === 'debt' ? 'created_debt' : 'created_payment',
            'new_value' => $entry->toArray(),
        ]);

        $debtor->forgetTotalCache();

        return $entry;
    }

    private function debtorForWarung(Warung $warung, int $debtorId): Debtor
    {
        return $warung->debtors()->findOrFail($debtorId);
    }

    private function ensureMember(Warung $warung, User $user): void
    {
        abort_unless(
            $warung->members()->where('user_id', $user->id)->where('is_active', true)->exists(),
            403,
        );
    }
}
