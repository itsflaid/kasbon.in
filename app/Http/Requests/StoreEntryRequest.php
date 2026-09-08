<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'debtor_id' => ['required', 'exists:debtors,id'],
            'type' => ['required', 'in:debt,payment'],
            'item_description' => ['nullable', 'string', 'required_if:type,debt'],
            'amount' => ['nullable', 'numeric', 'min:1', 'required_if:type,payment'],
        ];
    }
}
