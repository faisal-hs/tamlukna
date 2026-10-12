<?php

namespace Tests\Feature\Api;

use Tests\TestCase;

final class DefaultLocaleTest extends TestCase
{
    public function test_arabic_is_used_when_no_accept_language_header_is_sent(): void
    {
        // Laravel's test client (via Symfony Request::create) sends
        // "Accept-Language: en-us,en;q=0.5" by default, so clear it to
        // simulate a client that sends no preference at all.
        $this->withHeaders(['Accept-Language' => ''])
            ->getJson('/api/v1/definitely-not-a-route')
            ->assertNotFound()
            ->assertJsonPath('message', 'المورد المطلوب غير موجود.')
            ->assertHeader('Content-Language', 'ar');
    }

    public function test_default_stays_arabic_after_a_request_in_another_language(): void
    {
        $this->withHeaders(['Accept-Language' => 'en'])
            ->getJson('/api/v1/definitely-not-a-route')
            ->assertHeader('Content-Language', 'en');

        $this->withHeaders(['Accept-Language' => ''])
            ->getJson('/api/v1/definitely-not-a-route')
            ->assertHeader('Content-Language', 'ar')
            ->assertJsonPath('message', 'المورد المطلوب غير موجود.');
    }
}
