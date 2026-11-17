# مرزهای انتقال و فهرست تداخل‌ها

## انتقال کل قابلیت‌ها، نه جایگزینی هستهٔ لاراول

بستهٔ Laravel Shop از Laravel 10.48.29 و PHP >=8.2 <8.4 استفاده می‌کند، درحالی‌که ویرا Laravel 13 و PHP >=8.3 دارد. قالب و منطق تجاری باید با حفظ نتیجهٔ عملکردی سازگار شوند؛ `composer.json`، `composer.lock`، `bootstrap`، `config/auth.php` و `routes/auth.php` اسکریپت نباید جایگزین فایل‌های ویرا شوند.

| بخش سورس اسکریپت | مقصد / تصمیم |
| --- | --- |
| `resources/views/back/` و کنترلرهای Back | `WebsiteAdmin` با Routeهای `/site-admin`؛ بازنگاشت Dependency و Permission |
| `themes/DefaultTheme/src/resources/views` | نمایش عمومی در `Storefront`، بدون درهم‌ریختن CSS پنل ERP |
| `themes/DefaultTheme/src/Controllers` و Routeهای قالب | کنترلرهای عمومی تحت Namespace مستقل و Route Map |
| Page, Post, Menu, Slider, Banner, Widget | زیرسیستم محتوای WebsiteAdmin و مالکیت جداول جدید |
| Product, Category, Price | رابط سازگاری با Catalog (بدون Product موازی نهایی) |
| Stock, Reserve, Warehouse | فقط Inventory مرجع نهایی موجودی باشد |
| User, Address | کاربران Fortify و Customers؛ از ایجاد حساب‌های تکراری جلوگیری شود |
| Cart, Order, OrderItem, Transaction, Gateway | مسیر سفارش آنلاین با مرز مشخص از Sales و ثبت غیرتکراری پرداخت |
| Ticket, Wallet, Discount, Notification, API | قابلیت مستقل با تست امنیت و قرارداد اتصال |
| `routes/api/v1.php` | بازنگاشت مسیرها و Auth؛ ساخت مستندات نسخه‌بندی‌شدهٔ API |
| `public/`, `resources/`, `themes/` | تفکیک دارایی‌ها، مسیرها و build pipeline؛ Mix قدیمی مستقیم جایگزین Vite نشود |
| `vendor/`, `storage/`, `.env.example` | کپی مستقیم ممنوع؛ مدیریت وابستگی، فایل‌های اجرایی و محرمانه جداگانه بررسی شود |

## برخوردهای شناخته‌شده

- **ورود و دسترسی:** اسکریپت دارای `/login`، `/logout`، `/register`، login مدیریتی با prefix متغیر و امکان `loginAsUser` است؛ ویرا Fortify و مجوزهای مستقل `website.*` / `panel.*` دارد. Routeهای auth قدیمی فعال نشوند.
- **مسیر اصلی سایت:** قالب اسکریپت Route اصلی `/` را تعریف می‌کند؛ ویرا اکنون صفحه معرفی `/` و فروشگاه `/shop` دارد. قبل از فعال‌سازی، URL Map و Redirect Plan تهیه شود.
- **مدل‌های تجاری:** `User`، `Product` و جداول `users`، `products` از هر دو طرف وجود دارند. در بررسی بیشتر، `jobs` و مدل نقش/مجوز نیز تداخل دارند؛ Migration قدیمی روی دیتابیس ویرا اجرا نشود.
- **بسته‌های راه‌اندازی:** Laravel Shop از Sanctum 3، Laravel Mix و بسته‌های Laravel 10 استفاده می‌کند. انتخاب وابستگی جایگزین تنها پس از بررسی سازگاری و تست انجام شود.
- **API:** وجود `routes/api/v1.php` به معنای آمادگی همهٔ APIهای checkout و payment نیست. ابتدا فهرست endpointها و سناریوهای واقعی تهیه شود.
- **موجودی و سفارش:** مسیرهای پرداخت و رزرو باید از Actionهای مرکزی Inventory/Sales عبور کنند. تراکنش دیتابیس، Idempotency و جلوگیری از دوباره‌ثبت الزامی است.

## نقشه مالکیت پیشنهادی

- `WebsiteAdmin`: مدیریت محتوا، رسانه، منو، تنظیمات و عملیات وب‌سایت.
- `Storefront`: صفحات عمومی، تجربه خرید، نمایش کالا و مصرف سرویس‌ها.
- `Catalog`: حقیقت کالا و قیمت.
- `Inventory`: حقیقت موجودی و رزرو.
- `Customers`: پرونده و هویت تجاری مشتری.
- `Sales`: فروش، فاکتور، بازپرداخت و قواعد اصلی ثبت تراکنش.
- `Core`: حساب و مجوز کاربر، تنظیمات مشترک، ثبت رویداد.

تمام فایل‌ها برای انتقال ارزیابی خواهند شد؛ حذف یا بازنویسی هر قابلیت باید در PR جداگانه و با تست رفتار مستند شود.
