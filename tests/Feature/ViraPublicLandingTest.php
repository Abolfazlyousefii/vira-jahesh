<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ViraPublicLandingTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_homepage_introduces_vira_as_business_management_software(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('تمام کسب‌وکارتان')
            ->assertSee('در یک پنل مدیریتی یکپارچه')
            ->assertSee('مدیریت کالاها')
            ->assertSee('انبار و موجودی')
            ->assertSee('مشتریان و CRM')
            ->assertSee('مراحل راه‌اندازی');
    }

    public function test_public_homepage_has_a_prominent_real_panel_login_link(): void
    {
        $loginUrl = route('login');

        $this->get('/')
            ->assertOk()
            ->assertSee('ورود به پنل مدیریت')
            ->assertSee('href="'.$loginUrl.'"', false);
    }

    public function test_public_homepage_uses_real_contact_details_only_when_configured(): void
    {
        config(['vira.sales_phone' => '02112345678']);

        $this->get('/')
            ->assertOk()
            ->assertSee('02112345678')
            ->assertSee('tel:02112345678', false);
    }

    public function test_public_homepage_does_not_publish_a_placeholder_contact_address(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertDontSee('example.com')
            ->assertDontSee('info@example');
    }
}
