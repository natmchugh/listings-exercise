# Street Listings

A small property-listings application: a Laravel 13 API with a Vue 3 front end. It lists properties for sale, organised by branch, with filtering and a detail view.

This repository is the starting point for a technical exercise. **Your task is described in [`TASK.md`](TASK.md).** This README covers what's here and how to run it.

## Requirements

- PHP 8.4
- Composer
- Node 20+ and npm
- SQLite (bundled with PHP — no separate database server needed)

## Getting started

```bash
# 1. Install dependencies
#    composer install also creates .env from .env.example (if missing),
#    creates database/database.sqlite, and sets APP_KEY when it's blank.
composer install
npm install

# 2. Database — migrate and seed
php artisan migrate --seed

# 3. Run — in two terminals, or use the combined dev script
php artisan serve      # API + app at http://127.0.0.1:8000
npm run dev            # Vite dev server for the front end
```

Then open <http://127.0.0.1:8000>.

## Running the checks

```bash
php artisan test              # PHPUnit feature/unit tests
vendor/bin/pint --test        # Code style (Laravel Pint)
vendor/bin/phpstan analyse    # Static analysis (Larastan, level 5)
```

All three run in CI (`.github/workflows/ci.yml`) on push and pull request, and all pass on a fresh clone.

## How it's laid out

```
app/
  Enums/                 PropertyType, ListingStatus
  Events/                ListingWentLive (fired by Listing on going live)
  Http/
    Controllers/         ListingController, SavedSearchController, AlertController
    Requests/            ListingIndexRequest (filter validation)
                         StoreSavedSearchRequest (cap, duplicates, empty search)
                         Concerns/ListingCriteriaRules (rules shared by both)
    Resources/           ListingResource, BranchResource,
                         SavedSearchResource, AlertResource
    Middleware/          ActAsDemoUser (auth stub — see below)
                         HandleInertiaRequests (shared props, unread alert count)
  Listeners/             CreateAlertsForMatchingSearches (queued)
  Models/                Branch, Listing, SavedSearch, Alert, User
  Policies/              SavedSearchPolicy
database/
  factories/             BranchFactory, ListingFactory (with states),
                         SavedSearchFactory, AlertFactory
  migrations/            branches, listings, saved_searches, alerts
  seeders/               DatabaseSeeder
resources/js/
  pages/Listings/        Index.vue, Show.vue
  pages/SavedSearches/   Index.vue
  pages/Alerts/          Index.vue
  components/            AppLayout, ListingCard, ListingFilters, Pagination
  app.js                 Inertia entry point
routes/                  web.php, console.php (listings:draft, listings:publish)
tests/Feature/           ListingPageTest, ListingTest,
                         SavedSearchTest, ListingAlertTest, AlertTest
```

### The domain

- **Branch** — a name and a region.
- **Listing** — address, price, bedrooms, bathrooms, `property_type`, `status` (`draft` / `live` / `under_offer` / `sold`), a branch, and a `listed_at` date.
- **SavedSearch** — a user's criteria (`max_price`, `min_bedrooms`, `property_type`, `region`), the same four as the listings filters. A null criterion means "any".
- **Alert** — a user was told about a listing, because one of their saved searches matched it when it went live. One per user per listing; `read_at` is set once it has been seen.

### The stack

Laravel + **Inertia** + Vue 3 + Tailwind — the same shape as our internal apps. There is no separate JSON API: controllers return `Inertia::render(...)` with props, and API Resources (`ListingResource`, `BranchResource`) define the shape those props take.

| Method | Path                 | Page              | Notes                                                      |
| ------ | -------------------- | ----------------- | ---------------------------------------------------------- |
| GET    | `/`                  | `Listings/Index`  | Live listings, paginated. Filters: `property_type`, `max_price`, `min_bedrooms`, `region`, `per_page`. |
| GET    | `/listings/{listing}`| `Listings/Show`   | A single live listing. Non-live listings 404.              |
| GET    | `/saved-searches`    | `SavedSearches/Index` | The user's saved searches, newest first.               |
| POST   | `/saved-searches`    | —                 | Save the current filters as a search. Redirects to the index. |
| DELETE | `/saved-searches/{savedSearch}` | —      | Delete one of the user's searches (`SavedSearchPolicy`).   |
| GET    | `/alerts`            | `Alerts/Index`    | The user's alerts, newest first, paginated. Marks the page shown as read. |

Filters live in the query string, so a search is shareable, bookmarkable and survives the back button. `pages/Listings/Index.vue` seeds its form from the `filters` prop and re-issues a `router.get` on submit.

### Alerts

When a listing is created live, or its status changes to live, `Listing` fires `ListingWentLive`. The queued `CreateAlertsForMatchingSearches` listener writes an alert for every user with a matching search. Bulk query-builder updates skip model events, so they don't alert.

`.env.example` uses the `database` queue, so run a worker to process alerts (or set `QUEUE_CONNECTION=sync`). Nothing in the app publishes listings yet, so two demo commands let you try it by hand:

```bash
php artisan listings:draft              # creates a draft and prints its reference
php artisan listings:publish STR-XXXXXX # makes it live, firing ListingWentLive
php artisan queue:work --once           # processes the alerting job
```

`listings:draft` uses the model factory, so it needs dev dependencies. See [`NOTES.md`](NOTES.md) for the design decisions behind this.

### Authentication

Auth is **stubbed**. A real deployment would authenticate requests properly; to keep this exercise focused on the feature rather than on auth plumbing, every request is resolved as the seeded demo user (`demo@street.example`) via `App\Http\Middleware\ActAsDemoUser`, and shared to the front end as the `auth.user` prop. Build any user-scoped work against `$request->user()` / `auth()->user()` as you normally would — it will return the demo user.

Note the resolver returns `null` until the database is seeded, so run `php artisan migrate --seed` before you start.

---

Your task: **[`TASK.md`](TASK.md)**.
