<?php

use App\Enums\ListingStatus;
use App\Models\Branch;
use App\Models\Listing;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// There's no back office in this app, so this is how a listing goes live by
// hand. It saves through the model like any real publishing path would, so
// saved-search alerts are generated (queued; run queue:work to process them).
Artisan::command('listings:publish {reference}', function (string $reference) {
    $listing = Listing::query()->where('reference', $reference)->first();

    if ($listing === null) {
        $this->error("No listing with reference {$reference}.");

        return 1;
    }

    if ($listing->status === ListingStatus::Live) {
        $this->warn("{$reference} is already live.");

        return 0;
    }

    $listing->status = ListingStatus::Live;
    // Deliberately reset for any non-live status, including Under Offer and
    // Sold: this is a demo tool, and a fresh date puts the listing at the top
    // of the index where you can see it. A real relisting flow would decide
    // whether to keep the original date.
    $listing->listed_at = now();
    $listing->save();

    $this->info("{$reference} is live. Alerts so far: {$listing->alerts()->count()}");
    $this->line('With a non-sync queue, run `php artisan queue:work --once` to generate them.');

    return 0;
})->purpose('Make a listing live, alerting matching saved searches');

// The other half of the manual loop: a fresh draft to publish, built with the
// model factory at a random existing branch. The factory needs dev dependencies,
// so this is a local/demo tool, not for production.
Artisan::command('listings:draft', function () {
    $branch = Branch::query()->inRandomOrder()->first();

    if ($branch === null) {
        $this->error('There are no branches. Run `php artisan migrate --seed` first.');

        return 1;
    }

    $listing = Listing::factory()->draft()->for($branch)->create();

    $this->info(sprintf(
        'Created draft %s (%s, %s, %d bed, £%s).',
        $listing->reference,
        $listing->city,
        $listing->property_type->label(),
        $listing->bedrooms,
        number_format($listing->price),
    ));
    $this->line("Publish it: php artisan listings:publish {$listing->reference}");

    return 0;
})->purpose('Create a draft listing to try listings:publish with');
