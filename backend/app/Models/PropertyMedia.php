<?php

namespace App\Models;

use Database\Factories\PropertyMediaFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Media metadata. Storage location (disk/path) is internal and never exposed;
 * files are served through authorized URLs (Step 9). Original client file names
 * are not stored.
 *
 * @property int $id
 * @property string $public_id
 * @property int $property_id
 * @property string $disk
 * @property string $path
 * @property string $mime_type
 * @property int $size_bytes
 * @property int|null $width
 * @property int|null $height
 * @property string|null $checksum_sha256
 * @property int $sort_order
 * @property bool $is_cover
 */
class PropertyMedia extends Model
{
    /** @use HasFactory<PropertyMediaFactory> */
    use HasFactory, HasUlids;

    protected $table = 'property_media';

    /**
     * Internal-only model: rows are created by the media service, never from raw input.
     *
     * @var list<string>
     */
    protected $fillable = [
        'property_id',
        'disk',
        'path',
        'mime_type',
        'size_bytes',
        'width',
        'height',
        'checksum_sha256',
        'sort_order',
        'is_cover',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'disk',
        'path',
        'cover_property_id',
    ];

    /**
     * @return list<string>
     */
    public function uniqueIds(): array
    {
        return ['public_id'];
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'size_bytes' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
            'sort_order' => 'integer',
            'is_cover' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Property, $this>
     */
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }
}
