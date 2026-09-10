<div class="flex min-h-screen flex-col items-center justify-center bg-bg px-6">
    <div class="w-full max-w-sm">
        <h1 class="mb-1 text-center font-display text-2xl font-bold text-ink">Warung Utang</h1>
        <p class="mb-8 text-center text-sm text-muted">Pilih atau buat warung untuk mulai mencatat.</p>

        {{-- Existing Warungs --}}
        @if($warungs->isNotEmpty())
            <div class="mb-6">
                <p class="mb-2 text-xs font-semibold text-muted">Warung saya</p>
                <div class="space-y-2">
                    @foreach($warungs as $w)
                        <button wire:click="selectWarung({{ $w->id }})" class="flex w-full items-center justify-between rounded-xl border border-rule bg-surface p-4 text-left transition-colors hover:border-primary">
                            <div>
                                <div class="text-sm font-bold text-ink">{{ $w->name }}</div>
                                <div class="mt-0.5 text-[11px] text-muted">{{ $w->members_count }} anggota</div>
                            </div>
                            <svg class="h-4 w-4 text-muted" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
                        </button>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Tabs --}}
        <div class="mb-4 flex rounded-full border-[1.5px] border-primary p-0.5">
            <button wire:click="$set('activeTab', 'buat')" class="flex-1 rounded-full py-2 text-sm font-bold transition-colors {{ $activeTab === 'buat' ? 'bg-primary text-white' : 'text-primary' }}">Buat Baru</button>
            <button wire:click="$set('activeTab', 'gabung')" class="flex-1 rounded-full py-2 text-sm font-bold transition-colors {{ $activeTab === 'gabung' ? 'bg-primary text-white' : 'text-primary' }}">Gabung</button>
        </div>

        {{-- Create --}}
        @if($activeTab === 'buat')
            <div>
                <label class="mb-1 block text-xs font-semibold text-muted">Nama Warung</label>
                <input type="text" wire:model="newWarungName" placeholder="Contoh: Warung Bu Siti" class="mb-3 w-full rounded-full border border-rule bg-surface px-4 py-2.5 text-sm text-ink placeholder-muted focus:border-primary focus:outline-none">
                <button wire:click="createWarung" class="w-full rounded-full bg-primary py-3 text-sm font-bold text-white">Buat Warung</button>
                @error('newWarungName') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>
        @else
            <div>
                <label class="mb-1 block text-xs font-semibold text-muted">Kode Undangan (8 karakter)</label>
                <input type="text" wire:model="joinCode" placeholder="Masukkan kode…" maxlength="8" class="mb-3 w-full rounded-full border border-rule bg-surface px-4 py-2.5 text-center font-display text-lg tracking-widest text-ink uppercase placeholder-muted focus:border-primary focus:outline-none">
                <button wire:click="joinWarung" class="w-full rounded-full bg-primary py-3 text-sm font-bold text-white">Gabung Warung</button>
                @error('joinCode') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>
        @endif
    </div>
</div>
