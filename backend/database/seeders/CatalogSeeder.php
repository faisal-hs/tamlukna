<?php

namespace Database\Seeders;

use App\Enums\FeatureGroup;
use App\Enums\PropertyCategory;
use App\Models\Feature;
use App\Models\PropertyType;
use Illuminate\Database\Seeder;

/**
 * Platform-managed catalog: property types and features (Arabic/English).
 * Editable later through the Phase 2 dashboard. Idempotent.
 */
final class CatalogSeeder extends Seeder
{
    /** @var list<array{string, string, string, PropertyCategory, bool, bool}> slug, Arabic, English, category, rooms, building */
    private const TYPES = [
        ['apartment', 'شقة', 'Apartment', PropertyCategory::Residential, true, true],
        ['villa', 'فيلا', 'Villa', PropertyCategory::Residential, true, true],
        ['floor', 'دور', 'Floor', PropertyCategory::Residential, true, true],
        ['building', 'عمارة', 'Building', PropertyCategory::Residential, false, true],
        ['rest-house', 'استراحة', 'Rest house', PropertyCategory::Residential, true, false],
        ['residential-land', 'أرض سكنية', 'Residential land', PropertyCategory::Residential, false, false],
        ['commercial-land', 'أرض تجارية', 'Commercial land', PropertyCategory::Commercial, false, false],
        ['office', 'مكتب', 'Office', PropertyCategory::Commercial, false, true],
        ['shop', 'محل تجاري', 'Shop', PropertyCategory::Commercial, false, true],
        ['warehouse', 'مستودع', 'Warehouse', PropertyCategory::Commercial, false, true],
        ['farm', 'مزرعة', 'Farm', PropertyCategory::Agricultural, false, false],
    ];

    /** @var list<array{string, string, string, FeatureGroup}> slug, Arabic, English, group */
    private const FEATURES = [
        ['central-ac', 'تكييف مركزي', 'Central air conditioning', FeatureGroup::Interior],
        ['split-ac', 'مكيفات منفصلة', 'Split air conditioners', FeatureGroup::Interior],
        ['kitchen-installed', 'مطبخ راكب', 'Fitted kitchen', FeatureGroup::Interior],
        ['maid-room', 'غرفة خادمة', 'Maid room', FeatureGroup::Interior],
        ['driver-room', 'غرفة سائق', 'Driver room', FeatureGroup::Interior],
        ['laundry-room', 'غرفة غسيل', 'Laundry room', FeatureGroup::Interior],
        ['elevator', 'مصعد', 'Elevator', FeatureGroup::Building],
        ['parking', 'موقف سيارات', 'Parking', FeatureGroup::Building],
        ['security', 'حراسة أمنية', 'Security', FeatureGroup::Building],
        ['basement', 'قبو', 'Basement', FeatureGroup::Building],
        ['private-roof', 'سطح خاص', 'Private roof', FeatureGroup::Building],
        ['annex', 'ملحق', 'Annex', FeatureGroup::Building],
        ['private-entrance', 'مدخل خاص', 'Private entrance', FeatureGroup::Building],
        ['private-pool', 'مسبح خاص', 'Private pool', FeatureGroup::Exterior],
        ['garden', 'حديقة', 'Garden', FeatureGroup::Exterior],
        ['courtyard', 'حوش', 'Courtyard', FeatureGroup::Exterior],
        ['outdoor-seating', 'جلسة خارجية', 'Outdoor seating', FeatureGroup::Exterior],
        ['playground', 'ملعب أطفال', 'Children\'s play area', FeatureGroup::Exterior],
    ];

    public function run(): void
    {
        foreach (self::TYPES as $i => [$slug, $ar, $en, $category, $rooms, $building]) {
            PropertyType::query()->updateOrCreate(['slug' => $slug], [
                'name_ar' => $ar,
                'name_en' => $en,
                'category' => $category,
                'allows_rooms' => $rooms,
                'allows_building_details' => $building,
                'sort_order' => $i + 1,
                'is_active' => true,
            ]);
        }

        foreach (self::FEATURES as $i => [$slug, $ar, $en, $group]) {
            Feature::query()->updateOrCreate(['slug' => $slug], [
                'name_ar' => $ar,
                'name_en' => $en,
                'group' => $group,
                'sort_order' => $i + 1,
                'is_active' => true,
            ]);
        }
    }
}
