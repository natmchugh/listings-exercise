<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ListingCriteriaRules;
use App\Models\SavedSearch;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Blank fields arrive as null (the global ConvertEmptyStringsToNull middleware),
 * so "any" is always stored as NULL rather than an empty string.
 */
class StoreSavedSearchRequest extends FormRequest
{
    use ListingCriteriaRules;

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = $this->criteriaRules();

        // The index filter tolerates any region (it just finds nothing), but a
        // saved search with a mistyped region would silently never alert.
        $rules['region'][] = Rule::exists('branches', 'region');

        return $rules;
    }

    /**
     * Errors that aren't about a single field are reported under `criteria`.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $criteria = $this->criteria();

                if ($criteria === []) {
                    // An empty search matches every new listing.
                    $validator->errors()->add('criteria', 'Choose at least one filter before saving a search.');

                    return;
                }

                /** @var User $user */
                $user = $this->user();
                $searches = $user->savedSearches();

                if ($searches->clone()->count() >= SavedSearch::MAX_PER_USER) {
                    $validator->errors()->add('criteria', 'You can keep up to '.SavedSearch::MAX_PER_USER.' saved searches. Delete one to save another.');

                    return;
                }

                $duplicate = $searches->clone();
                foreach (SavedSearch::CRITERIA as $key) {
                    isset($criteria[$key])
                        ? $duplicate->where($key, $criteria[$key])
                        : $duplicate->whereNull($key);
                }

                if ($duplicate->exists()) {
                    $validator->errors()->add('criteria', 'You already have this search saved.');
                }
            },
        ];
    }

    /**
     * The criteria that were given, cast to what's stored.
     *
     * @return array{property_type?: string, max_price?: int, min_bedrooms?: int, region?: string}
     */
    public function criteria(): array
    {
        $criteria = [];

        foreach (SavedSearch::CRITERIA as $key) {
            if ($this->filled($key)) {
                $criteria[$key] = in_array($key, ['max_price', 'min_bedrooms'], true)
                    ? $this->integer($key)
                    : $this->string($key)->toString();
            }
        }

        return $criteria;
    }
}
