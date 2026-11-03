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

                // The cap and the duplicate check both read and then insert, so
                // two saves at the same moment can both pass, leaving a
                // duplicate or a 21st search. That's accepted here: it needs a
                // double submit, and the only cost is a redundant row. Closing
                // it means moving both checks next to the insert, in one
                // transaction that locks the user row first.
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
     * The criteria that were given, as validated. Types are left to the
     * SavedSearch casts, so a new criterion only needs a rule and a cast.
     *
     * @return array<string, mixed>
     */
    public function criteria(): array
    {
        return array_filter(
            $this->safe()->only(SavedSearch::CRITERIA),
            fn (mixed $value) => $value !== null,
        );
    }
}
