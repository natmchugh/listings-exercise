<?php

namespace App\Http\Resources;

use App\Enums\ListingStatus;
use App\Models\Alert;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Alert
 */
class AlertResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'is_unread' => $this->read_at === null,
            'created_at' => $this->created_at->toIso8601String(),
            'listing' => new ListingResource($this->listing),
            // Alerts outlive the listing being on the market, and the listing
            // page returns 404 for anything that isn't live.
            'listing_available' => $this->listing->status === ListingStatus::Live,
            // Null once the user deletes the search. The alert stays.
            'saved_search' => $this->savedSearch === null ? null : new SavedSearchResource($this->savedSearch),
        ];
    }
}
