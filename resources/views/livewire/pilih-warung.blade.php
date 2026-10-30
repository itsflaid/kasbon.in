<div class="min-h-screen bg-bg text-ink">
    <div class="mx-auto grid min-h-screen w-full max-w-[1180px] lg:grid-cols-[minmax(0,0.92fr)_minmax(0,1.08fr)]">
        <aside class="relative flex flex-col overflow-hidden border-b border-rule px-5 pb-8 pt-6 sm:px-8 lg:border-b-0 lg:border-r lg:px-12 lg:py-10">
            <div
                aria-hidden="true"
                class="pointer-events-none absolute inset-0 opacity-70"
                style="background-image: repeating-linear-gradient(to bottom, transparent 0, transparent 39px, rgba(231, 228, 220, 0.55) 39px, rgba(231, 228, 220, 0.55) 40px);"
            ></div>

            <div class="relative flex items-center justify-between gap-4">
                <div class="flex items-center gap-2.5">
                    <span class="flex h-9 w-9 items-center justify-center rounded-full bg-primary font-display text-[13px] font-bold text-white">W</span>
                    <span class="font-display text-sm font-bold tracking-tight">{{ config('app.name') }}</span>
                </div>
                <span class="text-[10px] font-semibold uppercase tracking-[0.18em] text-muted">{{ now()->format('Y') }} · Buku kas</span>
            </div>

            <div class="relative mt-10 max-w-lg lg:my-auto">
                <h1 class="font-display text-[clamp(2.6rem,7vw,5.4rem)] font-bold leading-[0.9] tracking-[-0.05em] text-ink">
                    Satu warung,<br>
                    <span class="relative inline-block text-primary">
                        satu buku.
                        <svg class="absolute -bottom-2 left-0 h-2.5 w-full" viewBox="0 0 240 10" preserveAspectRatio="none" aria-hidden="true">
                            <path d="M2 6C40 2 76 8 116 4S190 2 238 6" stroke="currentColor" stroke-width="2.5" fill="none" stroke-linecap="round"/>
                        </svg>
                    </span>
                </h1>

                <p class="mt-6 max-w-sm text-[15px] leading-relaxed text-muted">Pilih warung yang mau dicatat, atau buka lembar baru. Utang dan pembayaran tiap warung tersimpan terpisah.</p>
            </div>

        </aside>

        <main class="flex flex-col px-5 pb-10 pt-7 sm:px-8 lg:px-12 lg:py-10">
            <div class="w-full max-w-[28rem] lg:my-auto">
                @if($warungs->isNotEmpty())
                    <section aria-labelledby="lanjutkan-heading">
                        <div class="mb-3 flex items-baseline justify-between gap-4">
                            <h2 id="lanjutkan-heading" class="font-display text-xl font-bold tracking-tight">Lanjutkan</h2>
                            <span class="text-[10px] font-semibold uppercase tracking-[0.16em] text-muted">{{ $warungs->count() }} warung</span>
                        </div>

                        <div class="overflow-hidden rounded-2xl border border-rule bg-surface" wire:loading.class="opacity-60" wire:target="selectWarung">
                            @foreach($warungs as $w)
                                <button
                                    type="button"
                                    wire:key="warung-{{ $w->id }}"
                                    wire:click="selectWarung({{ $w->id }})"
                                    class="group flex w-full items-center gap-3.5 border-b border-rule px-4 py-3.5 text-left transition-colors last:border-b-0 hover:bg-primary-soft/40 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-primary active:bg-primary-soft/70"
                                >
                                    <span class="w-5 shrink-0 font-display text-[10px] font-bold tabular-nums text-muted">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-primary-soft font-display text-sm font-bold text-primary">{{ strtoupper(substr($w->name, 0, 1)) }}</span>
                                    <span class="min-w-0 flex-1">
                                        <span class="block truncate text-[15px] font-semibold text-ink">{{ $w->name }}</span>
                                        <span class="mt-0.5 block truncate text-[11px] text-muted">{{ $w->members_count }} anggota · kode {{ $w->invite_code }}</span>
                                    </span>
                                    <svg class="h-4 w-4 shrink-0 text-muted transition-transform group-hover:translate-x-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M5 12h14"/>
                                        <path d="m13 6 6 6-6 6"/>
                                    </svg>
                                </button>
                            @endforeach
                        </div>
                    </section>

                    <div class="my-7 flex items-center gap-3">
                        <span class="h-px flex-1 bg-rule"></span>
                        <span class="text-[10px] font-semibold uppercase tracking-[0.18em] text-muted">atau</span>
                        <span class="h-px flex-1 bg-rule"></span>
                    </div>
                @else
                    <div class="mb-7 border-y border-rule py-5">
                        <p class="font-display text-xl font-bold tracking-tight">Buku ini masih kosong.</p>
                        <p class="mt-1.5 max-w-sm text-sm leading-relaxed text-muted">Buat warung pertama, atau gabung pakai kode undangan dari pemilik warung.</p>
                    </div>
                @endif

                <section aria-labelledby="aksi-heading" class="overflow-hidden rounded-2xl border border-rule bg-surface">
                    <div class="border-b border-rule px-4 pb-3.5 pt-4">
                        <h2 id="aksi-heading" class="font-display text-lg font-bold tracking-tight">{{ $warungs->isNotEmpty() ? 'Buka warung lain' : 'Mulai dari sini' }}</h2>
                        <p class="mt-1 text-[11px] leading-relaxed text-muted">{{ $warungs->isNotEmpty() ? 'Buat warung baru atau gabung ke warung yang sudah ada.' : 'Pilih salah satu cara untuk masuk ke buku kas.' }}</p>
                    </div>

                    <div class="flex gap-6 border-b border-rule px-4">
                        <button type="button" wire:click="$set('activeTab', 'buat')" aria-pressed="{{ $activeTab === 'buat' ? 'true' : 'false' }}" class="relative -mb-px border-b-2 py-3 text-sm font-semibold transition-colors {{ $activeTab === 'buat' ? 'border-primary text-ink' : 'border-transparent text-muted hover:text-ink' }}">Buat warung</button>
                        <button type="button" wire:click="$set('activeTab', 'gabung')" aria-pressed="{{ $activeTab === 'gabung' ? 'true' : 'false' }}" class="relative -mb-px border-b-2 py-3 text-sm font-semibold transition-colors {{ $activeTab === 'gabung' ? 'border-primary text-ink' : 'border-transparent text-muted hover:text-ink' }}">Gabung</button>
                    </div>

                    <div class="p-4">
                        @if($activeTab === 'buat')
                            <div>
                                <label for="new-warung-name" class="mb-1.5 block text-[11px] font-semibold uppercase tracking-[0.14em] text-muted">Nama warung</label>
                                <input
                                    id="new-warung-name"
                                    type="text"
                                    wire:model="newWarungName"
                                    placeholder="Contoh: Warung Bu Siti"
                                    autocomplete="organization"
                                    class="w-full rounded-xl border border-rule bg-bg px-3.5 py-3 text-sm text-ink placeholder-muted transition-colors focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/15 @error('newWarungName') border-red-400 @enderror"
                                >
                                @error('newWarungName')
                                    <p role="alert" class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                                @enderror

                                <button type="button" wire:click="createWarung" wire:loading.attr="disabled" wire:target="createWarung" class="group mt-3 flex min-h-[44px] w-full items-center justify-center gap-2 rounded-xl bg-primary px-4 py-3 text-sm font-bold text-white transition-transform active:scale-[0.98] disabled:cursor-wait disabled:opacity-70">
                                    <span wire:loading.remove wire:target="createWarung">Buat warung</span>
                                    <span wire:loading wire:target="createWarung">Menyiapkan…</span>
                                    <svg wire:loading.remove wire:target="createWarung" class="h-4 w-4 transition-transform group-hover:translate-x-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M5 12h14"/>
                                        <path d="m13 6 6 6-6 6"/>
                                    </svg>
                                </button>
                            </div>
                        @else
                            <div>
                                <label for="join-code" class="mb-1.5 block text-[11px] font-semibold uppercase tracking-[0.14em] text-muted">Kode undangan</label>
                                <input
                                    id="join-code"
                                    type="text"
                                    wire:model="joinCode"
                                    placeholder="XXXXXXXX"
                                    maxlength="8"
                                    autocomplete="off"
                                    autocapitalize="characters"
                                    spellcheck="false"
                                    class="w-full rounded-xl border border-rule bg-bg px-3.5 py-3 text-center font-display text-xl font-bold uppercase tracking-[0.3em] text-ink placeholder-muted transition-colors focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/15 @error('joinCode') border-red-400 @enderror"
                                >
                                @error('joinCode')
                                    <p role="alert" class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                                <p class="mt-1.5 text-[11px] leading-relaxed text-muted">Minta 8 karakter ini ke pemilik warung.</p>

                                <button type="button" wire:click="joinWarung" wire:loading.attr="disabled" wire:target="joinWarung" class="mt-3 flex min-h-[44px] w-full items-center justify-center gap-2 rounded-xl border-[1.5px] border-primary bg-surface px-4 py-3 text-sm font-bold text-primary transition-colors hover:bg-primary-soft/50 active:scale-[0.98] disabled:cursor-wait disabled:opacity-70">
                                    <span wire:loading.remove wire:target="joinWarung">Gabung warung</span>
                                    <span wire:loading wire:target="joinWarung">Mencari…</span>
                                </button>
                            </div>
                        @endif
                    </div>
                </section>
            </div>
        </main>
    </div>
</div>
