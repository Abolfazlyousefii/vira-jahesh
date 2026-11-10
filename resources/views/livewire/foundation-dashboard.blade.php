@php
    // The dashboard is a presentation of already-authorized, module-filtered widget resolvers.
    $indexed = collect($widgets)->keyBy('key');
    $visible = fn (string $key): bool => isset($indexed[$key]) && $indexed[$key]['is_visible'];
    $metricKeys = ['sales.today', 'customers.count', 'crm.open', 'catalog.count', 'inventory.units', 'pos.count', 'storefront.published'];
    $metrics = collect($metricKeys)->filter($visible)->take(3)->map(fn ($key) => $indexed[$key])->values();
    $featured = ['sales.trend', 'crm.due', 'sales.recent'];
    $pinned = array_merge($metrics->pluck('key')->all(), $featured);
    $extras = collect($widgets)->filter(fn ($w) => $w['is_visible'] && !in_array($w['key'], $pinned, true))->values();
    $icons = ['sales.today' => 'wallet', 'customers.count' => 'users', 'crm.open' => 'clock', 'catalog.count' => 'package', 'inventory.units' => 'boxes', 'pos.count' => 'scan-line', 'storefront.published' => 'store'];
    $colors = ['teal', 'blue', 'purple'];
@endphp
<div class="v4-dashboard dashboard-layout">
    <div class="page-heading">
        <div class="heading-copy">
            <div class="breadcrumbs"><span>ویرا</span><span>›</span><span>میز کار</span><span>›</span><span class="current">نمای کلی</span></div>
            <div class="page-title-row"><h1 class="page-title">نمای کلی کسب‌وکار</h1><span class="page-subtitle">خلاصه اتفاقات مهم مجموعه در یک نگاه</span></div>
        </div>
        <div class="heading-actions">
            <span class="live-indicator"><i></i> داده‌های واقعی</span>
            <span class="date-button"><i data-lucide="calendar-days"></i>{{ \Morilog\Jalali\Jalalian::fromCarbon(now('Asia/Tehran'))->format('Y/m/d') }}</span>
            <button type="button" class="text-link" wire:click="$toggle('editing')" :aria-pressed="$wire.editing ? 'true' : 'false'">{{ $editing ? 'پایان ویرایش' : 'تنظیم ویجت‌ها' }}</button>
        </div>
    </div>
    @if($editing)
        <div class="v4-edit-panel">
            <h2>تنظیم نمایش و ترتیب ویجت‌ها</h2>
            @foreach($widgets as $widget)
                <div class="v4-edit-row" wire:key="v4-widget-edit-{{ $widget['key'] }}">
                    <span>{{ $widget['title'] }}</span>
                    <div>
                        <button type="button" wire:click="toggleWidget('{{ $widget['key'] }}')">{{ $widget['is_visible'] ? 'مخفی کردن' : 'نمایش دادن' }}</button>
                        <button type="button" wire:click="moveWidget('{{ $widget['key'] }}', 'up')" @disabled($loop->first) aria-label="بالا بردن {{ $widget['title'] }}">↑</button>
                        <button type="button" wire:click="moveWidget('{{ $widget['key'] }}', 'down')" @disabled($loop->last) aria-label="پایین بردن {{ $widget['title'] }}">↓</button>
                        <button type="button" wire:click="resizeWidget('{{ $widget['key'] }}', '{{ $widget['size'] === 'wide' ? 'compact' : 'wide' }}')">{{ $widget['size'] === 'wide' ? 'عادی' : 'عریض' }}</button>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
    <section class="kpi-grid" aria-label="شاخص‌های کلیدی">
        @forelse($metrics as $widget)
            <a class="kpi-card" href="{{ route($widget['data']['route']) }}" wire:key="v4-kpi-{{ $widget['key'] }}">
                <span class="kpi-icon {{ $colors[$loop->index % count($colors)] }}"><i data-lucide="{{ $icons[$widget['key']] ?? 'activity' }}"></i></span>
                <div class="kpi-content">
                    <span class="kpi-label">{{ $widget['title'] }}</span>
                    <strong class="kpi-value num">{{ number_format($widget['data']['value']) }} <small>{{ $widget['data']['unit'] }}</small></strong>
                    <div class="kpi-foot">{{ $widget['data']['description'] }}</div>
                </div>
            </a>
        @empty
            <div class="v4-dashboard-empty">با فعال‌سازی ماژول‌های مجاز، خلاصه آن‌ها در این قسمت نمایش داده می‌شود.</div>
        @endforelse
    </section>
    <div class="dashboard-panels">
        <div class="panel-row top">
            @if($visible('sales.trend'))
                @include($indexed['sales.trend']['view'], ['data' => $indexed['sales.trend']['data']])
            @endif
            @if($visible('crm.due'))
                @include($indexed['crm.due']['view'], ['data' => $indexed['crm.due']['data']])
            @endif
        </div>
        <div class="panel-row bottom">
            @if($visible('sales.recent'))
                @include($indexed['sales.recent']['view'], ['data' => $indexed['sales.recent']['data']])
            @endif
        </div>
    </div>
    @if($extras->isNotEmpty())
        <details class="v4-extras">
            <summary>مشاهده سایر ویجت‌ها ({{ $extras->count() }})</summary>
            <div class="v4-extra-grid">
                @foreach($extras as $widget)
                    <section class="v4-extra-item {{ $widget['size'] === 'wide' ? 'v4-extra-wide' : '' }}" wire:key="v4-extra-{{ $widget['key'] }}">
                        <h2>{{ $widget['title'] }}</h2>
                        @include($widget['view'], ['data' => $widget['data']])
                    </section>
                @endforeach
            </div>
        </details>
    @endif
</div>
