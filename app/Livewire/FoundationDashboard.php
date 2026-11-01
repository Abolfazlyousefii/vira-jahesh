<?php

namespace App\Livewire;

use App\Support\ModuleAccess;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Modules\Core\Dashboard\WidgetRegistry;
use Modules\Core\Models\DashboardWidgetPreference;

#[Layout('components.layouts.panel')]
class FoundationDashboard extends Component
{
    public bool $editing = false;

    public function toggleWidget(string $key): void
    {
        $this->authorizeWidget($key);

        $pref = $this->preference($key);
        $pref->is_visible = ! $pref->is_visible;
        $pref->save();
    }

    public function resizeWidget(string $key, string $size): void
    {
        $this->authorizeWidget($key);
        abort_unless(in_array($size, ['compact', 'wide'], true), 422);

        $pref = $this->preference($key);
        $pref->size = $size;
        $pref->save();
    }

    public function moveWidget(string $key, string $direction): void
    {
        $this->authorizeWidget($key);
        abort_unless(in_array($direction, ['up', 'down'], true), 422);

        $widgets = $this->orderedWidgets();
        $keys = array_column($widgets, 'key');
        $index = array_search($key, $keys, true);
        $target = $index + ($direction === 'up' ? -1 : 1);

        if ($target < 0 || $target >= count($keys)) {
            return;
        }

        [$keys[$index], $keys[$target]] = [$keys[$target], $keys[$index]];

        DB::transaction(function () use ($keys): void {
            foreach ($keys as $position => $widgetKey) {
                $pref = $this->preference($widgetKey);
                $pref->position = $position;
                $pref->save();
            }
        });
    }

    private function authorizeWidget(string $key): void
    {
        abort_unless(auth()->check() && app(ModuleAccess::class)->available('Core'), 403);
        app(WidgetRegistry::class)->authorizeWidget(auth()->user(), $key);
    }

    private function preference(string $key): DashboardWidgetPreference
    {
        $user = auth()->user();
        abort_unless($user !== null, 403);

        return DashboardWidgetPreference::firstOrCreate(
            ['user_id' => $user->id, 'widget_key' => $key],
            ['position' => $this->defaultPosition($key), 'size' => 'compact', 'is_visible' => true]
        );
    }

    private function defaultPosition(string $key): int
    {
        $keys = array_column(app(WidgetRegistry::class)->visibleTo(auth()->user()), 'key');

        return (int) array_search($key, $keys, true);
    }

    private function orderedWidgets(): array
    {
        $user = auth()->user();
        $preferences = DashboardWidgetPreference::query()->where('user_id', $user->id)->get()->keyBy('widget_key');

        return collect(app(WidgetRegistry::class)->visibleTo($user))
            ->map(function (array $widget, int $index) use ($preferences): array {
                $pref = $preferences->get($widget['key']);

                return [
                    ...$widget,
                    'position' => $pref?->position ?? $index,
                    'size' => $pref?->size ?? 'compact',
                    'is_visible' => $pref?->is_visible ?? true,
                    'original_index' => $index,
                ];
            })
            ->sortBy([['position', 'asc'], ['original_index', 'asc']])
            ->values()->all();
    }

    public function render()
    {
        $widgets = array_map(function (array $widget): array {
            $visible = $widget['is_visible'];
            $data = $visible ? ($widget['resolve'])() : [];

            unset($widget['resolve']);

            return [...$widget, 'data' => $data];
        }, $this->orderedWidgets());

        return view('livewire.foundation-dashboard', [
            'widgets' => $widgets,
        ]);
    }
}
