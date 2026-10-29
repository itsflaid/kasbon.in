<div
    x-data="{ show: @js(true) }"
    x-on:close-modal.window="show = false"
    x-show="show"
    x-cloak
    class="fixed inset-0 z-50 flex items-end justify-center"
>
    {{-- Backdrop --}}
    <div class="absolute inset-0 bg-black/40" x-on:click="show = false; $wire.close()"></div>

    {{-- Confirm Warning --}}
    @if($showConfirm)
        <div class="relative z-10 w-full max-w-lg rounded-t-2xl bg-surface p-6 pb-8" x-show="show" x-transition>
            <p class="text-sm font-semibold text-amber">{{ $warning }}</p>
            <div class="mt-4 flex gap-2.5">
                <button wire:click="confirmSubmit" class="flex-1 rounded-full bg-primary py-3 text-sm font-bold text-white">Tetap Simpan</button>
                <button wire:click="close" class="flex-1 rounded-full border-[1.5px] border-primary py-3 text-sm font-bold text-primary">Batal</button>
            </div>
        </div>
    @else
        <div class="relative z-10 w-full max-w-lg rounded-t-2xl bg-surface p-6 pb-8" x-show="show" x-transition>
            {{-- Header --}}
            <div class="mb-5 flex items-center justify-between">
                <h2 class="text-base font-bold text-ink">{{ $type === 'debt' ? 'Catat Utang Baru' : 'Catat Pembayaran' }}</h2>
                <button wire:click="close" class="text-xl text-muted">&times;</button>
            </div>

            {{-- Type Toggle --}}
            <div class="mb-4 flex rounded-full border-[1.5px] border-primary p-0.5">
                <button wire:click="$set('type', 'debt')" class="flex-1 rounded-full py-2 text-sm font-bold transition-colors {{ $type === 'debt' ? 'bg-primary text-white' : 'text-primary' }}">Utang</button>
                <button wire:click="$set('type', 'payment')" class="flex-1 rounded-full py-2 text-sm font-bold transition-colors {{ $type === 'payment' ? 'bg-primary text-white' : 'text-primary' }}">Bayar</button>
            </div>

            {{-- Debtor --}}
            <div class="mb-3">
                <label class="mb-1 block text-xs font-semibold text-muted">Nama Pelanggan</label>
                @if($type === 'debt')
                    <div class="mb-2 flex items-center gap-2">
                        <input type="checkbox" wire:model.live="useNewDebtor" id="newDebtorCheck" class="accent-primary">
                        <label for="newDebtorCheck" class="text-xs text-muted">Nama baru</label>
                    </div>
                    @if($useNewDebtor)
                        <input type="text" wire:model="newDebtorName" placeholder="Ketik nama baru…" class="w-full rounded-full border border-rule bg-surface px-4 py-2.5 text-sm text-ink placeholder-muted focus:border-primary focus:outline-none">
                        @error('newDebtorName')
                            <p class="mt-1 text-xs text-amber">{{ $message }}</p>
                        @enderror
                    @else
                        <select wire:model="debtor_id" class="w-full rounded-full border border-rule bg-surface px-4 py-2.5 text-sm text-ink focus:border-primary focus:outline-none">
                            <option value="">Pilih nama…</option>
                            @foreach($this->debtors as $d)
                                <option value="{{ $d->id }}">{{ $d->name }}{{ $d->note ? " ({$d->note})" : '' }}</option>
                            @endforeach
                        </select>
                    @endif
                @else
                    <select wire:model="debtor_id" class="w-full rounded-full border border-rule bg-surface px-4 py-2.5 text-sm text-ink focus:border-primary focus:outline-none">
                        <option value="">Pilih nama…</option>
                        @foreach($this->debtors as $d)
                            <option value="{{ $d->id }}">{{ $d->name }}{{ $d->note ? " ({$d->note})" : '' }}</option>
                        @endforeach
                    </select>
                @endif
                @error('debtor_id')
                    <p class="mt-1 text-xs text-amber">{{ $message }}</p>
                @enderror
                @if($type === 'debt' && $this->debtors->isEmpty() && ! $useNewDebtor)
                    <p class="mt-1 text-xs text-muted">Belum ada pelanggan. Centang "Nama baru" untuk menambahkan.</p>
                @endif
            </div>

            {{-- Items (debt only) --}}
            @if($type === 'debt')
                <div class="mb-3">
                    <div class="mb-1.5 flex items-center justify-between">
                        <label class="text-xs font-semibold text-muted">Barang / Keterangan</label>
                        @if($this->itemsTotal > 0)
                            <span class="text-[11px] font-semibold text-primary tabular-nums">Total: Rp {{ number_format($this->itemsTotal, 0, ',', '.') }}</span>
                        @endif
                    </div>
                    <div class="space-y-2.5">
                        @foreach($items as $index => $item)
                            <div wire:key="item-{{ $item['id'] ?? $index }}" class="rounded-2xl border border-rule bg-bg/60 p-3">
                                <div class="mb-2 flex items-center justify-between">
                                    <span class="text-[11px] font-semibold text-muted">Barang {{ $index + 1 }}</span>
                                    @if(count($items) > 1)
                                        <button type="button" wire:click="removeItem({{ $index }})" class="text-xs font-semibold text-muted hover:text-amber">Hapus</button>
                                    @endif
                                </div>
                                <input type="text" wire:model="items.{{ $index }}.item_description" placeholder="Contoh: Beras 5kg" class="w-full rounded-full border border-rule bg-surface px-4 py-2.5 text-sm text-ink placeholder-muted focus:border-primary focus:outline-none">
                                @error("items.{$index}.item_description")
                                    <p class="mt-1 text-xs text-amber">{{ $message }}</p>
                                @enderror
                                <div class="mt-2">
                                    <x-currency-input :model="'items.'.$index.'.amount'" :value="$item['amount'] ?? null" placeholder="Harga (opsional)" />
                                    @error("items.{$index}.amount")
                                        <p class="mt-1 text-xs text-amber">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        @endforeach
                    </div>
                    @error('items')
                        <p class="mt-1 text-xs text-amber">{{ $message }}</p>
                    @enderror
                    <button
                        type="button"
                        wire:click="addItem"
                        wire:loading.attr="disabled"
                        wire:target="addItem"
                        class="mt-2.5 flex w-full items-center justify-center gap-1.5 rounded-full border border-dashed border-primary/40 py-2.5 text-xs font-semibold text-primary disabled:opacity-60"
                    >
                        + Tambah barang
                    </button>
                </div>
            @endif

            {{-- Payment amount --}}
            @if($type === 'payment')
                <div class="mb-5">
                    <label class="mb-1 block text-xs font-semibold text-muted">Jumlah Bayar</label>
                    <x-currency-input model="amount" :value="$amount" />
                    @error('amount')
                        <p class="mt-1 text-xs text-amber">{{ $message }}</p>
                    @enderror
                </div>
            @endif

            {{-- Submit --}}
            <button
                wire:click="submit"
                wire:loading.attr="disabled"
                wire:target="submit"
                class="w-full rounded-full bg-primary py-3.5 text-sm font-bold text-white disabled:opacity-60"
            >
                <span wire:loading.remove wire:target="submit">Simpan</span>
                <span wire:loading wire:target="submit">Menyimpan…</span>
            </button>
        </div>
    @endif
</div>
