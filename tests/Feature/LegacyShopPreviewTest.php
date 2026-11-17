<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LegacyShopPreviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_preview_is_disabled_by_default_without_changing_the_existing_site(): void
    {
        $this->get('/shop-preview')->assertNotFound();
        $this->get('/shop-preview/products')->assertNotFound();
        $this->get('/')->assertOk();
        $this->get('/shop')->assertOk();
    }

    public function test_preview_requires_the_locally_installed_theme_when_enabled(): void
    {
        config(['vira.shop_preview.enabled' => true]);

        $this->get('/shop-preview')->assertStatus(503);
    }
}
