<div>
    <div class="s-heading">
        <div>
            <h1>نبض کسب‌وکار شما</h1>
            <p class="s-muted">خوش آمدی {{ auth()->user()->name }}؛ ویجت‌های این صفحه بر اساس مجوزهای شما نمایش داده می‌شوند.</p>
        </div>
        <button type="button" class="s-btn s-mainbtn" wire:click="$toggle('editing')" :aria-pressed="$wire.editing ? 'true' : 'false'">
            <i data-lucide="blocks"></i> {{ $editing ? 'پایان تنظیم چیدمان' : 'ویرایش چیدمان' }}
        </button>
    </div>
    <section class="vira-widget-grid" aria-label="ویجت‌های داشبورد">
        @foreach($widgets as $widget)
            @if($widget['is_visible'] || $editing)
                <section wire:key="widget-{{ $widget['key'] }}" class="s-panel vira-widget {{ $widget['size'] === 'wide' ? 'vira-widget-wide' : '' }}">
                    <div class="s-panelhead">
                        <h2>{{ $widget['title'] }}</h2>
                        @if($editing)<span class="s-pill">{{ $widget['is_visible'] ? 'نمایش داده می‌شود' : 'مخفی است' }}</span>@endif
                    </div>
                    @if($widget['is_visible'])
                        @include($widget['view'], ['data' => $widget['data']])
                    @else
                        <p class="s-muted">این ویجت در چیدمان شما مخفی است.</p>
                    @endif
                    @if($editing)
                        <div class="vira-widget-actions">
                            <button type="button" class="s-btn" wire:click="toggleWidget('{{ $widget['key'] }}')">{{ $widget['is_visible'] ? 'مخفی‌کردن' : 'نمایش‌دادن' }}</button>
                            <button type="button" class="s-btn" wire:click="moveWidget('{{ $widget['key'] }}', 'up')" @disabled($loop->first) aria-label="بالا بردن {{ $widget['title'] }}">↑</button>
                            <button type="button" class="s-btn" wire:click="moveWidget('{{ $widget['key'] }}', 'down')" @disabled($loop->last) aria-label="پایین بردن {{ $widget['title'] }}">↓</button>
                            <button type="button" class="s-btn" wire:click="resizeWidget('{{ $widget['key'] }}', '{{ $widget['size'] === 'wide' ? 'compact' : 'wide' }}')">{{ $widget['size'] === 'wide' ? 'اندازهٔ معمولی' : 'نمایش عریض' }}</button>
                        </div>
                    @endif
                </section>
            @endif
        @endforeach
    </section>
    @if(count($widgets) === 0)
        <section class="s-panel"><p class="s-muted">ویجتی با مجوزهای فعلی در دسترس نیست.</p></section>
    @endif
</div>
