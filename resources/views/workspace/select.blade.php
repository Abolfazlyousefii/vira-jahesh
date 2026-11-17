<x-layouts.base title="انتخاب محیط کاری ویرا">
    <main class="workspace-select">
        <div class="workspace-select-card">
            <a class="workspace-select-brand" href="{{ url('/') }}">
                <span class="workspace-select-mark">و</span>
                <span><strong>ویرا</strong><small>یکپارچه با جهش</small></span>
            </a>
            <div class="workspace-select-intro">
                <span class="workspace-select-eyebrow">YOUR WORKSPACE</span>
                <h1>به ویرا خوش آمدید</h1>
                <p>برای ادامه، محیط کاری موردنظرتان را انتخاب کنید.</p>
            </div>
            <div class="workspace-select-options">
                @if($canWebsite)
                    <a class="workspace-select-option workspace-select-option--website" href="{{ route('website.dashboard') }}">
                        <span class="workspace-select-option-icon"><i data-lucide="globe"></i></span>
                        <span class="workspace-select-option-copy">
                            <strong>مدیریت سایت</strong>
                            <small>صفحات، محتوا، ظاهر و تنظیمات وب‌سایت</small>
                        </span>
                        <i class="workspace-select-arrow" data-lucide="arrow-left"></i>
                    </a>
                @endif
                @if($canErp)
                    <a class="workspace-select-option workspace-select-option--erp" href="{{ route('panel.dashboard') }}">
                        <span class="workspace-select-option-icon"><i data-lucide="layout-dashboard"></i></span>
                        <span class="workspace-select-option-copy">
                            <strong>مدیریت داخلی</strong>
                            <small>ERP، CRM، کالا، انبار، فروش و کارکنان</small>
                        </span>
                        <i class="workspace-select-arrow" data-lucide="arrow-left"></i>
                    </a>
                @endif
            </div>
            <form class="workspace-select-logout" method="post" action="{{ route('logout') }}">@csrf<button type="submit">خروج از حساب</button></form>
        </div>
    </main>
</x-layouts.base>
