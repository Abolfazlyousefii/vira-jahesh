# ویرا — یکپارچه با جهش

نرم‌افزار یکپارچه و ماژولار ویرا برای مدیریت سایت، فروشگاه، مشتریان، انبار، فروش، CRM و صندوق.

## مرجع محصول و انتقال به چت جدید

برای ارسال یک فایل کامل به چت دیگر، [مرجع یکپارچهٔ محصول](VIRA-PROJECT-CONTEXT.fa.md) را استفاده کنید. این فایل هدف محصول، وضعیت واقعی کد، مشخصات ERP/CRM/صندوق/سایت، طراحی، معماری و متن آمادهٔ ادامهٔ کار را شامل می‌شود. [مستندهای تفکیکی](docs/product/README.md) مرجع ویرایش همین محتوا هستند.

## وضعیت فعلی

ویرا یک Laravel Modular Monolith است و هستهٔ عملیاتی کالا، مشتری، انبار، فروش دستی، مرجوعی، صندوق، CRM پایه، گزارش فروش و ویترین عمومی را دارد. ورود مشترک Fortify دو محیط مدیریتی مجزا را در اختیار می‌گذارد: /workspace برای انتخاب محیط، /panel برای ERP/CRM و /site-admin برای مدیریت سایت. ماژول WebsiteAdmin و پوستهٔ اولیهٔ آن ایجاد شده؛ اسکریپت آمادهٔ مدیریت سایت هنوز دریافت و یکپارچه نشده است. سبد خرید، تسویه و پرداخت آنلاین، حسابداری کامل و لایسنس مرکزی هنوز آماده نیستند.

## راه‌اندازی

نیازمند PHP 8.3 یا بالاتر با mbstring، intl، gd، exif، zip، bcmath و درایور دیتابیس، Composer و Node.js 22 یا بالاتر است.

```powershell
composer install
Copy-Item .env.example .env
php artisan key:generate
New-Item database/database.sqlite -ItemType File -ErrorAction SilentlyContinue
php artisan migrate --seed
npm ci
npm run build
php artisan vira:owner your-email@example.com
composer dev
```

دستور ساخت مدیر، رمز را مخفیانه از شما می‌پرسد؛ حساب موجود را بازنویسی نمی‌کند. در نصب فعلی، حساب اولیه به‌صورت محلی ایجاد شده و مشخصات آن داخل `storage/app/private/initial-access.txt` قرار دارد. این فایل، دیتابیس و `.env` در گیت ذخیره نمی‌شوند.

- سایت: http://127.0.0.1:8017
- انتخاب محیط: http://127.0.0.1:8017/workspace
- مدیریت داخلی ERP/CRM: http://127.0.0.1:8017/panel
- مدیریت سایت: http://127.0.0.1:8017/site-admin
- وضعیت اجرای برنامه: `/up`؛ این مسیر جای پایش دیتابیس و سرویس‌های وابسته را نمی‌گیرد.

## اجزای اصلی

- Laravel 13 و Fortify برای هسته و احراز هویت.
- Livewire 4، Alpine همراه آن، Blade، Tailwind 4 و Vite برای رابط.
- Nwidart Laravel Modules برای بارگذاری ماژول‌ها.
- Spatie Permission، Activitylog و Media Library برای مجوز، ثبت فعالیت و زیرساخت فایل.
- Morilog Jalali برای تاریخ شمسی، Brick Money برای محاسبات دقیق پول، Lucide برای آیکن‌ها، Chart.js برای گزارش‌های آینده و فونت محلی Vazir.
- PHPUnit و Pint برای آزمون و یکدستی کد؛ GitHub Actions برای اجرای بررسی‌ها.

کیف پول، درگاه، تلفن، پیامک و پرداخت اشتراک در این مرحله نصب و متصل نشده‌اند؛ انتخاب آن‌ها با نیاز و سرویس واقعی هر قابلیت انجام می‌شود.

## ساختار و بررسی

ماژول‌ها داخل `Modules/` هستند؛ [تصمیم‌های معماری](docs/architecture.md) و [اجرای محلی و XAMPP](docs/local-development.md) را ببینید.

```powershell
composer check
npm run build
php artisan module:list
```

این محصول اختصاصی جهش است. استفاده از کتابخانه‌های متن‌باز تابع مجوز همان کتابخانه‌هاست. طرح پنل، پیاده‌سازی اختصاصی بر اساس پیش‌نمایش تأییدشده است؛ قالب تجاری Vuexy یا Metronic خریداری یا در پروژه کپی نشده است.
