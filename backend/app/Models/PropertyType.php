<?php

namespace App\Models;

use App\Enums\PropertyCategory;
use Database\Factories\PropertyTypeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $slug
 * @property string $name_ar
 * @property string $name_en
 * @property PropertyCategory $category
 * @property bool $allows_rooms
 * @property bool $allows_building_details
 * @property int $sort_order
 * @property bool $is_active
 */
class PropertyType extends Model
{
    /** @use HasFactory<PropertyTypeFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = ['slug', 'name_ar', 'name_en', 'category', 'allows_rooms', 'allows_building_details', 'sort_order', 'is_active'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category' => PropertyCategory::class,
            'allows_rooms' => 'boolean',
            'allows_building_details' => 'boolean',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
