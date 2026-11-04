<?php

namespace App\Events;

use App\Models\Listing;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * A listing has just come onto the market: created live, published from draft,
 * or back from under offer.
 */
class ListingWentLive
{
    use Dispatchable, SerializesModels;

    public function __construct(public Listing $listing) {}
}
