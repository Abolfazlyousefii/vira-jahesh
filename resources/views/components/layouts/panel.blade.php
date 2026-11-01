@props(['title' => 'میز کار ویرا'])
@inject('moduleAccess', 'App\Support\ModuleAccess')
<x-layouts.base :title="$title">
    <div class="s-app" :class="{ 's-compact': collapsed }">
        <aside class="s-sidebar" :class="{ 's-show': menu }" aria-label="منوی اصلی">
            <a href="{{ route('panel.dashboard') }}" class="s-brand"><div class="s-logo">و</div><div class="s-hidecollapse"><div class="s-brandname">ویرا</div><small>یکپارچه با جهش</small></div></a>
            <div class="s-workspace"><div class="s-avatar"><i data-lucide="store"></i></div><div class="s-hidecollapse"><strong>مجموعهٔ من</strong><div class="s-small" style="color:#a9adc8">فضای کار مدیریت</div></div></div>
            <div><div class="s-cap">میز کار</div><nav class="s-nav">
                <a href="{{ route('panel.dashboard') }}" class="{{ request()->routeIs('panel.dashboard') ? 'active' : '' }}"><i data-lucide="layout-dashboard"></i><span class="s-hidecollapse">داشبورد مدیریتی</span></a>
                @foreach(['Customers', 'Crm', 'Catalog', 'Inventory', 'Sales', 'Pos'] as $name)
                    @if($moduleAccess->available($name))
                        @can(strtolower($name).'.view')
                        <a href="{{ route('panel.'.strtolower($name)) }}" class="{{ request()->routeIs('panel.'.strtolower($name)) ? 'active' : '' }}"><i data-lucide="{{ config('vira.modules.'.$name.'.icon') }}"></i><span class="s-hidecollapse">{{ config('vira.modules.'.$name.'.label') }}</span></a>
                        @endcan
                    @endif
                @endforeach
            </nav></div>
            <div><div class="s-cap">مدیریت</div><nav class="s-nav">
                @can('modules.manage')<a href="{{ route('panel.modules') }}" class="{{ request()->routeIs('panel.modules') ? 'active' : '' }}"><i data-lucide="blocks"></i><span class="s-hidecollapse">ماژول‌های مجموعه</span></a>@endcan
                @if($moduleAccess->available('Storefront'))
                    @can('storefront.manage')
                        <a href="{{ route('panel.storefront') }}" class="{{ request()->routeIs('panel.storefront') ? 'active' : '' }}"><i data-lucide="store"></i><span class="s-hidecollapse">مدیریت صفحهٔ اصلی سایت</span></a>
                    @endcan
                    <a href="{{ route('storefront.home') }}"><i data-lucide="store"></i><span class="s-hidecollapse">مشاهدهٔ سایت</span></a>
                @endif
            </nav></div>
            <div class="s-sidebottom"><div class="s-flex"><div class="s-avatar">{{ mb_substr(auth()->user()->name, 0, 1) }}</div><div class="s-hidecollapse"><strong class="s-small">{{ auth()->user()->name }}</strong><div class="s-small" style="color:#a9adc8">{{ auth()->user()->hasRole('owner') ? 'مدیر مجموعه' : 'کاربر مجموعه' }}</div></div></div></div>
        </aside>
        <main class="s-main"><header class="s-topbar">
            <div class="s-flex"><button class="s-btn s-iconbtn s-collapsebtn" @click="collapsed = !collapsed" :aria-expanded="!collapsed" aria-label="باز و بسته کردن منو"><i data-lucide="panel-right-close"></i></button><button class="s-btn s-iconbtn s-mobilemenu" @click="menu = !menu" :aria-expanded="menu" aria-label="منوی اصلی"><i data-lucide="menu"></i></button><span class="s-muted">میز کار یکپارچهٔ شما</span></div>
            <div class="s-controls"><button class="s-swatch" style="background:#7762de" @click="palette='violet'" :aria-pressed="palette==='violet'" aria-label="تم بنفش"></button><button class="s-swatch" style="background:#347dc0" @click="palette='ocean'" :aria-pressed="palette==='ocean'" aria-label="تم آبی"></button><button class="s-swatch" style="background:#b45b87" @click="palette='rose'" :aria-pressed="palette==='rose'" aria-label="تم رز"></button><button class="s-btn s-iconbtn" @click="mode = mode==='dark' ? 'light' : 'dark'" :aria-pressed="mode==='dark'" aria-label="حالت تیره"><i data-lucide="moon"></i></button><form method="post" action="{{ route('logout') }}">@csrf<button class="s-btn s-iconbtn" aria-label="خروج از حساب"><i data-lucide="log-out"></i></button></form></div>
        </header><div class="s-breadcrumb"><span>ویرا</span><span>/</span><span>{{ $title }}</span><span class="s-topdate" style="margin-right:auto">{{ \Morilog\Jalali\Jalalian::fromCarbon(now()->timezone('Asia/Tehran'))->format('Y/m/d') }}</span></div><div class="s-content">{{ $slot }}</div><footer class="s-footer"><span>ویرا · یکپارچه با جهش</span><span>فضای کار امن مجموعه</span></footer></main>
    </div>
</x-layouts.base>
