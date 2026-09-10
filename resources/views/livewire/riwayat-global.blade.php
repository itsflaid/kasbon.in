<div>
    <div class="flex flex-col min-h-screen bg-bg">
        {{-- Header --}}
        <header class="shrink-0 px-5 pt-4 pb-3">
            <h1 class="text-lg font-bold text-ink">Riwayat</h1>
        </header>

        {{-- Filters --}}
        <div class="shrink-0 space-y-2.5 px-5 pb-3">
            <div class="flex gap-2.5">
                <div class="flex-1">
                    <label class="mb-0.5 block text-[11px] font-semibold text-muted">Dari</label>
                    <input type="date" wire:model.live="dateFrom" class="w-full rounded-full border border-rule bg-surface px-3.5 py-2 text-sm text-ink focus:border-primary focus:outline-none">
                </div>
                <div class="flex-1">
                    <label class="mb-0.5 block text-[11px] font-semibold text-muted">Sampai</label>
                    <input type="date" wire:model.live="dateTo" class="w-full rounded-full border border-rule bg-surface px-3.5 py-2 text-sm text-ink focus:border-primary focus:outline-none">
                </div>
            </div>
            <div>
                <label class="mb-0.5 block text-[11px] font-semibold text-muted">Dicatat oleh</label>
                <select wire:model.live="userId" class="w-full rounded-full border border-rule bg-surface px-3.5 py-2 text-sm text-ink focus:border-primary focus:outline-none">
                    <option value="">Semua orang</option>
                    @foreach($this->users as $u)
                        <option value="{{ $u->id }}">{{ $u->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        {{-- List --}}
        <div class="flex-1 overflow-y-auto px-5 pb-24 pt-1">
            @forelse($entries as $entry)
                @php $isDebt = $entry->type === 'debt'; @endphp
                <div class="flex gap-3 border-b border-rule py-3.5">
                    <span class="mt-1 h-[9px] w-[9px] shrink-0 rounded-full {{ $isDebt ? 'bg-amber' : 'bg-green' }}"></span>
                    <div class="flex-1 min-w-0">
                        <div class="text-sm font-semibold text-ink">
                            {{ $entry->debtor->name }}{{ $entry->debtor->note ? " ({$entry->debtor->note})" : '' }}
                        </div>
                        <div class="mt-0.5 text-[11.5px] text-muted">
                            @if($isDebt)
                                {{ $entry->item_description ?? 'Utang' }} ·
                            @else
                                Bayar ·
                            @endif
                            Dicatat {{ $entry->recordedBy?->name }} · {{ $entry->created_at->format('d M, H:i') }}
                        </div>
                    </div>
                    <div class="shrink-0 text-right">
                        @if($entry->amount !== null)
                            <span class="font-display text-sm font-bold tabular-nums {{ $isDebt ? 'text-ink' : 'text-green' }}">{{ $isDebt ? '' : '+' }}Rp {{ number_format($entry->amount, 0, ',', '.') }}</span>
                        @else
                            <span class="inline-block rounded-full bg-amber-soft px-2.5 py-1 text-[11px] font-bold text-amber">belum diisi</span>
                        @endif
                    </div>
                </div>
            @empty
                <div class="flex flex-col items-center pt-20 text-muted">
                    <p class="text-sm">Belum ada riwayat.</p>
                </div>
            @endforelse

            @if($entries->hasPages())
                <div class="mt-4">
                    {{ $entries->links() }}
                </div>
            @endif
        </div>

        <x-bottom-nav active="riwayat" />
    </div>
</div>
