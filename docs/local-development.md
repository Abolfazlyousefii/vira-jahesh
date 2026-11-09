# اجرای محلی با Laragon یا XAMPP

پوشهٔ فعلی پروژه `C:\xampp\htdocs\vira-jahesh` است. PHP محلی از XAMPP اجرا می‌شود؛ هیچ تنظیم عمومی Apache/MySQL برای سرور توسعهٔ پورت ۸۰۱۷ تغییر داده نشده است.

## اجرای فوری

```powershell
Set-Location C:\xampp\htdocs\vira-jahesh
composer dev
```

این دستور وب‌سرور محلی روی پورت ۸۰۱۷، صف و Vite را اجرا می‌کند. برای نمایش فایل‌های ساخته‌شده بدون Vite، بعد از `npm run build` فقط `php artisan serve --host=127.0.0.1 --port=8017` کافی است. صف‌ها در این حالت برای پردازش کار پس‌زمینه باید جدا اجرا شوند.

Laravel Pail به‌صورت وابستگی پیش‌فرض skeleton وجود دارد، ولی به دلیل محدودیت pcntl روی ویندوز در دستور اجرای روزانه استفاده نشده است.

## Apache

DocumentRoot را حتماً روی `C:/xampp/htdocs/vira-jahesh/public` تنظیم کنید، نه ریشهٔ پروژه. نمونهٔ VirtualHost زیر را پس از انتخاب نام محلی، به تنظیمات خود اضافه کنید:

```apache
<VirtualHost *:80>
    ServerName vira.test
    DocumentRoot "C:/xampp/htdocs/vira-jahesh/public"
    <Directory "C:/xampp/htdocs/vira-jahesh/public">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

نام `vira.test` باید در hosts سیستم به 127.0.0.1 اشاره کند. mod_rewrite فعال باشد. بعد از تغییر دامنه، `APP_URL=http://vira.test` و `php artisan optimize:clear` را اجرا کنید. نسخهٔ PHP وب‌سرور هم باید حداقل 8.3 و افزونه‌های لازم را داشته باشد؛ نسخهٔ PHP ترمینال لزوماً با Apache یکسان نیست.

## شناسهٔ عددی کارکنان

فرم ورود، ایمیل و شناسهٔ کاربری عددی را می‌پذیرد. حسابی با ایمیل داخلی به شکل `09120000000@vira.local` می‌تواند با `09120000000` (ارقام فارسی/عربی نیز پذیرفته می‌شوند) یا همان ایمیل وارد شود. این شناسه فقط برای حساب کارکنانِ از قبل ساخته‌شده است؛ ورود با موبایل مشتری، ثبت‌نام عمومی، OTP و تأیید مالکیت شماره ایجاد نمی‌کند. رمز اختصاصی همچنان لازم است و حساب غیرفعال پذیرفته نمی‌شود. محدودیت تلاش ورود برای نمایش عددی/ایمیلی همین شناسه مشترک است. برای ساخت مدیر، فرمان `vira:owner` قبلی با ایمیل داخلی و رمز انتخابی اپراتور استفاده می‌شود؛ هیچ رمز پیش‌فرضی وجود ندارد.

## تنظیم دیتابیس

