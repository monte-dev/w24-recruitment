<?php

namespace Tests\Feature\Web;

use Tests\TestCase;

class SpaRouteTest extends TestCase
{
    public function test_spa_root_route_returns_app_view(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200)
            ->assertSee('id="app"', false);
    }

    public function test_spa_imports_route_returns_app_view(): void
    {
        $response = $this->get('/imports');

        $response->assertStatus(200)
            ->assertSee('id="app"', false);
    }

    public function test_spa_import_details_route_returns_app_view(): void
    {
        $response = $this->get('/imports/1');

        $response->assertStatus(200)
            ->assertSee('id="app"', false);
    }

    public function test_spa_nested_route_returns_app_view(): void
    {
        $response = $this->get('/imports/1/any-nested-path');

        $response->assertStatus(200)
            ->assertSee('id="app"', false);
    }
}
