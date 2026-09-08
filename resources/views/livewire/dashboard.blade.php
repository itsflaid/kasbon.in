<div>
    <h1 class="text-2xl font-bold">Dashboard</h1>

    @if($entries->isEmpty())
        <p class="mt-4 text-gray-500">Belum ada entri.</p>
    @else
        <ul class="mt-4 divide-y">
            @foreach($entries as $entry)
                <li class="py-3 flex justify-between items-center">
                    <div>
                        <span class="font-medium">{{ $entry->debtor->name }}</span>
                        <span class="ml-2 text-sm {{ $entry->type === 'debt' ? 'text-amber-600' : 'text-green-600' }}">
                            {{ $entry->type === 'debt' ? 'Utang' : 'Bayar' }}
                        </span>
                        @if($entry->item_description)
                            <span class="block text-sm text-gray-500">{{ $entry->item_description }}</span>
                        @endif
                    </div>
                    <div class="text-right">
                        @if($entry->amount)
                            <span class="font-semibold">Rp {{ number_format($entry->amount, 0, ',', '.') }}</span>
                        @else
                            <span class="text-xs text-amber-500">Harga belum diisi</span>
                        @endif
                        <span class="block text-xs text-gray-400">oleh {{ $entry->recordedBy->name }}</span>
                    </div>
                </li>
            @endforeach
        </ul>
    @endif
</div>
