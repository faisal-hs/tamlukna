<?php

namespace Tests\Feature\Api;

use App\Support\Api\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

final class ValidationTranslationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware('api')->post('api/v1/__test/validate-name', fn (Request $request) => ApiResponse::success(
            $request->validate(['name' => ['required', 'string']])
        ));
    }

    public function test_field_errors_are_arabic_by_default(): void
    {
        $this->withHeaders(['Accept-Language' => ''])
            ->postJson('/api/v1/__test/validate-name', [])
            ->assertStatus(422)
            ->assertJsonPath('errors.name.0', 'حقل الاسم مطلوب.');
    }

    public function test_field_errors_are_english_when_requested(): void
    {
        $this->withHeaders(['Accept-Language' => 'en'])
            ->postJson('/api/v1/__test/validate-name', [])
            ->assertStatus(422)
            ->assertJsonPath('errors.name.0', 'The name field is required.');
    }

    public function test_arabic_file_covers_every_framework_validation_rule(): void
    {
        $missing = array_values(array_diff(array_keys($this->englishMessages()), array_keys($this->arabicMessages())));

        $this->assertSame([], $missing, 'Missing Arabic validation keys: '.implode(', ', $missing));
    }

    public function test_arabic_messages_keep_the_same_placeholders_as_english(): void
    {
        $arabic = $this->arabicMessages();
        $mismatches = [];

        foreach ($this->englishMessages() as $key => $english) {
            if (! isset($arabic[$key])) {
                continue; // reported by the coverage test
            }

            if ($this->placeholders($english) !== $this->placeholders($arabic[$key])) {
                $mismatches[] = $key;
            }
        }

        $this->assertSame([], $mismatches, 'Placeholder mismatch in: '.implode(', ', $mismatches));
    }

    /**
     * @return array<string, string>
     */
    private function englishMessages(): array
    {
        return $this->flatten(require base_path('vendor/laravel/framework/src/Illuminate/Translation/lang/en/validation.php'));
    }

    /**
     * @return array<string, string>
     */
    private function arabicMessages(): array
    {
        return $this->flatten(require lang_path('ar/validation.php'));
    }

    /**
     * @param  array<string, mixed>  $messages
     * @return array<string, string>
     */
    private function flatten(array $messages): array
    {
        unset($messages['custom'], $messages['attributes']);

        return array_filter(Arr::dot($messages), 'is_string');
    }

    /**
     * @return array<int, string>
     */
    private function placeholders(string $message): array
    {
        preg_match_all('/:[a-z_]+/', $message, $matches);
        $found = array_values(array_unique($matches[0]));
        sort($found);

        return $found;
    }
}
