@props(['title' => 'مدیریت سایت'])
@inject('moduleAccess', 'App\Support\ModuleAccess')
@php
    $user = auth()->user();
    $canErp = $user->can('panel.access') && $moduleAccess->available('Core');
    $canHomepage = $moduleAccess->available('Storefront') && ($user->can('website.manage') || $user->can('storefront.manage'));
@endphp
<x-layouts.base :title="$title">
    <div class="website-admin-shell">
        <header class="website-admin-topbar">
            <a class="website-admin-brand" href="{{ route('website.dashboard') }}">
                <span class="website-admin-brand-mark">و</span>
                <span><strong>ویرا</strong><small>مدیریت وب‌سایت</small></span>
            </a>
            <div class="website-admin-top-actions">
                @if($moduleAccess->available('Storefront'))
                    <a href="{{ route('storefront.home') }}" target="_blank" rel="noopener"><i data-lucide="external-link"></i><span>نمایش سایت</span></a>
                @endif
                @if($canErp)
                    <a href="{{ route('panel.dashboard') }}"><i data-lucide="panels-top-left"></i><span>مدیریت داخلی</span></a>
                    <a href="{{ route('workspace.select') }}"><i data-lucide="arrow-left-right"></i><span>تغییر محیط</span></a>
                @endif
                <span class="website-admin-user">{{ $user->name }}</span>
                <form method="post" action="{{ route('logout') }}">@csrf<button type="submit" aria-label="خروج از حساب" title="خروج از حساب"><i data-lucide="log-out"></i></button></form>
            </div>
        </header>

        <div class="website-admin-body">
            <aside class="website-admin-sidebar" aria-label="منوی مدیریت سایت">
                <div class="website-admin-sidebar-heading">مدیریت وب‌سایت</div>
                <nav>
                    <a href="{{ route('website.dashboard') }}" class="{{ request()->routeIs('website.dashboard') ? 'active' : '' }}">
                        <i data-lucide="layout-dashboard"></i><span>نمای کلی</span>
                    </a>
                    @if($canHomepage)
                        <a href="{{ route('website.homepage') }}" class="{{ request()->routeIs('website.homepage') ? 'active' : '' }}">
                            <i data-lucide="panels-top-left"></i><span>صفحه اصلی سایت</span>
                        </a>
                    @endif
                </nav>
                <div class="website-admin-sidebar-note">
                    <i data-lucide="puzzle"></i>
                    <span>امکانات دیگر پس از بررسی و اتصال اسکریپت اضافه می‌شوند.</span>
                </div>
            </aside>

            <main class="website-admin-main">
                <div class="website-admin-page-heading">
                    <div><span>ویرا / مدیریت سایت</span><h1>{{ $title }}</h1></div>
                </div>
                {{ $slot }}
            </main>
        </div>
    </div>
</x-layouts.base>
