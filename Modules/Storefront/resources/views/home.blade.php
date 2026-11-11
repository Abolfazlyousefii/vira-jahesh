@php
    $sectionMap = collect($sections)->keyBy('key');
    $hero = $sectionMap->get('hero');
    $features = $sectionMap->get('features');
    $cta = $sectionMap->get('cta');

    $heroSettings = $hero['settings'] ?? [];
    $featureSettings = $features['settings'] ?? [];
    $ctaSettings = $cta['settings'] ?? [];

    $heroHref = in_array($heroSettings['href'] ?? '', ['#site-features', '#site-cta', '#site-top'], true)
        ? $heroSettings['href']
        : '#site-features';

    if ($heroHref === '#site-features' && (!$features || !$features['is_visible'])) {
        $heroHref = '#site-modules';
    }
    if ($heroHref === '#site-cta' && (!$cta || !$cta['is_visible'])) {
        $heroHref = '#site-modules';
    }
    if ($heroHref === '#site-top' && (!$hero || !$hero['is_visible'])) {
        $heroHref = '#site-modules';
    }

    $salesPhone = preg_replace('/[^\d+]/u', '', (string) config('vira.sales_phone'));
    $salesPhoneDisplay = trim((string) config('vira.sales_phone'));

    $featureItems = [
        [
            'key' => 'first',
            'fallback' => 'مدیریت کالاها و تنوع‌ها',
            'icon' => 'box',
            'description' => 'تعریف کالا، دسته‌بندی، کدگذاری، تنوع محصول و نگهداری اطلاعات پایه در یک فضای منظم.',
            'tone' => 'purple',
        ],
        [
            'key' => 'second',
            'fallback' => 'انبار، موجودی و فروش',
            'icon' => 'warehouse',
            'description' => 'ثبت گردش کالا، مدیریت انبارها، فروش و فاکتور با ارتباط میان بخش‌های عملیاتی.',
            'tone' => 'green',
        ],
        [
            'key' => 'third',
            'fallback' => 'مشتریان و پیگیری‌ها',
            'icon' => 'users',
            'description' => 'دسترسی به اطلاعات مشتریان و پیگیری‌های مرتبط، در کنار سوابق فروش و ارتباطات.',
            'tone' => 'blue',
        ],
    ];

    $modules = [
        ['icon' => 'box', 'title' => 'کالاها و تنوع‌ها', 'description' => 'مدیریت کالاهای ساده و چندتنوعی، شناسه‌ها، بارکد، تصاویر، قیمت و وضعیت انتشار.', 'tone' => 'purple'],
        ['icon' => 'warehouse', 'title' => 'انبار و موجودی', 'description' => 'چند انبار، رسید خرید، انتقال، گردش موجودی و کنترل مانده کالا.', 'tone' => 'green'],
        ['icon' => 'receipt', 'title' => 'فروش و فاکتورها', 'description' => 'ثبت فروش، صدور فاکتور، مرجوعی جزئی و مشاهده گزارش‌های فروش.', 'tone' => 'blue'],
        ['icon' => 'users', 'title' => 'مشتریان و CRM', 'description' => 'پرونده مشتری، سوابق خرید و مرجوعی و پیگیری‌های مرتبط با مشتری.', 'tone' => 'rose'],
        ['icon' => 'scan', 'title' => 'صندوق فروشگاهی', 'description' => 'ثبت فروش حضوری با استفاده از همان اطلاعات کالا، موجودی و فروش.', 'tone' => 'orange'],
        ['icon' => 'globe', 'title' => 'ویترین اینترنتی', 'description' => 'نمایش عمومی کالاهای منتشرشده و اطلاعات محصول؛ خرید آنلاین در مرحله جداگانه تکمیل می‌شود.', 'tone' => 'teal'],
    ];
@endphp

