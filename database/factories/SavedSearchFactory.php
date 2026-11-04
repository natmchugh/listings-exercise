<?php

namespace Database\Factories;

use App\Enums\PropertyType;
use App\Models\SavedSearch;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SavedSearch>
 */
class SavedSearchFactory extends Factory
{
    protected $model = SavedSearch::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'max_price' => fake()->numberBetween(15, 80) * 10_000,
            'min_bedrooms' => fake()->numberBetween(1, 4),
            'property_type' => fake()->randomElement(PropertyType::cases()),
            'region' => null,
        ];
    }
}
