<?php

namespace App\Http\Requests;

use App\Models\Entry;
use Illuminate\Foundation\Http\FormRequest;

class UpdateEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        $entry = $this->route('entry');

        return $entry instanceof Entry
            && $this->user()?->can('update', $entry) === true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'amount' => ['required', 'integer', 'min:1', 'max:'.Entry::MAX_AMOUNT],
            'item_description' => ['nullable', 'string', 'max:'.Entry::MAX_DESCRIPTION],
        ];
    }
}