<x-layouts.base title="ویرا | مدیریت یکپارچه کسب‌وکار">
    <div class="vira-landing" data-landing-page>
        <svg class="vl-symbols" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
            <symbol id="vl-arrow-left" viewBox="0 0 24 24"><path d="M19 12H5m7 7-7-7 7-7" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></symbol>
            <symbol id="vl-arrow-up-left" viewBox="0 0 24 24"><path d="M7 17 17 7M7 7h10v10" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></symbol>
            <symbol id="vl-login" viewBox="0 0 24 24"><path d="M10 17l5-5-5-5m5 5H3m9-9h6a3 3 0 0 1 3 3v12a3 3 0 0 1-3 3h-6" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></symbol>
            <symbol id="vl-box" viewBox="0 0 24 24"><path d="m12 3 9 5-9 5-9-5 9-5Zm-9 5v9l9 5 9-5V8m-9 5v9" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></symbol>
            <symbol id="vl-users" viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2m7-10a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm13 10v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></symbol>
            <symbol id="vl-warehouse" viewBox="0 0 24 24"><path d="m3 10 9-7 9 7v11H3V10Zm5 11v-7h8v7M3 10h18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></symbol>
            <symbol id="vl-chart" viewBox="0 0 24 24"><path d="M3 3v18h18M8 15l4-4 3 3 6-7" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></symbol>
            <symbol id="vl-globe" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M3 12h18M12 3a14 14 0 0 1 0 18M12 3a14 14 0 0 0 0 18" fill="none" stroke="currentColor" stroke-width="1.5"/></symbol>
            <symbol id="vl-layers" viewBox="0 0 24 24"><path d="m12 3 9 5-9 5-9-5 9-5Zm-9 9 9 5 9-5m-18 5 9 5 9-5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></symbol>
            <symbol id="vl-shield" viewBox="0 0 24 24"><path d="M12 22s8-4 8-11V5l-8-3-8 3v6c0 7 8 11 8 11Z" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="m9 12 2 2 4-4" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></symbol>
            <symbol id="vl-check" viewBox="0 0 24 24"><path d="m5 12 4 4L19 6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></symbol>
            <symbol id="vl-menu" viewBox="0 0 24 24"><path d="M4 7h16M4 12h16M4 17h16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></symbol>
            <symbol id="vl-chevron" viewBox="0 0 24 24"><path d="m6 9 6 6 6-6" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></symbol>
            <symbol id="vl-spark" viewBox="0 0 24 24"><path d="m12 3 1.7 5.3L19 10l-5.3 1.7L12 17l-1.7-5.3L5 10l5.3-1.7L12 3Zm7 12 .9 2.1L22 18l-2.1.9L19 21l-.9-2.1L16 18l2.1-.9L19 15Z" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></symbol>
            <symbol id="vl-receipt" viewBox="0 0 24 24"><path d="M4 3h16v18l-3-2-3 2-4-2-3 2-3-2V3Zm4 5h8m-8 4h8m-8 4h5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></symbol>
            <symbol id="vl-scan" viewBox="0 0 24 24"><path d="M4 7V4h3m10 0h3v3M4 17v3h3m10 0h3v-3M4 12h16m-12-4v8m3-8v8m3-8v8m3-8v8" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></symbol>
            <symbol id="vl-phone" viewBox="0 0 24 24"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.4 19.4 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7l.5 3a2 2 0 0 1-.6 1.7L7.1 10.3a16 16 0 0 0 6 6l1.9-1.9a2 2 0 0 1 1.7-.6l3 .5a2 2 0 0 1 1.7 2.6Z" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></symbol>
        </svg>

        <header class="vl-header">
            <div class="vl-container vl-nav">
                <a class="vl-brand" href="{{ route('storefront.home') }}" aria-label="ویرا، صفحه اصلی">
                    <span class="vl-brand-mark">و</span>
                    <span class="vl-brand-copy"><strong>ویرا</strong><small>محصولی از مجموعه جهش</small></span>
                </a>

                <nav class="vl-nav-links" aria-label="ناوبری اصلی">
                    <a href="{{ $features && $features['is_visible'] ? '#site-features' : '#site-modules' }}">امکانات</a>
                    <a href="#site-modules">ماژول‌ها</a>
                    <a href="#site-about">مزایای ویرا</a>
                    <a href="#site-process">مراحل راه‌اندازی</a>
                    <a href="#site-faq">سؤالات متداول</a>
                </nav>

                <div class="vl-nav-actions">
                    <a class="vl-btn vl-btn-quiet" href="{{ $cta && $cta['is_visible'] ? '#site-cta' : '#site-modules' }}">مشاوره و خرید</a>
                    <a class="vl-btn vl-btn-primary" href="{{ route('login') }}">
                        <svg class="vl-icon"><use href="#vl-login"/></svg>
                        ورود به پنل مدیریت
                    </a>
                </div>

                <details class="vl-mobile-nav">
                    <summary aria-label="باز کردن منو"><svg class="vl-icon"><use href="#vl-menu"/></svg></summary>
                    <nav aria-label="منوی موبایل">
                        <a href="{{ $features && $features['is_visible'] ? '#site-features' : '#site-modules' }}">امکانات</a>
                        <a href="#site-modules">ماژول‌ها</a>
                        <a href="#site-about">مزایای ویرا</a>
                        <a href="#site-process">مراحل راه‌اندازی</a>
                        <a href="#site-faq">سؤالات متداول</a>
                        <a href="{{ $cta && $cta['is_visible'] ? '#site-cta' : '#site-modules' }}">مشاوره و خرید</a>
                        <a class="vl-mobile-login" href="{{ route('login') }}">ورود به پنل مدیریت</a>
                    </nav>
                </details>
            </div>
        </header>

        <main>
            @php($staticSectionsRendered = false)
            @foreach($sections as $section)
                @if($section['key'] === 'hero' && $section['is_visible'])
                    @include('storefront::landing.hero')
                @elseif($section['key'] === 'features' && $section['is_visible'])
                    @include('storefront::landing.features')
                    @if(!$staticSectionsRendered)
                        @include('storefront::landing.static-sections')
                        @php($staticSectionsRendered = true)
                    @endif
                @elseif($section['key'] === 'cta' && $section['is_visible'])
                    @if(!$staticSectionsRendered)
                        @include('storefront::landing.static-sections')
                        @php($staticSectionsRendered = true)
                    @endif
                    @include('storefront::landing.cta')
                @endif
            @endforeach
            @if(!$staticSectionsRendered)
                @include('storefront::landing.static-sections')
            @endif

        </main>

        <footer class="vl-footer">
            <div class="vl-container">
                <div class="vl-footer-grid">
                    <div class="vl-footer-about">
                        <a class="vl-brand" href="{{ route('storefront.home') }}">
                            <span class="vl-brand-mark">و</span>
                            <span class="vl-brand-copy"><strong>ویرا</strong><small>محصولی از مجموعه جهش</small></span>
                        </a>
                        <p>ویرا بستری یکپارچه برای مدیریت بخش‌های مختلف کسب‌وکار است؛ راهکاری که با شناخت نیازهای هر مجموعه می‌تواند توسعه پیدا کند.</p>
                    </div>
                    <div><h3>دسترسی سریع</h3><nav><a href="#site-features">امکانات ویرا</a><a href="#site-modules">ماژول‌ها</a><a href="#site-about">مزایای ویرا</a><a href="#site-process">مراحل راه‌اندازی</a></nav></div>
                    <div><h3>راهنما</h3><nav><a href="#site-faq">پرسش‌های متداول</a><a href="{{ $cta && $cta['is_visible'] ? '#site-cta' : '#site-modules' }}">مشاوره و خرید</a><a href="{{ route('login') }}">ورود به پنل مدیریت</a></nav></div>
                    <div><h3>ارتباط با جهش</h3><nav>
                        @if($salesPhone !== '')
                            <a href="tel:{{ $salesPhone }}"><svg class="vl-icon"><use href="#vl-phone"/></svg> <bdi dir="ltr">{{ $salesPhoneDisplay }}</bdi></a>
                        @else
                            <span>برای دریافت مشاوره، از طریق راه‌های ارتباطی مجموعه جهش اقدام کنید.</span>
                        @endif
                    </nav></div>
                </div>
                <div class="vl-footer-bottom"><span>© تمامی حقوق این وب‌سایت متعلق به مجموعه جهش است.</span><span>ویرا؛ مدیریت یکپارچه کسب‌وکار</span></div>
            </div>
        </footer>
    </div>
    <script>
        (() => {
            const mobileMenu = document.querySelector('.vira-landing .vl-mobile-nav');
            if (!mobileMenu) return;
            mobileMenu.querySelectorAll('a').forEach((link) => {
                link.addEventListener('click', () => { mobileMenu.open = false; });
            });
        })();
    </script>
</x-layouts.base>
