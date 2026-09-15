<?php

namespace App\Services;

use App\Events\EntryCreated;
use App\Models\ActivityLog;
use App\Models\Debtor;
use App\Models\Entry;
use App\Models\User;
use App\Models\Warung;

class EntryService
{
    /**
     * Coba buat entry baru. Kalau type=payment dan amount melebihi sisa utang,
     * TIDAK jadi disimpan — return status 'warning' supaya caller bisa minta konfirmasi dulu.
     *
     * @return array{status: string, entry?: Entry, message?: string, overpayment?: int, data?: array}
     */
    public function create(Warung $warung, array $data, User $user): array
    {
        $data['warung_id'] = $warung->id;
        $data['recorded_by_user_id'] = $user->id;

        if ($data['type'] === 'payment' && isset($data['amount'])) {
            $debtor = Debtor::findOrFail($data['debtor_id']);

            if ($data['amount'] > $debtor->total()) {
                return [
                    'status' => 'warning',
                    'message' => 'Jumlah bayar melebihi sisa utang. Konfirmasi untuk tetap melanjutkan.',
                    'overpayment' => $data['amount'] - $debtor->total(),
                    'data' => $data,
                ];
            }
        }

        return [
            'status' => 'created',
            'entry' => $this->persist($warung, $data, $user),
        ];
    }

    /**
     * Simpan entry setelah user konfirmasi lewat peringatan overpayment.
     * Tidak ngecek ulang overpayment — sudah dikonfirmasi user.
     */
    public function confirmCreate(Warung $warung, array $data, User $user): Entry
    {
        $data['warung_id'] = $warung->id;
        $data['recorded_by_user_id'] = $user->id;

        return $this->persist($warung, $data, $user);
    }

    private function persist(Warung $warung, array $data, User $user): Entry
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

        Debtor::find($data['debtor_id'])?->forgetTotalCache();

        return $entry;
    }
}
