<?php

namespace App\Models;

use App\Enums\FeatureGroup;
use Database\Factories\FeatureFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $slug
 * @property string $name_ar
 * @property string $name_en
 * @property FeatureGroup $group
 * @property int $sort_order
 * @property bool $is_active
 */
class Feature extends Model
{
    /** @use HasFactory<FeatureFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = ['slug', 'name_ar', 'name_en', 'group', 'sort_order', 'is_active'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'group' => FeatureGroup::class,
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
