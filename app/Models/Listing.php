<?php

namespace App\Models;

use App\Enums\ListingStatus;
use App\Enums\PropertyType;
use App\Events\ListingWentLive;
use Database\Factories\ListingFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * The casts() method tells Eloquent how to hydrate these attributes, but static
 * analysis can't infer that from a string map — these annotations do.
 *
 * @property int $id
 * @property int $branch_id
 * @property string $reference
 * @property string $address_line_1
 * @property string $city
 * @property string $postcode
 * @property int $price
 * @property int $bedrooms
 * @property int $bathrooms
 * @property PropertyType $property_type
 * @property ListingStatus $status
 * @property Carbon|null $listed_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Branch $branch
 */
class Listing extends Model
{
    /** @use HasFactory<ListingFactory> */
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'reference',
        'address_line_1',
        'city',
        'postcode',
        'price',
        'bedrooms',
        'bathrooms',
        'property_type',
        'status',
        'listed_at',
    ];

    /**
     * Announce a listing coming onto the market, however the save happened.
     * Bulk query-builder updates skip model events, so they won't announce.
     */
    protected static function booted(): void
    {
        // Separate hooks rather than one "saved" hook checking
        // wasRecentlyCreated: that flag stays true for the instance's lifetime,
        // so every later edit would look like a fresh listing.
        static::created(function (Listing $listing) {
            if ($listing->status === ListingStatus::Live) {
                ListingWentLive::dispatch($listing);
            }
        });

        static::updated(function (Listing $listing) {
            if ($listing->wasChanged('status') && $listing->status === ListingStatus::Live) {
                ListingWentLive::dispatch($listing);
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'bedrooms' => 'integer',
            'bathrooms' => 'integer',
            'property_type' => PropertyType::class,
            'status' => ListingStatus::class,
            'listed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Branch, $this>
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * @return HasMany<Alert, $this>
     */
    public function alerts(): HasMany
    {
        return $this->hasMany(Alert::class);
    }

    /**
     * Only listings that are currently on the market.
     *
     * @param  Builder<Listing>  $query
     */
    public function scopeLive(Builder $query): void
    {
        $query->where('status', ListingStatus::Live);
    }

    /**
     * Narrow to listings meeting a set of search criteria: the single definition
     * of a "match", shared by the index filters and saved searches. Absent or
     * blank criteria don't constrain the search. SavedSearch::matchingListing()
     * is its inverse, used for alerting; keep the two in step.
     *
     * @param  Builder<Listing>  $query
     * @param  array{property_type?: string|null, max_price?: int|string|null, min_bedrooms?: int|string|null, region?: string|null}  $criteria
     */
    public function scopeMatching(Builder $query, array $criteria): void
    {
        $criteria = array_filter($criteria, fn ($value) => $value !== null && $value !== '');

        if (isset($criteria['property_type'])) {
            $query->where('property_type', $criteria['property_type']);
        }

        if (isset($criteria['max_price'])) {
            $query->where('price', '<=', (int) $criteria['max_price']);
        }

        if (isset($criteria['min_bedrooms'])) {
            $query->where('bedrooms', '>=', (int) $criteria['min_bedrooms']);
        }

        if (isset($criteria['region'])) {
            $region = $criteria['region'];
            $query->whereHas('branch', function ($branchQuery) use ($region) {
                $branchQuery->where('region', $region);
            });
        }
    }
}
