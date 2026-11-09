<?php

namespace Modules\Pos\Providers;

use App\Support\ModuleServiceProvider;
use Modules\Core\Dashboard\WidgetRegistry;
use Modules\Sales\Actions\SalesSummary;

class PosServiceProvider extends ModuleServiceProvider
{
    protected string $module = 'Pos';

    public function boot(): void
    {
        parent::boot();
        $this->callAfterResolving(WidgetRegistry::class, function ($registry): void {
            $registry->register('pos.count', 'فروش‌های صندوق من', 'Pos', 'pos.view', 'core::dashboard.widgets.metric', fn () => [
                'value' => app(SalesSummary::class)->cashierCount(auth()->id()), 'unit' => 'فاکتور', 'description' => 'فروش‌های ثبت‌شدهٔ شما از صندوق', 'route' => 'panel.pos', 'label' => 'رفتن به صندوق',
            ]);
        });
    }
}
