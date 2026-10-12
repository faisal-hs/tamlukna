<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\District;
use App\Models\Region;
use Illuminate\Database\Seeder;

/**
 * Regions: the 13 administrative regions with ISO 3166-2:SA codes (factual).
 * Cities and districts: a small SAMPLE (data_source = 'sample') until a licensed
 * dataset is chosen. City coordinates are approximate centres for map defaults only;
 * districts have no coordinates. Idempotent.
 */
final class LocationSeeder extends Seeder
{
    private const SOURCE = 'sample';

    /** @var list<array{string, string, string}> code, Arabic, English */
    private const REGIONS = [
        ['SA-01', 'منطقة الرياض', 'Riyadh Region'],
        ['SA-02', 'منطقة مكة المكرمة', 'Makkah Region'],
        ['SA-03', 'منطقة المدينة المنورة', 'Madinah Region'],
        ['SA-04', 'المنطقة الشرقية', 'Eastern Province'],
        ['SA-05', 'منطقة القصيم', 'Al-Qassim Region'],
        ['SA-06', 'منطقة حائل', 'Hail Region'],
        ['SA-07', 'منطقة تبوك', 'Tabuk Region'],
        ['SA-08', 'منطقة الحدود الشمالية', 'Northern Borders Region'],
        ['SA-09', 'منطقة جازان', 'Jazan Region'],
        ['SA-10', 'منطقة نجران', 'Najran Region'],
        ['SA-11', 'منطقة الباحة', 'Al-Bahah Region'],
        ['SA-12', 'منطقة الجوف', 'Al-Jawf Region'],
        ['SA-14', 'منطقة عسير', 'Asir Region'],
    ];

    /** @var list<array{string, string, string, string, float, float}> region, slug, Arabic, English, lat, lng */
    private const CITIES = [
        ['SA-01', 'riyadh', 'الرياض', 'Riyadh', 24.7136, 46.6753],
        ['SA-02', 'jeddah', 'جدة', 'Jeddah', 21.4858, 39.1925],
        ['SA-02', 'makkah', 'مكة المكرمة', 'Makkah', 21.3891, 39.8579],
        ['SA-02', 'taif', 'الطائف', 'Taif', 21.2703, 40.4158],
        ['SA-03', 'madinah', 'المدينة المنورة', 'Madinah', 24.5247, 39.5692],
        ['SA-04', 'dammam', 'الدمام', 'Dammam', 26.4207, 50.0888],
        ['SA-04', 'khobar', 'الخبر', 'Khobar', 26.2172, 50.1971],
        ['SA-05', 'buraidah', 'بريدة', 'Buraidah', 26.3260, 43.9750],
        ['SA-06', 'hail', 'حائل', 'Hail', 27.5114, 41.7208],
        ['SA-07', 'tabuk', 'تبوك', 'Tabuk', 28.3835, 36.5662],
        ['SA-14', 'abha', 'أبها', 'Abha', 18.2164, 42.5053],
    ];

    /** @var list<array{string, string, string, string}> city, slug, Arabic, English */
    private const DISTRICTS = [
        ['riyadh', 'al-olaya', 'العليا', 'Al Olaya'],
        ['riyadh', 'al-malqa', 'الملقا', 'Al Malqa'],
        ['riyadh', 'hittin', 'حطين', 'Hittin'],
        ['riyadh', 'al-yasmin', 'الياسمين', 'Al Yasmin'],
        ['riyadh', 'al-narjis', 'النرجس', 'Al Narjis'],
        ['riyadh', 'al-sahafa', 'الصحافة', 'Al Sahafa'],
        ['jeddah', 'al-rawdah', 'الروضة', 'Al Rawdah'],
        ['jeddah', 'al-shati', 'الشاطئ', 'Al Shati'],
        ['jeddah', 'al-zahra', 'الزهراء', 'Al Zahra'],
        ['jeddah', 'al-salamah', 'السلامة', 'Al Salamah'],
        ['jeddah', 'obhur-al-shamaliyah', 'أبحر الشمالية', 'Obhur Al Shamaliyah'],
    ];

    public function run(): void
    {
        $regions = [];
        foreach (self::REGIONS as [$code, $ar, $en]) {
            $regions[$code] = Region::query()->updateOrCreate(
                ['code' => $code],
                ['name_ar' => $ar, 'name_en' => $en, 'data_source' => 'iso-3166-2:sa'],
            );
        }

        $cities = [];
        foreach (self::CITIES as [$region, $slug, $ar, $en, $lat, $lng]) {
            $cities[$slug] = City::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'region_id' => $regions[$region]->id,
                    'name_ar' => $ar,
                    'name_en' => $en,
                    'latitude' => $lat,
                    'longitude' => $lng,
                    'is_active' => true,
                    'data_source' => self::SOURCE,
                ],
            );
        }

        foreach (self::DISTRICTS as [$city, $slug, $ar, $en]) {
            District::query()->updateOrCreate(
                ['city_id' => $cities[$city]->id, 'slug' => $slug],
                ['name_ar' => $ar, 'name_en' => $en, 'is_active' => true, 'data_source' => self::SOURCE],
            );
        }
    }
}
