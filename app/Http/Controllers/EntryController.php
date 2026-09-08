<?php

namespace App\Http\Controllers;

use App\Events\EntryCreated;
use App\Http\Requests\StoreEntryRequest;
use App\Models\ActivityLog;
use App\Models\Debtor;
use App\Models\Entry;
use App\Models\Warung;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class EntryController extends Controller
{
    public function store(StoreEntryRequest $request, Warung $warung)
    {
        $data = $request->validated();
        $data['warung_id'] = $warung->id;
        $data['recorded_by_user_id'] = auth()->id();

        $debtor = Debtor::findOrFail($data['debtor_id']);

        if ($data['type'] === 'payment' && isset($data['amount']) && $data['amount'] > $debtor->total()) {
            return response()->json([
                'warning' => 'Jumlah bayar melebihi sisa utang. Konfirmasi untuk tetap melanjutkan.',
                'overpayment' => $data['amount'] - $debtor->total(),
                'data' => $data,
            ], 200);
        }

        $entry = Entry::create($data);

        event(new EntryCreated($entry));

        ActivityLog::create([
            'warung_id' => $warung->id,
            'entry_id' => $entry->id,
            'user_id' => auth()->id(),
            'action' => $data['type'] === 'debt' ? 'created_debt' : 'created_payment',
            'new_value' => $entry->toArray(),
        ]);

        return response()->json(['entry' => $entry], 201);
    }

    public function confirmStore(Warung $warung, Request $request)
    {
        $request->validate([
            'debtor_id' => 'required|exists:debtors,id',
            'type' => 'required|in:debt,payment',
            'item_description' => 'nullable|string|required_if:type,debt',
            'amount' => 'nullable|numeric|min:1|required_if:type,payment',
        ]);

        $data = $request->only(['debtor_id', 'type', 'item_description', 'amount']);
        $data['warung_id'] = $warung->id;
        $data['recorded_by_user_id'] = auth()->id();

        $entry = Entry::create($data);

        event(new EntryCreated($entry));

        ActivityLog::create([
            'warung_id' => $warung->id,
            'entry_id' => $entry->id,
            'user_id' => auth()->id(),
            'action' => $data['type'] === 'debt' ? 'created_debt' : 'created_payment',
            'new_value' => $entry->toArray(),
        ]);

        return response()->json(['entry' => $entry], 201);
    }

    public function update(Request $request, Entry $entry)
    {
        Gate::authorize('update', $entry);

        $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
            'item_description' => ['nullable', 'string'],
        ]);

        $oldValue = $entry->toArray();

        $entry->update($request->only(['amount', 'item_description']) + [
            'edited_by_user_id' => auth()->id(),
        ]);

        ActivityLog::create([
            'warung_id' => $entry->warung_id,
            'entry_id' => $entry->id,
            'user_id' => auth()->id(),
            'action' => 'updated_entry',
            'old_value' => $oldValue,
            'new_value' => $entry->fresh()->toArray(),
        ]);

        return response()->json(['entry' => $entry]);
    }

    public function void(Entry $entry)
    {
        Gate::authorize('update', $entry);

        $oldValue = $entry->toArray();

        $entry->update([
            'is_voided' => true,
            'voided_by_user_id' => auth()->id(),
            'voided_at' => now(),
        ]);

        ActivityLog::create([
            'warung_id' => $entry->warung_id,
            'entry_id' => $entry->id,
            'user_id' => auth()->id(),
            'action' => 'voided_entry',
            'old_value' => $oldValue,
            'new_value' => $entry->fresh()->toArray(),
        ]);

        return response()->json(['success' => true]);
    }
}
