<div>
    <div class="flex flex-col min-h-screen bg-bg">
        {{-- Header --}}
        <header class="shrink-0 px-5 pt-1.5 pb-4">
            <p class="text-[13px] font-medium text-muted">{{ $this->warung->name }} · {{ $this->warung->members()->active()->count() }} anggota jaga</p>
            <div class="relative mt-0.5 inline-block">
                @if($hasIncompletePrice)
                    <span class="font-display text-[28px] font-bold text-muted">—</span>
                @else
                    <span class="font-display text-[32px] font-bold tracking-tight text-ink tabular-nums">Rp {{ number_format($total, 0, ',', '.') }}</span>
                @endif
                <svg class="block -mt-1" width="150" height="10" viewBox="0 0 150 10"><path d="M2 6 C 30 2, 60 9, 90 4 S 130 2, 148 6" stroke="#1F4E5F" stroke-width="2.5" fill="none" stroke-linecap="round"/></svg>
            </div>
        </header>

        {{-- Search --}}
        <div class="mx-5">
            <input
                type="text"
                wire:model.live.debounce.300ms="search"
                placeholder="Cari nama…"
                class="w-full rounded-full border border-rule bg-surface px-4 py-2.5 text-sm text-ink placeholder-muted focus:border-primary focus:outline-none"
            />
        </div>

        {{-- Filter Chips --}}
        <div class="flex gap-2 px-5 pt-3.5 pb-1.5 shrink-0">
            @foreach(['semua' => 'Semua', 'belum_lunas' => 'Belum Lunas', 'lunas' => 'Lunas'] as $value => $label)
                <button
                    wire:click="$set('filter', '{{ $value }}')"
                    class="rounded-full border-[1.5px] px-3.5 py-1.5 text-[13px] font-semibold transition-colors {{ $filter === $value ? 'border-primary bg-primary text-white' : 'border-rule text-muted' }}"
                >{{ $label }}</button>
            @endforeach
        </div>

        {{-- Debtor List --}}
        <div class="flex-1 overflow-y-auto px-5 pb-24 pt-1">
            @forelse($debtors as $debtor)
                @php
                    $lastEntry = $debtor->entries->first();
                    $incomplete = $debtor->hasIncompletePrice();
                    $bal = $debtor->total();
                    $isLunas = ! $incomplete && $bal <= 0 && $debtor->entries->isNotEmpty();
                @endphp
                <a
                    href="{{ route('debtor.detail', $debtor) }}"
                    wire:navigate
                    class="flex items-center justify-between border-b border-rule py-3.5"
                >
                    <div class="flex items-center gap-2.5">
                        <span class="h-2 w-2 shrink-0 rounded-full {{ $isLunas ? 'bg-green' : 'bg-amber' }}"></span>
                        <div>
                            <div class="text-[15px] font-semibold text-ink">{{ $debtor->name }}{{ $debtor->note ? " ({$debtor->note})" : '' }}</div>
                            @if($lastEntry)
                                <div class="mt-0.5 text-xs text-muted">Terakhir dicatat {{ $lastEntry->recordedBy?->name }} · {{ $lastEntry->created_at->diffForHumans() }}</div>
                            @endif
                        </div>
                    </div>
                    <div class="text-right shrink-0 ml-2">
                        @if($incomplete)
                            <span class="inline-block rounded-full bg-amber-soft px-2.5 py-1 text-[11px] font-semibold text-amber">{{ $debtor->entries->where('type','debt')->where('amount', null)->count() }} harga belum diisi</span>
                        @elseif($isLunas)
                            <span class="inline-block rounded-full bg-green-soft px-2.5 py-1 text-[11px] font-semibold text-green">Lunas</span>
                        @else
                            <span class="font-display text-[15px] font-bold tabular-nums text-ink">Rp {{ number_format($bal, 0, ',', '.') }}</span>
                        @endif
                    </div>
                </a>
            @empty
                <div class="flex flex-col items-center pt-20 text-muted">
                    <p class="text-sm">Belum ada debtor.</p>
                    <p class="mt-1 text-xs">Tekan tombol + untuk catat utang baru.</p>
                </div>
            @endforelse
        </div>

        {{-- FAB --}}
        <button
            wire:click="openModal"
            class="fixed right-5 bottom-20 z-30 flex h-[52px] w-[52px] items-center justify-center rounded-full bg-primary text-[26px] text-white shadow-lg"
            style="box-shadow: 0 8px 16px rgba(31,78,95,.35)"
        >+</button>

        {{-- Bottom Nav --}}
        <x-bottom-nav active="dashboard" />

        {{-- Entry Modal --}}
        @if($showModal)
            <livewire:entry-modal :warung="$this->warung" :key="'modal-'.$this->warung->id" />
        @endif
    </div>
</div>
