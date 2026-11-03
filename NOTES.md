# Notes — Saved-Search Alerts

## What's built
- **Saved searches (part 1):** create, list and delete, scoped to `auth()->user()` and guarded by a policy. A search uses the same four criteria as the listings filters and reuses their validation.
- **Alerts (part 2):** when a listing goes live, a queued listener writes an `alerts` row for every user with a matching search. To try it by hand, run `php artisan listings:publish {reference}` and then `php artisan queue:work --once`.
- **Seeing alerts (part 3):** not built yet. The table already has `read_at` and an index on `(user_id, created_at)` for this.

## Key decisions
- **What counts as a match.** It's exactly what the listings filters do: price ≤ max, bedrooms ≥ min, the same property type, and the branch's region. A criterion left empty means "any". If a saved search's results link shows a listing, that listing alerts.
- **Criteria are columns, not JSON.** Alerting asks "which searches does this listing match?", and plain columns let the database answer that in one query. Because I also need the reverse query, there are two definitions of a match: `Listing::matching()` and `SavedSearch::matchingListing()`. A test checks that they agree.
- **The trigger is a model event, not a controller.** Nothing in the app publishes listings, so `Listing` fires `ListingWentLive` when it's created live or its status changes to live. Whatever publishing path is added later will alert without extra work. The listener is queued and runs after the save is committed, so publishing never waits on a scan of all saved searches.
- **One alert per user per listing,** enforced by a unique index. Two matching searches give one alert, which records the first search that matched. A listing coming back from under offer alerts people who haven't heard about it, but not anyone who has. A retried job doesn't create duplicates.
- **No backfill.** A new search only alerts on listings that go live after it's saved. The listings already on the market are one click away through the search's results link, and alerting on them would flood users the moment they save, which is the opposite of what Support asked for.
- **Not spamming people.** An alert is a record in the app, not an email. Users can keep at most 20 searches, and exact duplicate searches are rejected. If we add email, I'd send a scheduled daily digest of unread alerts rather than one email per alert.

## Left out deliberately
- Real email and digests, unsubscribe or pause per search, and editing a search.
- Price ranges (a minimum price). They're easy to add, but the existing filters only have a max, and I kept the two in step.
- Admin tooling. The artisan command is just there to demo the feature.

## Where it won't hold up yet
- **Matching cost grows with the number of saved searches.** "Empty or ≤ / ≥" conditions don't use indexes well, so every listing that goes live reads every saved search. Region and property type are equality matches, so splitting or indexing on those would be the first fix. At larger scale I'd batch matching on a schedule instead of running it per listing.
- **Bulk updates are invisible.** A query like `Listing::query()->update(['status' => 'live'])` skips model events, so it won't alert. A scheduled sweep for listings that went live since the last run would catch these as a safety net.
- **Listings that come off the market.** Alerts stay after a listing is sold or withdrawn. The alerts page will need to hide them or mark them, because `Listings/Show` returns 404 for listings that aren't live.
- **Region is a free-text field on branches** and is compared case-sensitively. A regions table with a foreign key would make this reliable.

## With more time
Build part 3 (the alerts list with read/unread state and a count in the nav), add the daily digest notification, and add price ranges to both the filters and saved searches together.
