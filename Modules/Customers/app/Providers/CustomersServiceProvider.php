<?php

namespace Modules\Customers\Providers;

use App\Support\ModuleServiceProvider;
use Modules\Core\Dashboard\WidgetRegistry;
use Modules\Customers\Models\Customer;

class CustomersServiceProvider extends ModuleServiceProvider
{
    protected string $module = 'Customers';

    public function boot(): void
    {
        parent::boot();
        $this->callAfterResolving(WidgetRegistry::class, function ($registry): void {
            $registry->register('customers.count', 'مشتریان ثبت‌شده', 'Customers', 'customers.view', 'core::dashboard.widgets.metric', fn () => [
                'value' => Customer::count(), 'unit' => 'نفر', 'description' => 'پرونده‌های مشتریان فروشگاه', 'route' => 'panel.customers', 'label' => 'مشاهدهٔ مشتریان',
            ]);
        });
    }
}
