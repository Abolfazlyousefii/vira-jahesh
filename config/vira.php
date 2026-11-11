<?php

return [
    'brand' => 'ویرا',
    'tagline' => 'یکپارچه با جهش',
    'modules' => [
        'Core' => ['label' => 'هسته و کاربران', 'icon' => 'shield-check', 'enabled' => env('MODULE_CORE_ENABLED', true), 'requires' => []],
        'Catalog' => ['label' => 'کالاهای نرم‌افزار', 'icon' => 'package', 'enabled' => env('MODULE_CATALOG_ENABLED', true), 'requires' => ['Core']],
        'Inventory' => ['label' => 'انبار و موجودی', 'icon' => 'boxes', 'enabled' => env('MODULE_INVENTORY_ENABLED', true), 'requires' => ['Catalog']],
        'Customers' => ['label' => 'مشتریان', 'icon' => 'users', 'enabled' => env('MODULE_CUSTOMERS_ENABLED', true), 'requires' => ['Core']],
        'Sales' => ['label' => 'فروش و فاکتورها', 'icon' => 'receipt-text', 'enabled' => env('MODULE_SALES_ENABLED', true), 'requires' => ['Inventory', 'Customers']],
        'Crm' => ['label' => 'ارتباطات و پیگیری‌ها', 'icon' => 'contact-round', 'enabled' => env('MODULE_CRM_ENABLED', true), 'requires' => ['Customers']],
        'Pos' => ['label' => 'صندوق فروشگاهی', 'icon' => 'scan-line', 'enabled' => env('MODULE_POS_ENABLED', true), 'requires' => ['Sales']],
        'Storefront' => ['label' => 'سایت فروشگاهی', 'icon' => 'store', 'enabled' => env('MODULE_STOREFRONT_ENABLED', true), 'requires' => ['Sales']],
    ],
];
