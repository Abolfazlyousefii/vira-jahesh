<?php

namespace Modules\Core\Dashboard;

use App\Models\User;
use App\Support\ModuleAccess;
use Closure;

final class WidgetRegistry
{
    /** @var array<string, array<string, mixed>> */
    private array $definitions = [];

    public function __construct(private ModuleAccess $modules) {}

    public function register(string $key, string $title, string $module, string $permission, string $view, Closure $resolve): void
    {
        $this->definitions[$key] = compact('key', 'title', 'module', 'permission', 'view', 'resolve');
    }

    public function visibleTo(User $user): array
    {
        return array_values(array_filter($this->definitions, fn (array $widget) => $this->allowed($user, $widget)));
    }

    public function authorizeWidget(User $user, string $key): array
    {
        abort_unless(array_key_exists($key, $this->definitions), 404);

        $widget = $this->definitions[$key];
        abort_unless($this->modules->available($widget['module']), 404);
        abort_unless($user->can('panel.access') && $user->can($widget['permission']), 403);

        return $widget;
    }

    private function allowed(User $user, array $widget): bool
    {
        return $user->can('panel.access')
            && $user->can($widget['permission'])
            && $this->modules->available($widget['module']);
    }
}
