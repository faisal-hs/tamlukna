<?php

namespace Tests\Feature\Api;

use App\Support\Api\Pagination;
use Illuminate\Http\Request;
use Tests\TestCase;

final class PaginationTest extends TestCase
{
    public function test_per_page_uses_default_and_is_clamped_to_configured_bounds(): void
    {
        $this->assertSame(15, Pagination::perPage(Request::create('/x')));
        $this->assertSame(50, Pagination::perPage(Request::create('/x', 'GET', ['per_page' => 500])));
        $this->assertSame(1, Pagination::perPage(Request::create('/x', 'GET', ['per_page' => 0])));
        $this->assertSame(20, Pagination::perPage(Request::create('/x', 'GET', ['per_page' => 20])));
    }
}
