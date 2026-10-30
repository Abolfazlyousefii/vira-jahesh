<?php

namespace App\Support;

use Nwidart\Modules\Facades\Module;

final class ModuleAccess
{
    public function available(string $name, array $visited = []): bool
    {
        $definition = config('vira.modules.'.$name);
        if (! is_array($definition) || ! ($definition['enabled'] ?? false) || in_array($name, $visited, true)) {
            return false;
        }
        if (! Module::find($name)?->isEnabled()) {
            return false;
        }
        $visited[] = $name;
        foreach ($definition['requires'] as $dependency) {
            if (! $this->available($dependency, $visited)) {
                return false;
            }
        }

        return true;
    }

    public function definitions(): array
    {
        return collect(config('vira.modules'))->map(fn (array $module, string $name) => [
            ...$module, 'name' => $name, 'available' => $this->available($name),
        ])->all();
    }
}
