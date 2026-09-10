<?php

namespace App\Livewire;

use App\Models\Debtor;
use App\Models\Warung;
use Livewire\Component;

class EntryModal extends Component
{
    public Warung $warung;

    public string $type = 'debt';

    public ?int $debtor_id = null;

    public string $item_description = '';

    public ?int $amount = null;

    public string $newDebtorName = '';

    public bool $useNewDebtor = false;

    public bool $showConfirm = false;

    public ?array $pendingData = null;

    public string $warning = '';

    public function getDebtorsProperty()
    {
        return $this->warung->debtors()->orderBy('name')->get();
    }

    public function updatedType(): void
    {
        $this->resetValidation();
    }

    public function submit(): void
    {
        $this->validate($this->type === 'debt' ? [
            'debtor_id' => 'required_without:useNewDebtor|exists:debtors,id',
            'useNewDebtor' => 'required_without:debtor_id|boolean',
            'newDebtorName' => 'required_if:useNewDebtor,true|string|min:1|max:255',
            'item_description' => 'required|string|min:1|max:255',
            'amount' => 'nullable|integer|min:1',
        ] : [
            'debtor_id' => 'required|exists:debtors,id',
            'amount' => 'required|integer|min:1',
        ]);

        $debtorId = $this->debtor_id;

        if ($this->useNewDebtor && $this->newDebtorName !== '') {
            $debtor = Debtor::firstOrCreate(
                ['warung_id' => $this->warung->id, 'normalized_name' => strtolower(trim($this->newDebtorName))],
                ['name' => $this->newDebtorName]
            );
            $debtorId = $debtor->id;
        }

        $data = [
            'debtor_id' => $debtorId,
            'type' => $this->type,
            'item_description' => $this->type === 'debt' ? $this->item_description : null,
            'amount' => $this->amount,
        ];

        $response = $this->httpPost(route('entries.store', $this->warung), $data);

        if ($response['status'] === 200 && isset($response['body']['warning'])) {
            $this->warning = $response['body']['warning'];
            $this->pendingData = $data;
            $this->showConfirm = true;

            return;
        }

        $this->close();
    }

    public function confirmSubmit(): void
    {
        if ($this->pendingData) {
            $this->httpPost(route('entries.confirm-store', $this->warung), $this->pendingData);
        }

        $this->close();
    }

    public function close(): void
    {
        $this->reset();
        $this->dispatch('close-modal');
    }

    public function render()
    {
        return view('livewire.entry-modal');
    }
}
