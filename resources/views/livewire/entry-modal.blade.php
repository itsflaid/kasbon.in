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
                <h2 class="text-base font-bold text-ink">Catat Utang Baru</h2>
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
            </div>

            {{-- Item Description (debt only) --}}
            @if($type === 'debt')
                <div class="mb-3">
                    <label class="mb-1 block text-xs font-semibold text-muted">Barang / Keterangan</label>
                    <input type="text" wire:model="item_description" placeholder="Contoh: Beras 5kg" class="w-full rounded-full border border-rule bg-surface px-4 py-2.5 text-sm text-ink placeholder-muted focus:border-primary focus:outline-none">
                </div>
            @endif

            {{-- Amount --}}
            <div class="mb-5">
                <label class="mb-1 block text-xs font-semibold text-muted">{{ $type === 'debt' ? 'Harga (opsional)' : 'Jumlah Bayar' }}</label>
                <input type="number" wire:model="amount" placeholder="0" min="1" class="w-full rounded-full border border-rule bg-surface px-4 py-2.5 text-sm text-ink placeholder-muted focus:border-primary focus:outline-none tabular-nums">
            </div>

            {{-- Submit --}}
            <button wire:click="submit" class="w-full rounded-full bg-primary py-3.5 text-sm font-bold text-white">Simpan</button>
        </div>
    @endif
</div>
