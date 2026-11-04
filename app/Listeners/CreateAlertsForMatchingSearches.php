<?php

namespace App\Listeners;

use App\Enums\ListingStatus;
use App\Events\ListingWentLive;
use App\Models\Alert;
use App\Models\SavedSearch;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Queue\Attributes\DeleteWhenMissingModels;

/**
 * Queued so that publishing a listing doesn't wait on a scan of every saved
 * search, and after commit so the worker never sees a listing that rolled back.
 *
 * The event's listing is re-fetched when the job is unserialized, so it is
 * already current. If it was deleted while the job sat on the queue, that
 * re-fetch fails and the job is dropped instead of failing.
 */
#[DeleteWhenMissingModels]
class CreateAlertsForMatchingSearches implements ShouldHandleEventsAfterCommit, ShouldQueue
{
    public function handle(ListingWentLive $event): void
    {
        $listing = $event->listing->loadMissing('branch');

        // It may have been withdrawn while the job sat on the queue.
        if ($listing->status !== ListingStatus::Live) {
            return;
        }

        SavedSearch::query()
            ->matchingListing($listing)
            ->chunkById(500, function (Collection $searches) use ($listing) {
                $now = now();

                // The lowest-id match per user is recorded as the reason. The
                // unique (user_id, listing_id) index drops anyone already
                // alerted: a match in an earlier chunk, a re-listing, a retry.
                $rows = $searches
                    ->unique('user_id')
                    ->map(fn (SavedSearch $search) => [
                        'user_id' => $search->user_id,
                        'listing_id' => $listing->id,
                        'saved_search_id' => $search->id,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ])
                    ->values()
                    ->all();

                Alert::query()->insertOrIgnore($rows);
            });
    }
}
