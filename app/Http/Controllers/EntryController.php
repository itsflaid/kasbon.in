<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEntryRequest;
use App\Models\ActivityLog;
use App\Models\Entry;
use App\Models\Warung;
use App\Services\EntryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class EntryController extends Controller
{
    public function store(StoreEntryRequest $request, Warung $warung, EntryService $entryService)
    {
        $result = $entryService->create($warung, $request->validated(), $request->user());

        if ($result['status'] === 'warning') {
            return response()->json([
                'warning' => $result['message'],
                'overpayment' => $result['overpayment'],
                'data' => $result['data'],
            ], 200);
        }

        return response()->json(['entry' => $result['entry']], 201);
    }

    public function confirmStore(Warung $warung, Request $request, EntryService $entryService)
    {
        $data = $request->validate([
            'debtor_id' => 'required|exists:debtors,id',
            'type' => 'required|in:debt,payment',
            'item_description' => 'nullable|string|required_if:type,debt',
            'amount' => 'nullable|integer|min:1|required_if:type,payment',
        ]);

        $entry = $entryService->confirmCreate($warung, $data, $request->user());

        return response()->json(['entry' => $entry], 201);
    }

    public function update(Request $request, Entry $entry)
    {
        Gate::authorize('update', $entry);

        $request->validate([
            'amount' => ['required', 'integer', 'min:1'],
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

        $entry->debtor->forgetTotalCache();

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

        $entry->debtor->forgetTotalCache();

        return response()->json(['success' => true]);
    }
}
