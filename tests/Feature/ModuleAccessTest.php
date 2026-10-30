<?php

namespace Tests\Feature;

use App\Support\ModuleAccess;
use Tests\TestCase;

class ModuleAccessTest extends TestCase
{
    public function test_unknown_modules_are_unavailable(): void
    {
        $this->assertFalse(app(ModuleAccess::class)->available('Unknown'));
    }

    public function test_dependency_cycles_are_rejected_without_recursion_failure(): void
    {
        config(['vira.modules.Core.requires' => ['Customers']]);
        $this->assertFalse(app(ModuleAccess::class)->available('Customers'));
    }
}
