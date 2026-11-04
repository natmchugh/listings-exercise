<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ListingCriteriaRules;
use Illuminate\Foundation\Http\FormRequest;

class ListingIndexRequest extends FormRequest
{
    use ListingCriteriaRules;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            ...$this->criteriaRules(),
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
