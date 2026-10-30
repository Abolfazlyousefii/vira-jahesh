<?php

namespace App\Support;

use Illuminate\Support\ServiceProvider;

abstract class ModuleServiceProvider extends ServiceProvider
{
    protected string $module;

    public function boot(): void
    {
        $path = base_path('Modules/'.$this->module);
        if (is_dir($path.'/resources/views')) {
            $this->loadViewsFrom($path.'/resources/views', strtolower($this->module));
        }
        if (is_dir($path.'/database/migrations')) {
            $this->loadMigrationsFrom($path.'/database/migrations');
        }
        if (is_file($path.'/routes/web.php')) {
            $this->loadRoutesFrom($path.'/routes/web.php');
        }
    }
}
