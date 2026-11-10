@props(['title' => 'میز کار ویرا'])
@inject('moduleAccess', 'App\Support\ModuleAccess')
@php
    $user = auth()->user();
    // The same access-filtered navigation powers both the sidebar and command search.
    $primary = [
        ['module' => 'Customers', 'permission' => 'customers.view', 'route' => 'panel.customers', 'title' => 'مشتریان', 'icon' => 'users'],
        ['module' => 'Crm', 'permission' => 'crm.view', 'route' => 'panel.crm', 'title' => 'ارتباطات و پیگیری', 'icon' => 'contact-round'],
        ['module' => 'Catalog', 'permission' => 'catalog.view', 'route' => 'panel.catalog', 'title' => 'کالا و کاتالوگ', 'icon' => 'package'],
        ['module' => 'Inventory', 'permission' => 'inventory.view', 'route' => 'panel.inventory', 'title' => 'انبار و موجودی', 'icon' => 'boxes'],
        ['module' => 'Sales', 'permission' => 'sales.view', 'route' => 'panel.sales', 'title' => 'فروش و فاکتورها', 'icon' => 'receipt-text'],
    ];
    $secondary = [
        ['module' => 'Pos', 'permission' => 'pos.view', 'route' => 'panel.pos', 'title' => 'صندوق فروشگاهی', 'icon' => 'scan-line'],
        ['module' => 'Sales', 'permission' => 'sales.view', 'route' => 'panel.reports', 'title' => 'گزارش‌های فروش', 'icon' => 'chart-no-axes-combined'],
        ['module' => 'Storefront', 'permission' => 'storefront.manage', 'route' => 'panel.storefront', 'title' => 'مدیریت صفحه اصلی سایت', 'icon' => 'store'],
        ['module' => 'Core', 'permission' => 'settings.manage', 'route' => 'panel.settings', 'title' => 'تنظیمات مجموعه', 'icon' => 'settings'],
        ['module' => 'Core', 'permission' => 'modules.manage', 'route' => 'panel.modules', 'title' => 'ماژول‌های مجموعه', 'icon' => 'blocks'],
    ];
    if ($user->hasRole('owner') && $user->can('users.manage')) {
        $secondary[] = ['module' => 'Core', 'permission' => 'users.manage', 'route' => 'panel.staff', 'title' => 'کارکنان و دسترسی‌ها', 'icon' => 'user-round-cog'];
    }
    if ($moduleAccess->available('Storefront')) {
        $secondary[] = ['module' => 'Storefront', 'permission' => null, 'route' => 'storefront.home', 'title' => 'مشاهده سایت', 'icon' => 'external-link'];
    }
    $enabled = fn (array $item): bool => $moduleAccess->available($item['module']) && ($item['permission'] === null || $user->can($item['permission']));
    $primary = array_values(array_filter($primary, $enabled));
    $secondary = array_values(array_filter($secondary, $enabled));
    $searchItems = array_merge([['title' => 'میز کار', 'route' => 'panel.dashboard', 'icon' => 'layout-dashboard']], $primary, $secondary);
    $quickActions = array_values(array_filter([
        ['module' => 'Sales', 'permission' => 'sales.manage', 'route' => 'panel.sales', 'title' => 'شروع فروش', 'icon' => 'receipt-text'],
        ['module' => 'Customers', 'permission' => 'customers.manage', 'route' => 'panel.customers', 'title' => 'ثبت مشتری', 'icon' => 'user-plus'],
        ['module' => 'Catalog', 'permission' => 'catalog.manage', 'route' => 'panel.catalog', 'title' => 'ثبت کالا', 'icon' => 'package-plus'],
        ['module' => 'Crm', 'permission' => 'crm.manage', 'route' => 'panel.crm', 'title' => 'ثبت پیگیری', 'icon' => 'calendar-plus'],
    ], $enabled));
    $isSecondaryActive = collect($secondary)->contains(fn ($item) => request()->routeIs($item['route']));
    $businessName = \Modules\Core\Models\BusinessSetting::find(1)?->name ?: 'مجموعه من';
    // One semantic accent per workspace area; the application shell remains navy/teal.
    $area = match (true) {
        request()->routeIs('panel.customers*') => 'customers',
        request()->routeIs('panel.crm*') => 'crm',
        request()->routeIs('panel.catalog*') => 'catalog',
        request()->routeIs('panel.inventory*') => 'inventory',
        request()->routeIs('panel.sales*', 'panel.pos*', 'panel.reports*') => 'sales',
        request()->routeIs('panel.storefront*') => 'storefront',
        default => 'core',
    };
