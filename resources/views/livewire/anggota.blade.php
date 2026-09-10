<div>
    <div class="flex flex-col min-h-screen bg-bg">
        {{-- Header --}}
        <header class="shrink-0 px-5 pt-4 pb-3">
            <h1 class="text-lg font-bold text-ink">Anggota</h1>
            <p class="mt-0.5 text-xs text-muted">{{ $warung->name }} · {{ $members->count() }} orang</p>
        </header>

        {{-- Invite Code --}}
        @if($isOwner)
            <div class="mx-5 mb-3 rounded-xl border border-rule bg-surface p-3.5">
                <p class="text-[11px] font-semibold text-muted">Kode Undangan</p>
                <div class="mt-1 flex items-center gap-2">
                    <span class="font-display text-lg font-bold tracking-widest text-ink">{{ $warung->invite_code }}</span>
                    <button onclick="navigator.clipboard.writeText('{{ $warung->invite_code }}')" class="rounded-full bg-primary-soft px-3 py-1 text-xs font-semibold text-primary">Salin</button>
                </div>
            </div>
        @endif

        {{-- Members List --}}
        <div class="flex-1 overflow-y-auto px-5 pb-24 pt-1">
            @foreach($members as $member)
                <div class="flex items-center justify-between border-b border-rule py-3.5">
                    <div class="flex items-center gap-3">
                        @if($member->user?->avatar_url)
                            <img src="{{ $member->user->avatar_url }}" alt="" class="h-9 w-9 rounded-full object-cover">
                        @else
                            <div class="flex h-9 w-9 items-center justify-center rounded-full bg-primary-soft text-sm font-bold text-primary">
                                {{ strtoupper(substr($member->user?->name ?? '?', 0, 1)) }}
                            </div>
                        @endif
                        <div>
                            <div class="flex items-center gap-1.5">
                                <span class="text-sm font-semibold text-ink">{{ $member->user?->name }}</span>
                                @if($member->isOwner())
                                    <span class="rounded-full bg-primary px-2 py-0.5 text-[10px] font-bold text-white">Owner</span>
                                @elseif($member->can_edit_any_entry)
                                    <span class="rounded-full bg-amber-soft px-2 py-0.5 text-[10px] font-bold text-amber">Editor</span>
                                @endif
                            </div>
                            @if(! $member->is_active)
                                <span class="text-[11px] text-muted">Nonaktif</span>
                            @endif
                        </div>
                    </div>

                    @if($isOwner && $member->user_id !== auth()->id() && $member->is_active)
                        <div class="flex items-center gap-1.5">
                            <button wire:click="toggleEditor({{ $member->id }})" wire:confirm="Toggle editor?" class="rounded-full bg-amber-soft px-2.5 py-1 text-[11px] font-semibold text-amber">
                                {{ $member->can_edit_any_entry ? 'Cabut Editor' : 'Jadikan Editor' }}
                            </button>
                            <button wire:click="kick({{ $member->id }})" wire:confirm="Nonaktifkan anggota ini?" class="rounded-full bg-red-50 px-2.5 py-1 text-[11px] font-semibold text-red-600">Kick</button>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        <x-bottom-nav active="anggota" />
    </div>
</div>
