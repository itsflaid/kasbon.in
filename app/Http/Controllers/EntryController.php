<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEntryRequest;
use App\Http\Requests\UpdateEntryRequest;
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
            ]);
        }

        return response()->json(['entry' => $result['entry']], 201);
    }

    public function confirmStore(StoreEntryRequest $request, Warung $warung, EntryService $entryService): JsonResponse
    {
        $entry = $entryService->confirmCreate($warung, $request->validated(), $request->user());

        return response()->json(['entry' => $entry], 201);
    }

    public function update(UpdateEntryRequest $request, Entry $entry, EntryService $entryService): JsonResponse
    {
        $entry = $entryService->update($entry, $request->validated(), $request->user());

        return response()->json(['entry' => $entry]);
    }

    public function void(Request $request, Entry $entry, EntryService $entryService): JsonResponse
    {
        Gate::authorize('update', $entry);

        $entryService->void($entry, $request->user());

        return response()->json(['success' => true]);
    }
}
