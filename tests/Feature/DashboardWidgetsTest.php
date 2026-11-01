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

        $keys = collect(app(WidgetRegistry::class)->visibleTo($owner))->pluck('key');
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
}