نصب فعلی SQLite است و نیازی به روشن بودن MySQL ندارد. برای یک دیتابیس جدید MySQL، آن را در XAMPP ایجاد و مقادیر زیر را در `.env` تنظیم کنید:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=vira
DB_USERNAME=your_local_user
DB_PASSWORD=your_local_password
```

سپس `php artisan optimize:clear` و `php artisan migrate --seed` را اجرا کنید و مدیر جدید بسازید. تغییر اتصال، داده‌های SQLite را منتقل نمی‌کند؛ انتقال دادهٔ موجود کار جداگانه است. اجرای محلی این نسخه با SQLite بررسی شده است؛ اجرای MySQL باید پس از اتصال آزمایش شود.

## فعال‌سازی ماژول

```powershell
php artisan module:list
php artisan module:disable Crm
php artisan module:enable Crm
php artisan optimize:clear
```

Core زیرساخت الزامی است و نباید در نصب فعال خاموش شود. تغییر فعال‌سازی در حال حاضر مخصوص کل نصب است. وابستگی غیرفعال باعث عدم دسترسی ماژول وابسته می‌شود و اطلاعاتی حذف نمی‌کند. ویژگی‌های اختیاری را می‌توان با مقادیر `MODULE_*_ENABLED` در `.env` نیز محدود کرد. پس از هر تغییر، کش تنظیمات پاک شود.

## تولید و انتشار

### فایل موقت تصویر در محیط محدود

اگر PHP هنگام بارگذاری تصویر پیام `file created in the system's temporary directory` صادر کند، مسیر `upload_tmp_dir` آن فرایند قابل نوشتن نیست. این پیام می‌تواند پاسخ JSON بارگذاری را خراب کند. در این محیط، مسیر موقت فقط برای فرایند سرور به `storage/app/private/php-upload-tmp` تغییر داده شد؛ php.ini سراسری تغییر نکرد.

نمونهٔ اجرای مستقل در PowerShell از ریشهٔ همین پروژه:

```powershell
Set-Location C:\xampp\htdocs\vira-jahesh
New-Item -ItemType Directory -Path storage/app/private/php-upload-tmp -Force
$uploadTemp = (Resolve-Path storage/app/private/php-upload-tmp).Path
php -d "upload_tmp_dir=$uploadTemp" -S 127.0.0.1:8017 -t public scripts/local-server-router.php
```

### خطای SQLite فقط‌خواندنی در اجرای محدود

در محیط محدود Codex، آغاز فرایند با working directory برابر `public/` اجازهٔ نوشتن دیتابیس واقع در `database/` را نداشت؛ فایل دیتابیس پرچم ReadOnly نداشت. همان تراکنش آزمایشی از ریشهٔ پروژه موفق و از `public/` ناموفق بود. خطا هنگام ذخیرهٔ نشست ورود دیده می‌شد؛ روشن بودن MySQL راه‌حل این SQLite نیست.

فرایند سرور را از ریشهٔ پروژه اجرا کنید. `scripts/local-server-router.php` فقط هنگام رسیدگی به درخواست، پوشه را به `public/` می‌برد و router استاندارد Laravel را اجرا می‌کند. DocumentRoot همچنان `public/` است و ریشهٔ مخزن عمومی نمی‌شود. فایل vendor، مجوزهای عمومی Windows، دادهٔ دیتابیس و تنظیمات احراز هویت تغییر نکردند. این helper فقط برای سرور محلی است، نه استقرار production.

بررسی رفع خطا در ۲۰۲۶-۱۱-۰۹: نوشتن آزمایشی دیتابیس با rollback، دریافت فرم ورود واقعی (نه فقط status)، درخواست دوم با همان نشست، هدایت مهمان پنل به ورود و دریافت manifest دارایی‌ها موفق بودند؛ تب خطادار نیز در مرورگر به صفحهٔ ورود سالم رسید.

سرور فعلی از assets ساخته‌شده استفاده می‌کند؛ برای تغییر CSS/JS دوباره build لازم است. GD برای ساخت تصویر مصنوعی آزمون/PHPUnit فقط در همان فرایند CLI فعال شد؛ سرور بررسی تصویر بدون پردازش/تبدیل GD اجرا شد. پیش‌نمایش فایل‌های آزمایشی از فایل‌های کاری جدا بود.

پیش از استفادهٔ واقعی: دیتابیس و فایل‌ها پشتیبان قابل بازیابی داشته باشند؛ HTTPS، `APP_DEBUG=false`، دسترسی فایل مناسب و workerهای مدیریت‌شده تنظیم شوند. `.env`، دیتابیس، رمز اولیه و محتویات `storage` را منتشر نکنید. نسخهٔ فعلی برای شروع توسعه است، نه فروشگاه آمادهٔ دریافت وجه.
