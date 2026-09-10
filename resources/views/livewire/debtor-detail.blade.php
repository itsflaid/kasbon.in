<div>
    <div class="flex flex-col min-h-screen bg-bg">
        {{-- Header --}}
        <header class="shrink-0 px-5 pt-4 pb-4">
            <a href="{{ route('dashboard') }}" wire:navigate class="mb-3 inline-flex items-center gap-2.5 text-ink">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
            </a>

            <div class="mt-1">
                <h1 class="text-base font-bold text-ink">{{ $debtor->name }}{{ $debtor->note ? " ({$debtor->note})" : '' }}</h1>
                <p class="mt-0.5 text-xs text-muted">{{ $entryCount }} catatan · sejak {{ $firstEntryAt?->format('d M Y') ?? '—' }}</p>
            </div>

            <div class="relative mt-2 inline-block">
                @if($debtor->hasIncompletePrice())
                    <span class="font-display text-[28px] font-bold text-muted">—</span>
                @else
                    <span class="font-display text-[28px] font-bold tracking-tight text-ink tabular-nums">Rp {{ number_format($total, 0, ',', '.') }}</span>
                @endif
                <svg class="block -mt-1" width="130" height="10" viewBox="0 0 130 10"><path d="M2 6 C 26 2, 52 9, 78 4 S 112 2, 128 6" stroke="#1F4E5F" stroke-width="2.5" fill="none" stroke-linecap="round"/></svg>
            </div>
        </header>

        {{-- Timeline --}}
        <div class="flex-1 overflow-y-auto px-5 pb-28 pt-1">
            @forelse($entries as $entry)
                @php
                    $isDebt = $entry->type === 'debt';
                    $hasAmount = $entry->amount !== null;
                @endphp
                <div class="flex gap-3 border-b border-rule py-3.5">
                    <span class="mt-1 h-[9px] w-[9px] shrink-0 rounded-full {{ $isDebt ? 'bg-amber' : 'bg-green' }}"></span>
                    <div class="flex-1 min-w-0">
                        <div class="text-sm font-semibold text-ink">
                            @if($isDebt)
                                {{ $entry->item_description ?? 'Utang' }}
                            @else
                                Bayar
                            @endif
                        </div>
                        <div class="mt-0.5 text-[11.5px] text-muted">Dicatat {{ $entry->recordedBy?->name }} · {{ $entry->created_at->format('d M, H:i') }}</div>
                    </div>
                    <div class="shrink-0 text-right">
                        @if($hasAmount)
                            <span class="font-display text-sm font-bold tabular-nums text-ink">Rp {{ number_format($entry->amount, 0, ',', '.') }}</span>
                        @else
                            <span class="inline-block rounded-full bg-amber-soft px-2.5 py-1 text-[11px] font-bold text-amber">isi harga</span>
                        @endif
                    </div>
                </div>
            @empty
                <div class="flex flex-col items-center pt-20 text-muted">
                    <p class="text-sm">Belum ada catatan.</p>
                </div>
            @endforelse
        </div>

        {{-- Action Bar --}}
        <div class="fixed bottom-0 left-0 right-0 z-40 border-t border-rule bg-surface px-5 py-3.5 pb-5">
            <div class="flex gap-2.5">
                <a href="{{ route('dashboard') }}" wire:navigate class="flex-1 rounded-full border-[1.5px] border-primary py-3 text-center text-sm font-bold text-primary">Catat Utang</a>
                <button class="flex-1 rounded-full bg-primary py-3 text-sm font-bold text-white">Bayar Utang</button>
            </div>
        </div>
    </div>
</div>
