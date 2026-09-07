<?php

namespace App\Http\Controllers;

use App\Models\Warung;
use Illuminate\Http\Request;

class WarungController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Warung::class, 'warung');
    }

    public function create(Request $request)
    {
        $this->authorize('create', Warung::class);

        $warung = Warung::create([
            'name' => $request->get('name'),
        ]);

        // Auto-insert owner ke warung_members role owner
        $warung->members()->create([
            'user_id' => auth()->id(),
            'role' => 'owner',
            'can_edit_any_entry' => true,
            'is_active' => true,
        ]);

        return response()->json(['warung' => $warung, 'code' => $warung->invite_code]);
    }

    public function join(Request $request)
    {
        $request->validate([
            'code' => 'required|string|size:8',
        ]);

        $warung = Warung::where('invite_code', $request->code)->firstOrFail();

        // Cek apakah user sudah ikut
        if ($warung->members()->where('user_id', auth()->id())->exists()) {
            return response()->json(['error' => 'Sudah ikut warung ini'], 400);
        }

        // Insert sebagai staff
        $warung->members()->create([
            'user_id' => auth()->id(),
            'role' => 'staff',
            'can_edit_any_entry' => false,
            'is_active' => true,
        ]);

        return response()->json(['warung' => $warung]);
    }

    public function kick(Request $request, Warung $warung)
    {
        $this->authorize('manageMembers', $warung);

        // Soft delete: nonaktifkan member, tidak hapus row
        $member = $warung->members()->where('user_id', $request->target_user_id)->firstOrFail();

        $member->update(['is_active' => false]);

        return response()->json(['success' => true]);
    }

    public function toggleEditor(Request $request, Warung $warung)
    {
        $this->authorize('manageMembers', $warung);

        $member = $warung->members()->where('user_id', auth()->id())->firstOrFail();

        $member->toggle('can_edit_any_entry');

        return response()->json(['can_edit_any_entry' => $member->can_edit_any_entry]);
    }
}
