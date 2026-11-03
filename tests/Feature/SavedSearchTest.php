<?php

namespace Tests\Feature;

use App\Enums\PropertyType;
use App\Models\Branch;
use App\Models\SavedSearch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SavedSearchTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        Branch::factory()->create(['region' => 'Leeds']);
    }

    public function test_index_lists_only_the_current_users_searches(): void
    {
        SavedSearch::factory(2)->for($this->user)->create();
        SavedSearch::factory(3)->create();

        $this->actingAs($this->user)
            ->get('/saved-searches')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('SavedSearches/Index')
                ->has('savedSearches', 2)
            );
    }

    public function test_index_exposes_set_criteria_for_the_results_link(): void
    {
        SavedSearch::factory()->for($this->user)->create([
            'property_type' => PropertyType::Flat,
            'max_price' => 300_000,
            'min_bedrooms' => null,
            'region' => 'Leeds',
        ]);

        $this->actingAs($this->user)
            ->get('/saved-searches')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('savedSearches.0.criteria', [
                    'property_type' => 'flat',
                    'max_price' => 300_000,
                    'region' => 'Leeds',
                ])
                ->where('savedSearches.0.property_type_label', 'Flat')
            );
    }

    public function test_store_saves_the_criteria_with_blanks_as_any(): void
    {
        $this->actingAs($this->user)
            ->post('/saved-searches', [
                'property_type' => 'flat',
                'max_price' => '300000',
                'min_bedrooms' => '',
                'region' => 'Leeds',
            ])
            ->assertRedirect('/saved-searches');

        $search = $this->user->savedSearches()->sole();
        $this->assertSame(PropertyType::Flat, $search->property_type);
        $this->assertSame(300_000, $search->max_price);
        $this->assertNull($search->min_bedrooms);
        $this->assertSame('Leeds', $search->region);
    }

    /**
     * @return array<string, array{array<string, mixed>, string}>
     */
    public static function invalidSearches(): array
    {
        return [
            'no criteria' => [[], 'criteria'],
            'only blank criteria' => [['max_price' => '', 'region' => ''], 'criteria'],
            'unknown region' => [['region' => 'Atlantis'], 'region'],
            'unknown property type' => [['property_type' => 'castle'], 'property_type'],
            'negative price' => [['max_price' => '-1'], 'max_price'],
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    #[DataProvider('invalidSearches')]
    public function test_store_rejects_invalid_searches(array $payload, string $errorKey): void
    {
        $this->actingAs($this->user)
            ->post('/saved-searches', $payload)
            ->assertSessionHasErrors($errorKey);

        $this->assertDatabaseCount('saved_searches', 0);
    }

    public function test_store_rejects_a_duplicate_of_an_existing_search(): void
    {
        SavedSearch::factory()->for($this->user)->create([
            'property_type' => PropertyType::Flat,
            'max_price' => 300_000,
            'min_bedrooms' => null,
            'region' => null,
        ]);

        $this->actingAs($this->user)
            ->post('/saved-searches', ['property_type' => 'flat', 'max_price' => '300000'])
            ->assertSessionHasErrors('criteria');

        $this->assertDatabaseCount('saved_searches', 1);
    }

    /**
     * Differing only in a criterion being "any" isn't a duplicate, and nor is
     * another user's identical search.
     */
    public function test_store_allows_searches_that_are_not_exact_duplicates(): void
    {
        SavedSearch::factory()->for($this->user)->create([
            'property_type' => PropertyType::Flat,
            'max_price' => 300_000,
            'min_bedrooms' => 2,
            'region' => null,
        ]);
        SavedSearch::factory()->create([
            'property_type' => PropertyType::Flat,
            'max_price' => 300_000,
            'min_bedrooms' => null,
            'region' => null,
        ]);

        $this->actingAs($this->user)
            ->post('/saved-searches', ['property_type' => 'flat', 'max_price' => '300000'])
            ->assertSessionHasNoErrors();

        $this->assertSame(2, $this->user->savedSearches()->count());
    }

    public function test_store_caps_how_many_searches_a_user_keeps(): void
    {
        SavedSearch::factory(SavedSearch::MAX_PER_USER)->for($this->user)->create();

        $this->actingAs($this->user)
            ->post('/saved-searches', ['region' => 'Leeds'])
            ->assertSessionHasErrors('criteria');

        $this->assertSame(SavedSearch::MAX_PER_USER, $this->user->savedSearches()->count());
    }

    public function test_a_user_can_delete_their_own_search(): void
    {
        $search = SavedSearch::factory()->for($this->user)->create();

        $this->actingAs($this->user)
            ->delete("/saved-searches/{$search->id}")
            ->assertRedirect('/saved-searches');

        $this->assertModelMissing($search);
    }

    public function test_a_user_cannot_delete_someone_elses_search(): void
    {
        $search = SavedSearch::factory()->create();

        $this->actingAs($this->user)
            ->delete("/saved-searches/{$search->id}")
            ->assertForbidden();

        $this->assertModelExists($search);
    }

    public function test_deleting_a_user_deletes_their_searches(): void
    {
        $search = SavedSearch::factory()->for($this->user)->create();

        $this->user->delete();

        $this->assertModelMissing($search);
    }
}
