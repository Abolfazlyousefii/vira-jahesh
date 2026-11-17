<x-layouts.website-admin title="داشبورد مدیریت سایت">
    <div class="website-admin-page">
        <section class="website-admin-welcome">
            <div>
                <span class="website-admin-eyebrow">WEBSITE CONTROL CENTER</span>
                <h2>مدیریت وب‌سایت مجموعه</h2>
                <p>محتوا و تجربه حضور آنلاین کسب‌وکارتان را از این محیط مدیریت کنید.</p>
            </div>
            @if(app(\App\Support\ModuleAccess::class)->available('Storefront'))
                <a class="website-admin-button website-admin-button--quiet" href="{{ route('storefront.home') }}" target="_blank" rel="noopener">
                    <i data-lucide="external-link"></i>
                    نمایش سایت
                </a>
            @endif
        </section>

        <section class="website-admin-feature">
            <span class="website-admin-feature-icon"><i data-lucide="layout-template"></i></span>
            <div class="website-admin-feature-copy">
                <h2>مدیریت صفحه اصلی</h2>
                <p>ویرایش سکشن‌های فعلی صفحه معرفی و تنظیم ترتیب و نمایش آن‌ها.</p>
            </div>
            @if(app(\App\Support\ModuleAccess::class)->available('Storefront') && (auth()->user()->can('website.manage') || auth()->user()->can('storefront.manage')))
                <a class="website-admin-button" href="{{ route('website.homepage') }}">ورود به مدیریت</a>
            @else
                <span class="website-admin-muted">دسترسی مدیریت فعال نیست</span>
            @endif
        </section>

        <section class="website-admin-next">
            <div class="website-admin-next-icon"><i data-lucide="blocks"></i></div>
            <div>
                <h2>محیط آماده اتصال اسکریپت</h2>
                <p>ساختار مستقل مدیریت سایت آماده شده است. پس از بررسی اسکریپت شما، بخش‌های صفحات، منوها، رسانه‌ها، تنظیمات و SEO در همین ماژول یکپارچه می‌شوند؛ بدون ساخت بک‌اند موازی برای کالا و موجودی.</p>
            </div>
        </section>
    </div>
</x-layouts.website-admin>
