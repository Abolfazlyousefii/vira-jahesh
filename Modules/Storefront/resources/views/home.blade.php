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
            @if($hero && $hero['is_visible'])
                <section class="vl-hero" id="site-top">
                    <div class="vl-container vl-hero-grid">
                        <div class="vl-hero-copy">
                            <span class="vl-eyebrow"><span class="vl-eyebrow-dot"></span> سامانه یکپارچه مدیریت کسب‌وکار</span>
                            <h1>{!! nl2br(e($heroSettings['title'] ?? 'تمام کسب‌وکارتان، در یک پنل مدیریتی یکپارچه')) !!}</h1>
                            <p>{{ $heroSettings['description'] ?? 'با ویرا، کالاها، انبار، فروش و ارتباط با مشتریان را در یک محیط منسجم مدیریت کنید.' }}</p>
                            <div class="vl-hero-actions">
                                <a class="vl-btn vl-btn-primary vl-btn-large" href="{{ route('login') }}">
                                    ورود به پنل مدیریت
                                    <svg class="vl-icon"><use href="#vl-arrow-left"/></svg>
                                </a>
                                <a class="vl-btn vl-btn-secondary vl-btn-large" href="{{ $heroHref }}">
                                    {{ $heroSettings['button'] ?? 'آشنایی با امکانات' }}
                                    <svg class="vl-icon"><use href="#vl-arrow-left"/></svg>
                                </a>
                            </div>
                            <div class="vl-hero-note"><svg class="vl-icon"><use href="#vl-shield"/></svg> ساختار ماژولار، دسترسی کنترل‌شده و قابلیت توسعه متناسب با کسب‌وکار</div>
                        </div>

                        <div class="vl-hero-visual" aria-label="پیش‌نمایش محیط مدیریت ویرا">
                            <div class="vl-visual-glow"></div>
                            @if(!empty($hero['image_url']))
                                <div class="vl-hero-image"><img src="{{ $hero['image_url'] }}" alt="تصویر معرفی ویرا" loading="eager"></div>
                            @else
                                <div class="vl-dashboard-card">
                                    <div class="vl-dashboard-top">
                                        <div class="vl-dashboard-brand"><span class="vl-dashboard-logo">و</span><strong>فضای کاری ویرا</strong></div>
                                        <span class="vl-dashboard-user"><span class="vl-dashboard-avatar">م</span> مدیر مجموعه</span>
                                    </div>
                                    <div class="vl-dashboard-heading"><strong>نمای کلی کسب‌وکار</strong><span>محیط مدیریت یکپارچه</span></div>
                                    <div class="vl-dashboard-modules">
                                        <div class="vl-dashboard-module"><span class="vl-module-icon purple"><svg class="vl-icon"><use href="#vl-box"/></svg></span><span><strong>کالاها</strong><small>تعریف و مدیریت کالا</small></span><b>›</b></div>
                                        <div class="vl-dashboard-module"><span class="vl-module-icon green"><svg class="vl-icon"><use href="#vl-warehouse"/></svg></span><span><strong>انبار و موجودی</strong><small>گردش و مانده کالا</small></span><b>›</b></div>
                                        <div class="vl-dashboard-module"><span class="vl-module-icon blue"><svg class="vl-icon"><use href="#vl-users"/></svg></span><span><strong>مشتریان</strong><small>اطلاعات و پرونده‌ها</small></span><b>›</b></div>
                                    </div>
                                    <div class="vl-dashboard-bottom">
                                        <span><i></i> ماژول‌های مرتبط در یک محیط</span>
                                        <span>داده‌های مجموعه شما</span>
                                    </div>
                                </div>
                            @endif
                            <div class="vl-float-card vl-float-one"><span class="vl-float-icon green"><svg class="vl-icon"><use href="#vl-check"/></svg></span><span><strong>مدیریت یکپارچه</strong><small>اطلاعات در یک محیط</small></span></div>
                            <div class="vl-float-card vl-float-two"><span class="vl-float-icon purple"><svg class="vl-icon"><use href="#vl-layers"/></svg></span><span><strong>قابل توسعه</strong><small>متناسب با نیاز مجموعه</small></span></div>
                        </div>
                    </div>
                </section>
            @endif

            <div class="vl-trust-strip">
                <div class="vl-container vl-trust-inner">
                    <div><svg class="vl-icon"><use href="#vl-box"/></svg> مدیریت کالا و محصولات</div>
                    <div><svg class="vl-icon"><use href="#vl-warehouse"/></svg> انبار و موجودی</div>
                    <div><svg class="vl-icon"><use href="#vl-users"/></svg> مشتریان و ارتباطات</div>
                    <div><svg class="vl-icon"><use href="#vl-chart"/></svg> گزارش‌های مدیریتی</div>
                </div>
            </div>

            @if($features && $features['is_visible'])
                <section class="vl-section" id="site-features">
                    <div class="vl-container">
                        <div class="vl-section-heading">
                            <span class="vl-section-kicker">امکانات ویرا</span>
                            <h2>همه‌چیز برای مدیریت ساده‌تر</h2>
                            <p>به‌جای استفاده از چند ابزار جداگانه، بخش‌های مختلف کسب‌وکار را در یک فضای مدیریتی منظم دنبال کنید.</p>
                        </div>
                        <div class="vl-feature-grid">
                            @foreach($featureItems as $item)
                                <article class="vl-feature-card">
                                    <span class="vl-feature-icon {{ $item['tone'] }}"><svg class="vl-icon"><use href="#vl-{{ $item['icon'] }}"/></svg></span>
                                    <h3>{{ $featureSettings[$item['key']] ?? $item['fallback'] }}</h3>
                                    <p>{{ $item['description'] }}</p>
                                    <a href="#site-modules" class="vl-text-link">آشنایی با ماژول‌ها <svg class="vl-icon"><use href="#vl-arrow-left"/></svg></a>
                                </article>
                            @endforeach
                        </div>
                    </div>
                </section>
            @endif

            <section class="vl-section vl-section-soft" id="site-modules">
                <div class="vl-container">
                    <div class="vl-section-heading">
                        <span class="vl-section-kicker">بخش‌های نرم‌افزار</span>
                        <h2>ماژول‌های کاربردی برای مدیریت روزمره</h2>
                        <p>هر بخش مسئولیت مشخصی دارد و اطلاعات موردنیازش را از هسته مشترک نرم‌افزار دریافت می‌کند.</p>
                    </div>
                    <div class="vl-module-grid">
                        @foreach($modules as $module)
                            <article class="vl-module-card">
                                <span class="vl-module-icon {{ $module['tone'] }}"><svg class="vl-icon"><use href="#vl-{{ $module['icon'] }}"/></svg></span>
                                <div><h3>{{ $module['title'] }}</h3><p>{{ $module['description'] }}</p></div>
                                <span class="vl-module-arrow"><svg class="vl-icon"><use href="#vl-arrow-up-left"/></svg></span>
                            </article>
                        @endforeach
                    </div>
                    <p class="vl-module-note"><svg class="vl-icon"><use href="#vl-shield"/></svg> امکانات هر مجموعه بر اساس نیازهای کاری و ماژول‌های فعال‌شده در زمان راه‌اندازی مشخص می‌شود.</p>
                </div>
            </section>

            <section class="vl-section" id="site-workspace">
                <div class="vl-container vl-showcase">
                    <div class="vl-showcase-copy">
                        <span class="vl-section-kicker">یک محیط مدیریتی منسجم</span>
                        <h2>از اطلاعات کالا تا ارتباط با مشتری؛ همه‌چیز در دسترس شماست</h2>
                        <p>ویرا با هدف یکپارچه‌سازی فرایندهای کسب‌وکار طراحی شده است. مدیر مجموعه می‌تواند متناسب با ساختار کاری خود، بخش‌های موردنیاز را در یک محیط مدیریتی دنبال کند.</p>
                        <div class="vl-check-list">
                            <div><span><svg class="vl-icon"><use href="#vl-check"/></svg></span> دسترسی منظم به اطلاعات و عملیات مجموعه</div>
                            <div><span><svg class="vl-icon"><use href="#vl-check"/></svg></span> ساختار ماژولار و قابلیت توسعه در مراحل بعدی</div>
                            <div><span><svg class="vl-icon"><use href="#vl-check"/></svg></span> سطح دسترسی متناسب با نقش کاربران</div>
                            <div><span><svg class="vl-icon"><use href="#vl-check"/></svg></span> تجربه یکپارچه در بخش‌های مختلف نرم‌افزار</div>
                        </div>
                        <a class="vl-btn vl-btn-primary" href="{{ route('login') }}">ورود به فضای مدیریت <svg class="vl-icon"><use href="#vl-arrow-left"/></svg></a>
                    </div>

                    <div class="vl-screen-wrap">
                        <div class="vl-screen">
                            <div class="vl-screen-bar"><span class="vl-screen-dots"><i></i><i></i><i></i></span><strong>پیش‌نمایش فضای کاری ویرا</strong><span>مدیر مجموعه</span></div>
                            <div class="vl-screen-body">
                                <aside class="vl-screen-sidebar">
                                    <strong>ویرا</strong>
                                    <span class="active"></span><span></span><span></span><span></span><span></span><span></span>
                                </aside>
                                <div class="vl-screen-content">
                                    <div class="vl-screen-welcome"><strong>فضای کاری مجموعه</strong><span class="vl-screen-avatar">م</span></div>
                                    <div class="vl-screen-tiles">
                                        <div><span class="vl-screen-tile-icon purple"><svg class="vl-icon"><use href="#vl-box"/></svg></span><strong>کالاهای نرم‌افزار</strong><small>اطلاعات و تنوع‌ها</small></div>
                                        <div><span class="vl-screen-tile-icon green"><svg class="vl-icon"><use href="#vl-warehouse"/></svg></span><strong>انبار و موجودی</strong><small>اسناد و گردش کالا</small></div>
                                        <div><span class="vl-screen-tile-icon blue"><svg class="vl-icon"><use href="#vl-users"/></svg></span><strong>مشتریان و CRM</strong><small>پرونده و پیگیری</small></div>
                                    </div>
                                    <div class="vl-screen-table">
                                        <div class="vl-screen-table-head"><strong>دسترسی سریع به بخش‌ها</strong><span>نمای کلی</span></div>
                                        <div class="vl-screen-row"><span>فروش و فاکتورها</span><b>بخش عملیاتی</b></div>
                                        <div class="vl-screen-row"><span>گزارش‌های مدیریتی</span><b>گزارش و تحلیل</b></div>
                                        <div class="vl-screen-row"><span>مدیریت کارکنان</span><b>سطح دسترسی</b></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="vl-section vl-section-soft" id="site-about">
                <div class="vl-container">
                    <div class="vl-section-heading">
                        <span class="vl-section-kicker">چرا ویرا؟</span>
                        <h2>مدیریت ساده‌تر، دید شفاف‌تر، توسعه‌پذیری بیشتر</h2>
                        <p>ویرا بستری برای نظم‌دادن به اطلاعات و فرایندهای کسب‌وکار است؛ با همراهی مجموعه جهش در مسیر راه‌اندازی و توسعه.</p>
                    </div>
                    <div class="vl-benefit-grid">
                        <article><span>۰۱ / یکپارچگی</span><h3>اطلاعات متمرکز</h3><p>بخش‌های مختلف را در یک محیط مدیریتی دنبال کنید و پراکندگی ابزارها را کاهش دهید.</p></article>
                        <article><span>۰۲ / انعطاف</span><h3>متناسب با کسب‌وکار</h3><p>امکانات و ماژول‌های موردنیاز بر اساس نیاز و اولویت‌های مجموعه بررسی می‌شوند.</p></article>
                        <article><span>۰۳ / توسعه</span><h3>آماده رشد</h3><p>ساختار ماژولار، مسیر توسعه تدریجی قابلیت‌ها و افزودن بخش‌های جدید را فراهم می‌کند.</p></article>
                        <article><span>۰۴ / همراهی</span><h3>همراهی مجموعه جهش</h3><p>در مسیر بررسی نیاز، راه‌اندازی و توسعه نرم‌افزار از همراهی تیم جهش استفاده می‌کنید.</p></article>
                    </div>
                </div>
            </section>

            <section class="vl-section" id="site-process">
                <div class="vl-container">
                    <div class="vl-section-heading">
                        <span class="vl-section-kicker">شروع همکاری</span>
                        <h2>از شناخت نیاز تا راه‌اندازی ویرا</h2>
                        <p>فرایند تهیه و راه‌اندازی نرم‌افزار با شناخت دقیق‌تر نیازهای مجموعه شما آغاز می‌شود.</p>
                    </div>
                    <div class="vl-steps">
                        <article><span>۱</span><h3>درخواست مشاوره</h3><p>نیاز اولیه و ساختار کاری مجموعه خود را مطرح می‌کنید.</p></article>
                        <article><span>۲</span><h3>بررسی نیازها</h3><p>فرایندهای کاری و ماژول‌های موردنیاز کسب‌وکار بررسی می‌شوند.</p></article>
                        <article><span>۳</span><h3>راه‌اندازی و تحویل</h3><p>نرم‌افزار بر اساس محدوده توافق‌شده آماده و راه‌اندازی می‌شود.</p></article>
                        <article><span>۴</span><h3>مدیریت کسب‌وکار</h3><p>مدیر مجموعه از طریق پنل مدیریت به امکانات فعال‌شده دسترسی خواهد داشت.</p></article>
                    </div>
                </div>
            </section>

            <section class="vl-section vl-section-soft" id="site-faq">
                <div class="vl-container">
                    <div class="vl-section-heading">
                        <span class="vl-section-kicker">پرسش‌های متداول</span>
                        <h2>پاسخ پرسش‌های شما درباره ویرا</h2>
                        <p>برای آشنایی بهتر با ساختار نرم‌افزار، پاسخ پرسش‌های پرتکرار را بخوانید.</p>
                    </div>
                    <div class="vl-faq-list">
                        <details class="vl-faq" open>
                            <summary>ویرا برای چه کسب‌وکارهایی مناسب است؟ <svg class="vl-icon"><use href="#vl-chevron"/></svg></summary>
                            <p>برای کسب‌وکارهایی که می‌خواهند بخش‌هایی مانند کالا، انبار، فروش، مشتریان و ارتباطات را در یک محیط مدیریتی منسجم‌تر مدیریت کنند. محدوده امکانات مناسب هر مجموعه در جلسه مشاوره بررسی می‌شود.</p>
                        </details>
                        <details class="vl-faq">
                            <summary>آیا همه ماژول‌ها برای تمام مشتریان فعال می‌شوند؟ <svg class="vl-icon"><use href="#vl-chevron"/></svg></summary>
                            <p>خیر. ماژول‌ها و امکانات موردنیاز هر کسب‌وکار بر اساس نیاز و محدوده توافق‌شده بررسی و فعال می‌شوند.</p>
                        </details>
                        <details class="vl-faq">
                            <summary>چطور می‌توانم برای تهیه ویرا مشاوره بگیرم؟ <svg class="vl-icon"><use href="#vl-chevron"/></svg></summary>
                            <p>برای هماهنگی جلسه معرفی و بررسی نیازهای کسب‌وکار، با مجموعه جهش تماس بگیرید. در صورت ثبت شماره تماس مستقیم، همین صفحه راه تماس را نیز نمایش می‌دهد.</p>
                        </details>
                        <details class="vl-faq">
                            <summary>مشتریان فعلی ویرا از کجا وارد نرم‌افزار می‌شوند؟ <svg class="vl-icon"><use href="#vl-chevron"/></svg></summary>
                            <p>مشتریان فعلی می‌توانند از دکمه «ورود به پنل مدیریت» در بالای صفحه استفاده کنند. این مسیر به صفحه ورود اصلی نرم‌افزار متصل است.</p>
                        </details>
                    </div>
                </div>
            </section>

            @if($cta && $cta['is_visible'])
                <section class="vl-cta" id="site-cta">
                    <div class="vl-container vl-cta-inner">
                        <span class="vl-cta-kicker"><span></span> همراه شما در مسیر رشد</span>
                        <h2>{{ $ctaSettings['title'] ?? 'مدیریت کسب‌وکارتان را یکپارچه‌تر کنید' }}</h2>
                        <p>{{ $ctaSettings['description'] ?? 'برای بررسی نیازهای مجموعه و آشنایی با مسیر راه‌اندازی ویرا، با تیم جهش در ارتباط باشید.' }}</p>
                        <div class="vl-cta-actions">
                            @if($salesPhone !== '')
                                <a class="vl-btn vl-btn-white" href="tel:{{ $salesPhone }}"><svg class="vl-icon"><use href="#vl-phone"/></svg> تماس با مجموعه جهش <bdi dir="ltr">{{ $salesPhoneDisplay }}</bdi></a>
                            @endif
                            <a class="vl-btn vl-btn-glass" href="{{ route('login') }}">ورود به پنل مدیریت <svg class="vl-icon"><use href="#vl-arrow-left"/></svg></a>
                        </div>
                    </div>
                </section>
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
