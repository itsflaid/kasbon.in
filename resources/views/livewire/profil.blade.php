<div>
    <div class="flex flex-col min-h-screen bg-bg">
        {{-- Header --}}
        <header class="shrink-0 px-5 pt-4 pb-3">
            <h1 class="text-lg font-bold text-ink">Profil</h1>
        </header>

        {{-- User Info --}}
        <div class="mx-5 rounded-xl border border-rule bg-surface p-5">
            <div class="flex items-center gap-4">
                @if($user->avatar_url)
                    <img src="{{ $user->avatar_url }}" alt="" class="h-14 w-14 rounded-full object-cover">
                @else
                    <div class="flex h-14 w-14 items-center justify-center rounded-full bg-primary-soft text-xl font-bold text-primary">
                        {{ strtoupper(substr($user->name, 0, 1)) }}
                    </div>
                @endif
                <div>
                    <div class="text-base font-bold text-ink">{{ $user->name }}</div>
                    <div class="mt-0.5 text-sm text-muted">{{ $user->email }}</div>
                </div>
            </div>
        </div>

        {{-- Warung List --}}
        <div class="mx-5 mt-4">
            <p class="mb-2 text-xs font-semibold text-muted">Warung yang diikuti</p>
            @forelse($warungs as $wm)
                <div class="flex items-center justify-between border-b border-rule py-3">
                    <div>
                        <div class="text-sm font-semibold text-ink">{{ $wm->warung?->name }}</div>
                        <div class="mt-0.5 text-[11px] text-muted">Role: {{ ucfirst($wm->role) }}{{ $wm->can_edit_any_entry ? ' · Editor' : '' }}</div>
                    </div>
                    @if($wm->warung_id == session('active_warung_id'))
                        <span class="rounded-full bg-primary-soft px-2.5 py-1 text-[11px] font-semibold text-primary">Aktif</span>
                    @endif
                </div>
            @empty
                <p class="text-sm text-muted">Belum ada warung.</p>
            @endforelse
        </div>

        {{-- Logout --}}
        <div class="mx-5 mt-8">
            <button wire:click="logout" wire:confirm="Keluar dari akun?" class="w-full rounded-full border-[1.5px] border-red-400 py-3 text-sm font-bold text-red-500 transition-colors hover:bg-red-50">Keluar</button>
        </div>

        <x-bottom-nav active="profil" />
    </div>
</div>
