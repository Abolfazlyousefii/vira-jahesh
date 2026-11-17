# بکاپ و آماده‌سازی استقرار فاز صفر روی سرور

## وضعیت
این PR همچنان Draft است و فقط تحلیل، فهرست انتقال و ابزار بکاپ دارد؛ هنوز کد اجرایی Laravel Shop روی سرور منتقل نمی‌شود. ذخیرهٔ فایل اسکریپت در GitHub به معنای اجرای آن روی سرور یا وجود بکاپ واقعی نیست.

قبل از هر فرمان، مسیر پروژه و نوع DB_CONNECTION واقعی را فقط در .env خود سرور بررسی کنید؛ از ارسال رمزها، .env و دیتابیس در گفتگو یا GitHub خودداری کنید. بکاپ باید خارج از پوشهٔ Git و public_html ذخیره شود.

## Linux / SSH / SQLite
پیش‌نیاز: ابزار sqlite3 و دسترسی خواندن دیتابیس. این اسکریپت با SQLite online backup و integrity_check خروجی می‌گیرد؛ روی دیتابیس MySQL اجرا نشود.

بعد از merge و pull مربوطه، از پوشه اصلی پروژه اجرا کنید:

~~~bash
command -v sqlite3
export VIRA_BACKUP_DIR="$HOME/ViraBackups"
bash scripts/backup-vira-sqlite.sh
~~~

اگر در تنظیمات سرور مسیر DB_DATABASE متفاوت است، آن را صریح وارد کنید:

~~~bash
bash scripts/backup-vira-sqlite.sh "/absolute/path/to/database.sqlite"
~~~

سه مقدار Backup، Size و SHA256 را ذخیره کنید؛ فایل دیتابیس را ارسال نکنید. نسخهٔ خروجی باید روی سرور/محل جدا قابل بازیابی باشد. آپلودهای storage/app/public جداگانه بکاپ لازم دارند.

## cPanel / MySQL بدون SSH
در cPanel بخش **Backup / Backup Wizard** از MySQL database مربوط به ویرا بکاپ دریافت کنید. اگر در دسترس نیست، در **phpMyAdmin** همان دیتابیس را انتخاب و از Export خروجی SQL بگیرید؛ مطمئن شوید اطلاعات جدول‌ها و ساختار و در صورت استفاده Triggerها، Routines و Events درج شده‌اند. فایل را خارج از public_html نگه دارید و اندازه و SHA-256 آن را محاسبه کنید. فایل‌های آپلودی را نیز جداگانه بکاپ بگیرید.

برای MySQL در سرور دارای SSH می‌توان با mysqldump و فایل تنظیمات خصوصی Client خارج از مخزن نسخه پشتیبان گرفت؛ هرگز رمز را در Command Line، Log یا Git وارد نکنید.

## Windows / XAMPP لوکال
دستورات Copy-Item و Join-Path مخصوص PowerShell ویندوز هستند، نه لینوکس. پیش از کپی مستقیم SQLite باید composer dev، PHP server و queue و هر نویسندهٔ دیتابیس متوقف باشند. راهنما در LOCAL-BACKUP-RUNBOOK.md است.

## ترتیب امن
۱. تشخیص DB_CONNECTION سرور. ۲. بکاپ دیتابیس و آپلودها. ۳. آزمون خوانایی و بازیابی مستقل. ۴. بررسی Git و .env و وابستگی‌ها. ۵. فقط بعد از تأیید مجوز سورس، انتقال کنترل‌شده در PR مراحل بعد. ۶. روی دیتابیس تولید migrate:fresh یا reset انجام نشود.

**این مرحله هیچ تغییری در دادهٔ سرور ایجاد نمی‌کند تا اپراتور بکاپ واقعی را اجرا و گزارش کند.**
