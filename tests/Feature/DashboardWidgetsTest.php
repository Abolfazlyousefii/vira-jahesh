<?php

namespace Tests\Feature;

use App\Livewire\FoundationDashboard;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Core\Dashboard\WidgetRegistry;
use Tests\TestCase;

class DashboardWidgetsTest extends TestCase
{
    use RefreshDatabase;

    public function test_widget_preferences_belong_to_the_authenticated_user(): void
    {
        $this->seed();
        $owner = User::factory()->create();
        $owner->assignRole('owner');
        $other = User::factory()->create();
        $other->assignRole('owner');

        Livewire::actingAs($owner)->test(FoundationDashboard::class)
            ->call('toggleWidget', 'activities')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('dashboard_widget_preferences', [
            'user_id' => $owner->id,
            'widget_key' => 'activities',
            'is_visible' => false,
        ]);
        $this->assertDatabaseMissing('dashboard_widget_preferences', [
            'user_id' => $other->id,
            'widget_key' => 'activities',
        ]);
    }

    public function test_a_user_cannot_mutate_a_widget_without_its_permission(): void
    {
        $this->seed();
        $operator = User::factory()->create();
        $operator->assignRole('operator');

        Livewire::actingAs($operator)->test(FoundationDashboard::class)
            ->call('toggleWidget', 'activities')
            ->assertForbidden();
        $this->assertDatabaseCount('dashboard_widget_preferences', 0);
    }

    public function test_unknown_widgets_are_rejected(): void
    {
        $this->seed();
        $owner = User::factory()->create();
        $owner->assignRole('owner');

        Livewire::actingAs($owner)->test(FoundationDashboard::class)
            ->call('toggleWidget', 'unknown-widget')
            ->assertNotFound();
    }

    public function test_inaccessible_module_widgets_are_not_returned_by_the_registry(): void
    {
        $this->seed();
        $owner = User::factory()->create();
        $owner->assignRole('owner');
        config(['vira.modules.Crm.enabled' => false]);

        $registry = app(WidgetRegistry::class);
        $registry->register('crm.followups', 'پیگیری‌های CRM', 'Crm', 'crm.view', 'core::dashboard.widgets.modules', function () {
            throw new \RuntimeException('Disabled module resolver must not execute.');
        });

        $keys = collect($registry->visibleTo($owner))->pluck('key');
        $this->assertNotContains('crm.followups', $keys);
        $this->assertContains('modules', $keys);
    }

    public function test_a_user_can_reorder_and_resize_only_supported_widgets(): void
    {
        $this->seed();
        $owner = User::factory()->create();
        $owner->assignRole('owner');

        Livewire::actingAs($owner)->test(FoundationDashboard::class)
            ->call('moveWidget', 'activities', 'up')
            ->call('resizeWidget', 'modules', 'wide')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('dashboard_widget_preferences', [
            'user_id' => $owner->id,
            'widget_key' => 'modules',
            'size' => 'wide',
        ]);
    }

    public function test_a_widget_resolver_is_never_executed_for_a_user_without_permission(): void
    {
        $this->seed();
        $operator = User::factory()->create();
        $operator->assignRole('operator');

        $registry = app(WidgetRegistry::class);
        $registry->register('restricted', 'Restricted', 'Core', 'modules.manage', 'core::dashboard.widgets.modules', function () {
            throw new \RuntimeException('Private resolver was invoked.');
        });

        Livewire::actingAs($operator)->test(FoundationDashboard::class)->assertOk();
        $this->assertNotContains('restricted', array_column($registry->visibleTo($operator), 'key'));
    }

    public function test_widget_order_is_persisted_across_a_new_component_instance(): void
    {
        $this->seed();
        $owner = User::factory()->create();
        $owner->assignRole('owner');

        Livewire::actingAs($owner)->test(FoundationDashboard::class)
            ->call('moveWidget', 'activities', 'up')
            ->assertHasNoErrors();

        Livewire::actingAs($owner)->test(FoundationDashboard::class)
            ->assertViewHas('widgets', fn ($widgets) => array_search('activities', array_column($widgets, 'key'), true) < array_search('staff', array_column($widgets, 'key'), true));
    }
}
