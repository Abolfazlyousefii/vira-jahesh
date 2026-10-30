# اجرای محلی با Laragon یا XAMPP

پوشهٔ فعلی پروژه `E:\laragon\www\vira` است. اجرای PHP CLI از Laragon انجام می‌شود. وب‌سرور موجود روی پورت ۸۰ این سیستم متعلق به XAMPP است؛ هیچ تنظیم عمومی آن تغییر داده نشده است.

## اجرای فوری

```powershell
Set-Location E:\laragon\www\vira
composer dev
```

این دستور وب‌سرور محلی روی پورت ۸۰۱۷، صف و Vite را اجرا می‌کند. برای نمایش فایل‌های ساخته‌شده بدون Vite، بعد از `npm run build` فقط `php artisan serve --host=127.0.0.1 --port=8017` کافی است. صف‌ها در این حالت برای پردازش کار پس‌زمینه باید جدا اجرا شوند.

Laravel Pail به‌صورت وابستگی پیش‌فرض skeleton وجود دارد، ولی به دلیل محدودیت pcntl روی ویندوز در دستور اجرای روزانه استفاده نشده است.

## Apache

DocumentRoot را حتماً روی `E:/laragon/www/vira/public` تنظیم کنید، نه ریشهٔ پروژه. نمونهٔ VirtualHost زیر را پس از انتخاب نام محلی، به تنظیمات خود اضافه کنید:

```apache
<VirtualHost *:80>
    ServerName vira.test
    DocumentRoot "E:/laragon/www/vira/public"
    <Directory "E:/laragon/www/vira/public">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

نام `vira.test` باید در hosts سیستم به 127.0.0.1 اشاره کند. mod_rewrite فعال باشد. بعد از تغییر دامنه، `APP_URL=http://vira.test` و `php artisan optimize:clear` را اجرا کنید. نسخهٔ PHP وب‌سرور هم باید حداقل 8.3 و افزونه‌های لازم را داشته باشد؛ نسخهٔ PHP ترمینال لزوماً با Apache یکسان نیست.

## دیتابیس

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

پیش از استفادهٔ واقعی: دیتابیس و فایل‌ها پشتیبان قابل بازیابی داشته باشند؛ HTTPS، `APP_DEBUG=false`، دسترسی فایل مناسب و workerهای مدیریت‌شده تنظیم شوند. `.env`، دیتابیس، رمز اولیه و محتویات `storage` را منتشر نکنید. نسخهٔ فعلی برای شروع توسعه است، نه فروشگاه آمادهٔ دریافت وجه.