@endphp
<x-layouts.base :title="$title">
    <div class="v4-app app-shell" :class="{'sidebar-open': menu}" x-init="more = @js($isSecondaryActive)" @keydown.escape.window="menu = false; quickOpen = false; searchOpen = false" @resize.window="if (innerWidth > 920) menu = false">
        <button x-show="menu" x-cloak type="button" class="sidebar-overlay" @click="menu=false; $refs.mobileToggle.focus()" aria-label="بستن منوی اصلی"></button>
        <main class="workspace" data-vira-section="{{ $area }}">
            <header class="topbar">
                <button type="button" class="mobile-menu" x-ref="mobileToggle" @click="menu=true; $nextTick(() => $refs.mobileClose.focus())" aria-label="باز کردن منوی اصلی" :aria-expanded="menu"><i data-lucide="menu"></i></button>
                <div class="top-context">
                    <div class="context-mark"><i data-lucide="layout-grid"></i></div>
                    <div class="context-copy"><strong>{{ $businessName }}</strong><small>فضای کاری مجموعه</small></div>
                </div>
                <div class="global-search" @click.outside="searchOpen=false" @keydown.window.ctrl.k.prevent="$refs.searchInput.focus(); searchOpen=true" @keydown.window.meta.k.prevent="$refs.searchInput.focus(); searchOpen=true">
                    <label class="search-box" for="vira-nav-search">
                        <i data-lucide="search"></i>
                        <input id="vira-nav-search" x-ref="searchInput" x-model="searchTerm" @focus="searchOpen=true" @input="searchOpen=true" type="search" placeholder="جست‌وجوی بخش‌ها..." autocomplete="off" aria-label="جست‌وجوی بخش‌ها">
                        <kbd class="shortcut">Ctrl K</kbd>
                    </label>
                    <div class="search-results v4-search-results" x-show="searchOpen" x-cloak>
                        <div class="popover-title">رفتن به بخش</div>
                        @foreach($searchItems as $item)
                            <a class="search-result" x-show="@js($item['title']).includes(searchTerm.trim())" href="{{ route($item['route']) }}"><i data-lucide="{{ $item['icon'] }}"></i>{{ $item['title'] }}</a>
                        @endforeach
                        <p class="v4-search-empty" x-show="searchTerm.trim() !== '' && !@js(array_column($searchItems, 'title')).some(label => label.includes(searchTerm.trim()))">بخشی با این عنوان پیدا نشد.</p>
                    </div>
                </div>
                <div class="top-actions">
                    @if(count($quickActions))
                        <div class="v4-quick" @click.outside="quickOpen=false">
                            <button class="create-button" type="button" @click="quickOpen = !quickOpen" :aria-expanded="quickOpen"><i data-lucide="plus"></i><span>ایجاد جدید</span></button>
                            <div class="v4-quick-menu" x-show="quickOpen" x-cloak>
                                @foreach($quickActions as $action)
                                    <a href="{{ route($action['route']) }}"><i data-lucide="{{ $action['icon'] }}"></i>{{ $action['title'] }}</a>
                                @endforeach
                            </div>
                        </div>
                    @endif
                    <a class="icon-button" href="{{ route('panel.dashboard') }}" title="میز کار" aria-label="میز کار"><i data-lucide="layout-dashboard"></i></a>
                    <span class="user-chip" title="{{ $user->name }}">{{ mb_substr($user->name, 0, 1) }}</span>
                </div>
            </header>
            <div class="dashboard-main v4-panel-main" :inert="menu">
                @unless(request()->routeIs('panel.dashboard'))
                    <div class="v4-panel-breadcrumb">ویرا / {{ $title }}</div>
                @endunless
                <div class="v4-panel-body">{{ $slot }}</div>
            </div>
        </main>
        <aside class="sidebar" aria-label="منوی اصلی" :role="menu ? 'dialog' : 'complementary'" :aria-modal="menu ? 'true' : null">
            <div class="brand-row">
                <a class="brand-mark" href="{{ route('panel.dashboard') }}" aria-label="خانه ویرا">و</a>
                <a href="{{ route('panel.dashboard') }}" class="brand-copy"><div class="brand-name">ویرا</div><div class="brand-sub">BUSINESS WORKSPACE</div></a>
                <button type="button" class="mobile-menu" x-ref="mobileClose" @click="menu=false; $refs.mobileToggle.focus()" aria-label="بستن منو"><i data-lucide="x"></i></button>
            </div>
            <div class="workspace-switch" aria-label="مجموعه جاری">
                <div class="workspace-avatar"><i data-lucide="store"></i></div>
                <div><strong>{{ $businessName }}</strong><small>مدیریت مجموعه</small></div>
            </div>
            <nav class="nav-scroll" aria-label="بخش‌های ویرا">
                <div class="nav-label">فضای کاری</div>
                <div class="nav-list">
                    <a class="nav-link {{ request()->routeIs('panel.dashboard') ? 'active' : '' }}" href="{{ route('panel.dashboard') }}" @if(request()->routeIs('panel.dashboard')) aria-current="page" @endif><i data-lucide="layout-dashboard"></i><span>میز کار</span></a>
                    @foreach($primary as $item)
                        <a href="{{ route($item['route']) }}" data-vira-area="{{ $item['module'] }}" class="nav-link {{ request()->routeIs($item['route'].'*') ? 'active' : '' }}" @if(request()->routeIs($item['route'].'*')) aria-current="page" @endif><i data-lucide="{{ $item['icon'] }}"></i><span>{{ $item['title'] }}</span></a>
                    @endforeach
                </div>
                @if(count($secondary))
                    <div class="nav-divider"></div>
                    <button type="button" class="more-modules" @click="more=!more" :aria-expanded="more"><span>مشاهده همه ماژول‌ها</span><i data-lucide="chevron-down"></i></button>
                    <div class="nav-list" x-show="more" x-cloak>
                        @foreach($secondary as $item)
                            <a href="{{ route($item['route']) }}" data-vira-area="{{ $item['module'] }}" class="nav-link {{ request()->routeIs($item['route']) ? 'active' : '' }}" @if(request()->routeIs($item['route'])) aria-current="page" @endif><i data-lucide="{{ $item['icon'] }}"></i><span>{{ $item['title'] }}</span></a>
                        @endforeach
                    </div>
                @endif
            </nav>
            <div class="sidebar-bottom">
                <div class="sidebar-help"><i data-lucide="shield-check"></i><span>فضای کاری امن مجموعه</span></div>
                <div class="profile-row">
                    <div class="profile-avatar">{{ mb_substr($user->name, 0, 1) }}</div>
                    <div class="profile-meta"><strong>{{ $user->name }}</strong><small>{{ $user->hasRole('owner') ? 'مدیر مجموعه' : 'همکار مجموعه' }}</small></div>
                    <form method="post" action="{{ route('logout') }}" class="v4-profile-actions">@csrf<button type="submit" aria-label="خروج از حساب" title="خروج از حساب"><i data-lucide="log-out"></i></button></form>
                </div>
            </div>
        </aside>
    </div>
</x-layouts.base>
