<?php

use App\Enums\ListingStatus;
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
    $listing->listed_at = now();
    $listing->save();

    $this->info("{$reference} is live. Alerts so far: {$listing->alerts()->count()}");
    $this->line('With a non-sync queue, run `php artisan queue:work --once` to generate them.');

    return 0;
})->purpose('Make a listing live, alerting matching saved searches');
