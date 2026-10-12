<?php

namespace Tests\Feature\Database;

use App\Enums\Role;
use App\Models\City;
use App\Models\District;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ReferenceIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_city_with_districts_cannot_be_deleted(): void
    {
        $district = District::factory()->create();

        $this->expectException(QueryException::class);
        City::query()->findOrFail($district->city_id)->delete();
    }

    public function test_district_slugs_are_unique_per_city_only(): void
    {
        $first = City::factory()->create();
        $second = City::factory()->create();

        District::factory()->create(['city_id' => $first->id, 'slug' => 'al-rawdah']);
        District::factory()->create(['city_id' => $second->id, 'slug' => 'al-rawdah']);

        $this->expectException(QueryException::class);
        District::factory()->create(['city_id' => $first->id, 'slug' => 'al-rawdah']);
    }

    public function test_polymorphic_columns_store_the_stable_alias(): void
    {
        $user = User::factory()->withRole(Role::Owner)->create();
        $user->createToken('t');

        $this->assertDatabaseHas('model_has_roles', ['model_type' => 'user', 'model_id' => $user->id]);
        $this->assertDatabaseHas('personal_access_tokens', ['tokenable_type' => 'user', 'tokenable_id' => $user->id]);
    }
}
