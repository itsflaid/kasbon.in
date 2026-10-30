<?php

namespace App\Livewire;

use App\Livewire\Concerns\EnsuresWarungMembership;
use App\Models\Debtor;
use App\Models\Entry;
use App\Models\Warung;
use App\Services\EntryService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Component;

class EntryModal extends Component
{
    use EnsuresWarungMembership;

    #[Locked]
    public Warung $warung;

    public string $type = 'debt';

    public ?int $debtor_id = null;

    /** @var array<int, array{id?: int, item_description?: string, amount?: int|string|null}> */
    public array $items = [
        ['id' => 1, 'item_description' => '', 'amount' => null],
    ];

    public int $nextItemId = 2;

    public ?int $amount = null;

    public string $newDebtorName = '';

    public bool $useNewDebtor = false;

    public bool $showConfirm = false;

    /** @var array<string, mixed>|null */
    #[Locked]
    public ?array $pendingData = null;

    public string $warning = '';

    public function mount(Warung $warung, ?int $debtorId = null, string $initialType = 'debt'): void
    {
        $this->warung = $warung;
        $this->debtor_id = $debtorId;
        $this->type = in_array($initialType, ['debt', 'payment'], true) ? $initialType : 'debt';
        $this->useNewDebtor = $this->type === 'debt' && ! $this->warung->debtors()->exists();
    }

    /**
     * @return Collection<int, Debtor>
     */
    public function getDebtorsProperty(): Collection
    {
        return $this->warung->debtors()->orderBy('name')->get();
    }

    public function getItemsTotalProperty(): int
    {
        return collect($this->items)->sum(fn (array $item): int => (int) ($item['amount'] ?? 0));
    }

    public function addItem(): void
    {
        $this->items[] = [
            'id' => $this->nextItemId++,
            'item_description' => '',
            'amount' => null,
        ];
    }

    public function removeItem(int $index): void
    {
        if (count($this->items) <= 1 || ! isset($this->items[$index])) {
            return;
        }

        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    public function updatedType(): void
    {
        if ($this->type === 'payment') {
            $this->useNewDebtor = false;
            $this->newDebtorName = '';
        }

        $this->resetValidation();
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'debtor_id.required_if' => 'Pilih pelanggan atau isi nama baru.',
            'debtor_id.exists' => 'Pelanggan tidak ditemukan.',
            'newDebtorName.required_if' => 'Nama pelanggan baru wajib diisi.',
            'items.required' => 'Tambahkan minimal satu barang.',
            'items.min' => 'Tambahkan minimal satu barang.',
            'items.*.item_description.required' => 'Nama barang wajib diisi.',
            'items.*.item_description.min' => 'Nama barang minimal 1 karakter.',
            'items.*.amount.integer' => 'Harga harus berupa angka bulat.',
            'items.*.amount.min' => 'Harga minimal 1.',
            'amount.required' => 'Jumlah bayar wajib diisi.',
            'amount.integer' => 'Jumlah harus berupa angka bulat.',
            'amount.min' => 'Jumlah minimal 1.',
        ];
    }

    public function submit(): void
    {
        $items = [];

        if ($this->type === 'debt') {
            if ($this->useNewDebtor) {
                $this->debtor_id = null;
            }

            $items = $this->normalizeItems();
            $this->items = $items;
        }

        $debtorExists = Rule::exists('debtors', 'id')
            ->where(fn ($query) => $query->where('warung_id', $this->warung->id));

        $this->validate($this->type === 'debt' ? [
            'debtor_id' => ['nullable', 'required_if:useNewDebtor,false', $debtorExists],
            'useNewDebtor' => ['boolean'],
            'newDebtorName' => ['required_if:useNewDebtor,true', 'string', 'min:1', 'max:'.Entry::MAX_DESCRIPTION],
            'items' => ['required', 'array', 'min:1', 'max:'.Entry::MAX_ITEMS_PER_SUBMIT],
            'items.*.item_description' => ['required', 'string', 'min:1', 'max:'.Entry::MAX_DESCRIPTION],
            'items.*.amount' => ['nullable', 'integer', 'min:1', 'max:'.Entry::MAX_AMOUNT],
        ] : [
            'debtor_id' => ['required', $debtorExists],
            'amount' => ['required', 'integer', 'min:1', 'max:'.Entry::MAX_AMOUNT],
        ]);

        $debtorId = $this->useNewDebtor ? null : $this->debtor_id;
        $newDebtorName = $this->useNewDebtor ? $this->newDebtorName : null;

        if ($this->type === 'debt') {
            app(EntryService::class)->createMany(
                $this->warung,
                $debtorId,
                $items,
                auth()->user(),
                $newDebtorName,
            );

            $this->close();

            return;
        }

        $result = app(EntryService::class)->create($this->warung, [
            'debtor_id' => $debtorId,
            'type' => 'payment',
            'item_description' => null,
            'amount' => $this->amount,
        ], auth()->user());

        if ($result['status'] === 'warning') {
            $this->warning = $result['message'];
            $this->pendingData = $result['data'];
            $this->showConfirm = true;

            return;
        }

        $this->close();
    }

    public function confirmSubmit(): void
    {
        if ($this->pendingData) {
            app(EntryService::class)->confirmCreate($this->warung, $this->pendingData, auth()->user());
        }

        $this->close();
    }

    public function close(): void
    {
        $this->resetExcept('warung');
        $this->dispatch('close-modal');
    }

    public function render(): View
    {
        return view('livewire.entry-modal');
    }

    protected function membershipWarungId(): int
    {
        return $this->warung->id;
    }

    /**
     * @return array<int, array{id: int, item_description: string, amount: int|null}>
     */
    private function normalizeItems(): array
    {
        $items = collect($this->items)
            ->filter(function (array $item): bool {
                return trim((string) ($item['item_description'] ?? '')) !== ''
                    || trim((string) ($item['amount'] ?? '')) !== '';
            })
            ->map(function (array $item): array {
                $digits = preg_replace('/\D/', '', (string) ($item['amount'] ?? ''));
                $digits = $digits !== '' ? substr($digits, 0, strlen((string) Entry::MAX_AMOUNT)) : '';

                return [
                    'id' => (int) ($item['id'] ?? 0),
                    'item_description' => trim((string) ($item['item_description'] ?? '')),
                    'amount' => $digits !== '' ? (int) $digits : null,
                ];
            })
            ->values()
            ->all();

        return $items !== [] ? $items : [
            ['id' => 1, 'item_description' => '', 'amount' => null],
        ];
    }
}
