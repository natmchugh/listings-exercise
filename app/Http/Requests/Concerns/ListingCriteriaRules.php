<?php

namespace App\Http\Requests\Concerns;

use App\Enums\PropertyType;
use App\Models\Listing;
use Illuminate\Validation\Rules\Enum;

/**
 * The criteria a listing search can be narrowed by. Shared by the listings
 * index filters and saved searches, so the two can't drift apart.
 *
 * @see Listing::scopeMatching()
 */
trait ListingCriteriaRules
{
    /**
     * @return array<string, list<mixed>>
     */
    protected function criteriaRules(): array
    {
        return [
            'property_type' => ['nullable', new Enum(PropertyType::class)],
            // Upper bound is the unsignedInteger column it's stored in.
            'max_price' => ['nullable', 'integer', 'min:0', 'max:4294967295'],
            'min_bedrooms' => ['nullable', 'integer', 'min:0', 'max:20'],
            'region' => ['nullable', 'string', 'max:100'],
        ];
    }
}
