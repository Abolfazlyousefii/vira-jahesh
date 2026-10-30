<?php

namespace App\Livewire;

use App\Models\User;
use App\Support\ModuleAccess;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Spatie\Activitylog\Models\Activity;

#[Layout('components.layouts.panel')]
class FoundationDashboard extends Component
{
    public function render()
    {
        $modules = app(ModuleAccess::class)->definitions();

        return view('livewire.foundation-dashboard', [
            'modules' => $modules,
            'activeModules' => collect($modules)->where('available', true)->count(),
            'users' => User::count(),
            'activities' => Activity::latest()->limit(5)->get(),
        ]);
    }
}
