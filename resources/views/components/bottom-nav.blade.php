@props(['active' => 'dashboard'])

@php
    $items = [
        ['key' => 'dashboard', 'label' => 'Warung', 'route' => 'dashboard', 'icon' => 'home'],
        ['key' => 'riwayat', 'label' => 'Riwayat', 'route' => 'riwayat', 'icon' => 'history'],
        ['key' => 'anggota', 'label' => 'Anggota', 'route' => 'anggota', 'icon' => 'users'],
        ['key' => 'profil', 'label' => 'Profil', 'route' => 'profil', 'icon' => 'user'],
    ];
@endphp

<nav class="fixed bottom-0 left-0 right-0 z-40 border-t border-rule bg-surface px-2 pb-3 pt-2">
    <div class="mx-auto flex max-w-lg">
        @foreach ($items as $item)
            <a
                href="{{ route($item['route']) }}"
                wire:navigate
                class="flex flex-1 flex-col items-center gap-0.5 text-[10.5px] font-semibold transition-colors {{ $active === $item['key'] ? 'text-primary' : 'text-muted' }}"
            >
                @switch($item['icon'])
                    @case('home')
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                        @break
                    @case('history')
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        @break
                    @case('users')
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                        @break
                    @case('user')
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        @break
                @endswitch
                {{ $item['label'] }}
            </a>
        @endforeach
    </div>
</nav>
