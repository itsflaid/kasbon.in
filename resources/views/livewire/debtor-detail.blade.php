<div>
    <div class="flex flex-col min-h-screen bg-bg">
        {{-- Header --}}
        <header class="shrink-0 px-5 pt-4 pb-4">
            <a href="{{ route('dashboard') }}" wire:navigate class="mb-3 inline-flex items-center gap-2.5 text-ink">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
            </a>

            <div class="mt-1">
                <h1 class="text-base font-bold text-ink">{{ $debtor->name }}{{ $debtor->note ? " ({$debtor->note})" : '' }}</h1>
                <p class="mt-0.5 text-xs text-muted">{{ $entryCount }} catatan · sejak {{ $firstEntryAt?->timezone(config('app.timezone'))->format('d M Y') ?? '—' }}</p>
            </div>

            <div class="relative mt-2 inline-block">
                @if($debtor->hasIncompletePrice())
                    <span class="font-display text-[28px] font-bold text-muted">—</span>
                @else
                    <span class="font-display text-[28px] font-bold tracking-tight tabular-nums {{ $total < 0 ? 'text-red-600' : 'text-ink' }}">Rp {{ number_format($total, 0, ',', '.') }}</span>
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
                <div wire:key="entry-{{ $entry->id }}" class="flex gap-3 border-b border-rule py-3.5">
                    <span class="mt-1 h-[9px] w-[9px] shrink-0 rounded-full {{ $isDebt ? 'bg-amber' : 'bg-green' }}"></span>
                    <div class="flex-1 min-w-0">
                        <div class="text-sm font-semibold text-ink">
                            @if($isDebt)
                                {{ $entry->item_description ?? 'Utang' }}
                            @else
                                Bayar
                            @endif
                        </div>
                        <div class="mt-0.5 text-[11.5px] text-muted">Dicatat {{ $entry->recordedBy?->name }} · {{ $entry->created_at->timezone(config('app.timezone'))->format('d M, H:i') }}</div>
                    </div>
                    <div class="shrink-0 text-right">
                        @if($hasAmount)
                            <span class="font-display text-sm font-bold tabular-nums text-ink">Rp {{ number_format($entry->amount, 0, ',', '.') }}</span>
                        @else
                            <span class="inline-block rounded-full bg-amber-soft px-2.5 py-1 text-[11px] font-bold text-amber">isi harga</span>
                        @endif

                        @if(in_array($entry->id, $editableEntryIds, true))
                            <div class="mt-1">
                                <button wire:click="editEntry({{ $entry->id }})" class="text-[11px] font-semibold text-primary">
                                    {{ $hasAmount ? 'Edit' : 'Isi harga' }}
                                </button>
                            </div>
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
                <button wire:click="openEntryModal('debt')" class="flex-1 rounded-full border-[1.5px] border-primary py-3 text-center text-sm font-bold text-primary">Catat Utang</button>
                <button wire:click="openEntryModal('payment')" class="flex-1 rounded-full bg-primary py-3 text-sm font-bold text-white">Bayar Utang</button>
            </div>
        </div>

        @if($showEntryModal)
            <livewire:entry-modal
                :warung="$debtor->warung"
                :debtor-id="$debtor->id"
                :initial-type="$entryType"
                :key="'entry-modal-'.$debtor->id.'-'.$entryType"
            />
        @endif

        @if($showEditModal)
            <div class="fixed inset-0 z-50 flex items-end justify-center">
                <div class="absolute inset-0 bg-black/40" wire:click="closeEditModal"></div>

                <div class="relative z-10 w-full max-w-lg rounded-t-2xl bg-surface p-6 pb-8">
                    <div class="mb-5 flex items-center justify-between">
                        <h2 class="text-base font-bold text-ink">{{ $editingType === 'debt' ? 'Edit Catatan Utang' : 'Edit Pembayaran' }}</h2>
                        <button wire:click="closeEditModal" class="text-xl text-muted">&times;</button>
                    </div>

                    @if($editingType === 'debt')
                        <div class="mb-3">
                            <label class="mb-1 block text-xs font-semibold text-muted">Barang / Keterangan</label>
                            <input type="text" wire:model="editItemDescription" class="w-full rounded-full border border-rule bg-surface px-4 py-2.5 text-sm text-ink placeholder-muted focus:border-primary focus:outline-none">
                            @error('editItemDescription') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>
                    @endif

                    <div class="mb-5">
                        <label class="mb-1 block text-xs font-semibold text-muted">Jumlah</label>
                        <x-currency-input model="editAmount" :value="$editAmount" />
                        @error('editAmount') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>

                    <button wire:click="saveEntry" class="w-full rounded-full bg-primary py-3.5 text-sm font-bold text-white">Simpan</button>

                    <button
                        wire:click="voidEntry({{ $editingEntryId }})"
                        wire:confirm="Batalkan catatan ini? Catatan tidak dihapus, hanya ditandai batal."
                        class="mt-3 w-full rounded-full border-[1.5px] border-red-400 py-3 text-sm font-bold text-red-500"
                    >Batalkan Catatan</button>
                </div>
            </div>
        @endif
    </div>
</div>
