<?php

namespace App\Http\Requests;

use App\Models\Entry;
use App\Models\Warung;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        $warung = $this->route('warung');

        return $warung instanceof Warung
            && $this->user()?->can('create', [Entry::class, $warung]) === true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $warung = $this->route('warung');

        return [
            'debtor_id' => [
                'required',
                'integer',
                Rule::exists('debtors', 'id')->where(
                    fn ($query) => $query->where('warung_id', $warung instanceof Warung ? $warung->id : 0),
                ),
            ],
            'type' => ['required', Rule::in(['debt', 'payment'])],
            'item_description' => [
                'nullable',
                'string',
                'max:'.Entry::MAX_DESCRIPTION,
                'required_if:type,debt',
            ],
            'amount' => [
                'nullable',
                'integer',
                'min:1',
                'max:'.Entry::MAX_AMOUNT,
                'required_if:type,payment',
            ],
        ];
    }
}
