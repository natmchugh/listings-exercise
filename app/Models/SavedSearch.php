<?php

namespace App\Models;

use App\Enums\PropertyType;
use Database\Factories\SavedSearchFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A user's stored listing criteria. A null criterion means "any".
 *
 * @property int $id
 * @property int $user_id
 * @property int|null $max_price
 * @property int|null $min_bedrooms
 * @property PropertyType|null $property_type
 * @property string|null $region
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read User $user
 */
class SavedSearch extends Model
{
    /** @use HasFactory<SavedSearchFactory> */
    use HasFactory;

    /**
     * The criteria a search is made of, named as the listings index filters are.
     */
    public const CRITERIA = ['property_type', 'max_price', 'min_bedrooms', 'region'];

    /**
     * Each search can become a stream of alerts, so cap how many a user keeps.
     */
    public const MAX_PER_USER = 20;

    protected $fillable = [
        'max_price',
        'min_bedrooms',
        'property_type',
        'region',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'max_price' => 'integer',
            'min_bedrooms' => 'integer',
            'property_type' => PropertyType::class,
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The criteria that are set, as scalars: ready for Listing::matching() or a
     * listings index query string.
     *
     * @return array{property_type?: string, max_price?: int, min_bedrooms?: int, region?: string}
     */
    public function criteria(): array
    {
        return array_filter([
            'property_type' => $this->property_type?->value,
            'max_price' => $this->max_price,
            'min_bedrooms' => $this->min_bedrooms,
            'region' => $this->region,
        ], fn ($value) => $value !== null);
    }
}
