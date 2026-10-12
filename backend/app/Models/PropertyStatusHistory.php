<?php

namespace App\Models;

use App\Enums\ListingStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use LogicException;

/**
 * Append-only record of listing status changes (moderation history).
 *
 * @property int $id
 * @property int $property_id
 * @property ListingStatus|null $from_status
 * @property ListingStatus $to_status
 * @property int|null $actor_id
 * @property string|null $reason
 * @property Carbon $created_at
 */
class PropertyStatusHistory extends Model
{
    public const UPDATED_AT = null;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'property_id',
        'from_status',
        'to_status',
        'actor_id',
        'reason',
    ];

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Status history is immutable.'));
        static::deleting(fn () => throw new LogicException('Status history is immutable.'));
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'from_status' => ListingStatus::class,
            'to_status' => ListingStatus::class,
            'created_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Property, $this>
     */
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
