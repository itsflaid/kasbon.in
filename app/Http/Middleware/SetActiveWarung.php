<?php

namespace App\Http\Middleware;

use App\Models\Warung;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SetActiveWarung
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (! $user) {
            return $next($request);
        }

        if ($request->routeIs('pilih-warung')) {
            return $next($request);
        }

        $activeWarungId = session('active_warung_id');

        if ($activeWarungId) {
            $isMember = Warung::where('id', $activeWarungId)
                ->whereHas('members', fn ($q) => $q->where('user_id', $user->id)->active())
                ->exists();

            if ($isMember) {
                return $next($request);
            }

            session()->forget('active_warung_id');
        }

        $warungCount = Warung::whereHas('members', fn ($q) => $q->where('user_id', $user->id)->active())->count();

        if ($warungCount === 0) {
            return redirect()->route('pilih-warung');
        }

        if ($warungCount === 1) {
            $warung = Warung::whereHas('members', fn ($q) => $q->where('user_id', $user->id)->active())->first();
            session(['active_warung_id' => $warung->id]);

            return $next($request);
        }

        return redirect()->route('pilih-warung');
    }
}
