<?php

namespace Tests\Feature;

use App\Enums\ListingStatus;
use App\Models\Alert;
use App\Models\SavedSearch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AlertTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    public function test_index_lists_only_the_current_users_alerts_newest_first(): void
    {
        $older = Alert::factory()->for($this->user)->create(['created_at' => now()->subDay()]);
        $newer = Alert::factory()->for($this->user)->create(['created_at' => now()]);
        Alert::factory(2)->create();

        $this->actingAs($this->user)
            ->get('/alerts')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Alerts/Index')
                ->has('alerts.data', 2)
                ->where('alerts.data.0.id', $newer->id)
                ->where('alerts.data.1.id', $older->id)
            );
    }

    public function test_unread_alerts_show_as_new_once_and_are_then_marked_read(): void
    {
        $alert = Alert::factory()->for($this->user)->create();

        $this->actingAs($this->user)
            ->get('/alerts')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('alerts.data.0.is_unread', true)
                // The nav badge is counted after the page is marked read.
                ->where('unreadAlertsCount', 0)
            );

        $this->assertNotNull($alert->fresh()?->read_at);

        $this->actingAs($this->user)
            ->get('/alerts')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('alerts.data.0.is_unread', false)
            );
    }

    public function test_only_alerts_on_the_viewed_page_are_marked_read(): void
    {
        $alerts = collect(range(1, 3))->map(fn (int $daysAgo) => Alert::factory()
            ->for($this->user)
            ->create(['created_at' => now()->subDays($daysAgo)]));
        $othersAlert = Alert::factory()->create();

        $this->actingAs($this->user)->get('/alerts?per_page=2')->assertOk();

        $this->assertNotNull($alerts[0]->fresh()?->read_at);
        $this->assertNotNull($alerts[1]->fresh()?->read_at);
        $this->assertNull($alerts[2]->fresh()?->read_at);
        $this->assertNull($othersAlert->fresh()?->read_at);
    }

    public function test_an_oversized_page_is_rejected_without_marking_anything_read(): void
    {
        $alert = Alert::factory()->for($this->user)->create();

        $this->actingAs($this->user)
            ->get('/alerts?per_page=1000')
            ->assertSessionHasErrors('per_page');

        $this->assertNull($alert->fresh()?->read_at);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function prefetchHeaders(): array
    {
        return [
            'inertia prefetch' => ['Purpose', 'prefetch'],
            'browser prefetch' => ['Sec-Purpose', 'prefetch'],
            'browser prerender' => ['Sec-Purpose', 'prefetch;prerender'],
        ];
    }

    #[DataProvider('prefetchHeaders')]
    public function test_a_prefetch_does_not_mark_alerts_read(string $header, string $value): void
    {
        $alert = Alert::factory()->for($this->user)->create();

        $this->actingAs($this->user)
            ->get('/alerts', [$header => $value])
            ->assertOk();

        $this->assertNull($alert->fresh()?->read_at);
    }

    public function test_an_already_read_alert_keeps_when_it_was_read(): void
    {
        $readAt = now()->subWeek()->startOfSecond();
        $alert = Alert::factory()->for($this->user)->create(['read_at' => $readAt]);

        $this->actingAs($this->user)->get('/alerts')->assertOk();

        $this->assertTrue($readAt->equalTo($alert->fresh()?->read_at));
    }

    public function test_an_alert_for_a_listing_no_longer_live_is_marked_unavailable(): void
    {
        $alert = Alert::factory()->for($this->user)->create();
        $alert->listing->update(['status' => ListingStatus::Sold]);

        $this->actingAs($this->user)
            ->get('/alerts')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('alerts.data.0.listing_available', false)
                ->where('alerts.data.0.listing.status', 'sold')
            );
    }

    public function test_an_alert_shows_the_search_that_matched_until_it_is_deleted(): void
    {
        // The alert comes first: a search saved before the listing went live
        // would have alerted through the listener already.
        $alert = Alert::factory()->for($this->user)->create();
        $search = SavedSearch::factory()->for($this->user)->create();
        $alert->update(['saved_search_id' => $search->id]);

        $this->actingAs($this->user)
            ->get('/alerts')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('alerts.data.0.saved_search.id', $search->id)
                ->where('alerts.data.0.listing_available', true)
            );

        $search->delete();

        $this->actingAs($this->user)
            ->get('/alerts')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('alerts.data.0.saved_search', null)
            );
    }

    public function test_the_unread_count_is_shared_with_other_pages(): void
    {
        Alert::factory(2)->for($this->user)->create();
        Alert::factory()->for($this->user)->create(['read_at' => now()]);
        Alert::factory()->create();

        $this->actingAs($this->user)
            ->get('/saved-searches')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('unreadAlertsCount', 2)
            );
    }
}
