<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\Inventory\Models\Warehouse;
use Modules\Sales\Models\Sale;
use Tests\TestCase;

class ViraDashboardExperienceTest extends TestCase
{
    use RefreshDatabase;

    public function test_panel_uses_approved_v4_shell_without_legacy_theme_switchers(): void
    {
        $this->seed();
        $owner = User::factory()->create();
        $owner->assignRole('owner');

        $this->actingAs($owner)->get('/panel')
            ->assertOk()
            ->assertSee('v4-app')
            ->assertSee('مشاهده همه ماژول‌ها')
            ->assertSee('جست‌وجوی بخش‌ها')
            ->assertDontSee('تم بنفش')
            ->assertDontSee('حالت تیره');
    }

    public function test_disabled_modules_and_unpermitted_actions_are_not_in_panel_navigation(): void
    {
        $this->seed();
        $operator = User::factory()->create();
        $operator->assignRole('operator');
        config(['vira.modules.Crm.enabled' => false]);

        $this->actingAs($operator)->get('/panel')
            ->assertOk()
            ->assertDontSee('href="'.route('panel.crm').'"', false)
            ->assertDontSee('href="'.route('panel.sales').'"', false)
            ->assertDontSee('href="'.route('panel.staff').'"', false)
            ->assertDontSee('روند فروش');
    }

    public function test_sales_overview_uses_real_invoices_and_not_demonstration_values(): void
    {
        $this->seed();
        $owner = User::factory()->create();
        $owner->assignRole('owner');
        $warehouse = Warehouse::create(['name' => 'انبار اصلی']);
        $sale = Sale::create([
            'request_token' => (string) Str::uuid(), 'request_hash' => str_repeat('a', 64),
            'customer_name' => 'مشتری نمونهٔ تست', 'warehouse_id' => $warehouse->id,
            'user_id' => $owner->id, 'channel' => 'direct', 'payment_method' => 'cash',
            'total_toman' => 1729000,
        ]);

        $this->actingAs($owner)->get('/panel')
            ->assertOk()
            ->assertSee('مشتری نمونهٔ تست')
            ->assertSee('#'.$sale->id)
            ->assertSee('1,729,000')
            ->assertDontSee('84,600,000');
    }

    public function test_dashboard_respects_a_hidden_sales_widget(): void
    {
        $this->seed();
        $owner = User::factory()->create();
        $owner->assignRole('owner');
        \Modules\Core\Models\DashboardWidgetPreference::create([
            'user_id' => $owner->id, 'widget_key' => 'sales.trend', 'position' => 0,
            'size' => 'wide', 'is_visible' => false,
        ]);

        $this->actingAs($owner)->get('/panel')
            ->assertOk()
            ->assertDontSee('روند فروش');
    }
}
