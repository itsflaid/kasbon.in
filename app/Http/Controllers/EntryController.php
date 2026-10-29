<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEntryRequest;
use App\Models\Entry;
use App\Models\Warung;
use App\Services\EntryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class EntryController extends Controller
{
    public function store(StoreEntryRequest $request, Warung $warung, EntryService $entryService): JsonResponse
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

    public function confirmStore(Warung $warung, Request $request, EntryService $entryService): JsonResponse
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

    public function update(Request $request, Entry $entry, EntryService $entryService): JsonResponse
    {
        Gate::authorize('update', $entry);

        $data = $request->validate([
            'amount' => ['required', 'integer', 'min:1'],
            'item_description' => ['nullable', 'string'],
        ]);

        $entry = $entryService->update($entry, $data, $request->user());

        return response()->json(['entry' => $entry]);
    }

    public function void(Request $request, Entry $entry, EntryService $entryService): JsonResponse
    {
        Gate::authorize('update', $entry);

        $entryService->void($entry, $request->user());

        return response()->json(['success' => true]);
    }
}
