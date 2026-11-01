<div class="s-flex" style="flex-wrap:wrap;gap:12px">
    @can('modules.manage')
        <a class="s-btn" href="{{ route('panel.modules') }}">وضعیت ماژول‌ها <i data-lucide="blocks"></i></a>
    @endcan
    @if($data['storefront'] && auth()->user()->can('storefront.manage'))
        <a class="s-btn" href="{{ route('panel.storefront') }}">مدیریت سایت <i data-lucide="store"></i></a>
    @endif
    @if($data['storefront'])
        <a class="s-btn" href="{{ route('storefront.home') }}">مشاهدهٔ سایت <i data-lucide="arrow-left"></i></a>
    @endif
    <a class="s-btn" href="{{ route('panel.dashboard') }}">میز کار <i data-lucide="layout-dashboard"></i></a>
</div>
