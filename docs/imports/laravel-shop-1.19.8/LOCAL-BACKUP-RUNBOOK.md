# بکاپ دیتابیس لوکال ویرا در Windows / XAMPP

این دستورالعمل باید توسط مالک در سیستم لوکال اجرا شود؛ شاخه Git و خروجی `git pull` نسخهٔ پشتیبان دیتابیس نیست.

## آماده‌سازی (الزامی)

۱. `composer dev`، `php artisan serve` و Queue Workerها را متوقف کنید تا هنگام کپی SQLite عملیات نوشتن در جریان نباشد.
۲. از داخل `C:\xampp\htdocs\vira-jahesh` فایل `.env` را باز کنید و نوع `DB_CONNECTION` را ببینید. مقدار رمز عبور یا کلید را برای هیچ‌کس ارسال نکنید.
۳. مسیر بکاپ را **خارج از پوشهٔ Git و public** انتخاب کنید.

## اگر DB_CONNECTION=sqlite است

در PowerShell ریشهٔ پروژه:

```powershell
$ErrorActionPreference = 'Stop'
$backupDir = Join-Path $env:USERPROFILE 'ViraLocalBackups'
New-Item -ItemType Directory -Path $backupDir -Force | Out-Null
$db = Join-Path (Get-Location).Path 'database\database.sqlite'
if (-not (Test-Path -LiteralPath $db)) { throw 'database.sqlite not found; check DB_DATABASE in .env.' }
$stamp = Get-Date -Format 'yyyyMMdd-HHmmss'
$backup = Join-Path $backupDir "vira-$stamp.sqlite"
Copy-Item -LiteralPath $db -Destination $backup -ErrorAction Stop
Get-Item -LiteralPath $backup | Select-Object FullName, Length
Get-FileHash -LiteralPath $backup -Algorithm SHA256
```

اگر `DB_DATABASE` در .env به مسیر دیگری اشاره می‌کند، متغیر `$db` را به آن فایل واقعی تغییر دهید. هنگام بکاپ هیچ فرآیند PHP نباید دیتابیس را بنویسد. صفر نبودن حجم و SHA-256 را ثبت کنید. بکاپ را پیش از اجرای Migrationهای بعدی نگه دارید.

## اگر DB_CONNECTION=mysql است

خروجی `.env` را به کسی ندهید. در PowerShell، هنگام خاموش بودن عملیات حساس و با نام و کاربر دیتابیس منطبق با تنظیمات محلی:

```powershell
$backupDir = Join-Path $env:USERPROFILE 'ViraLocalBackups'
New-Item -ItemType Directory -Path $backupDir -Force | Out-Null
$backup = Join-Path $backupDir ("vira-" + (Get-Date -Format 'yyyyMMdd-HHmmss') + ".sql")
& 'C:\xampp\mysql\bin\mysqldump.exe' --single-transaction --quick --routines --triggers --default-character-set=utf8mb4 --host=127.0.0.1 --user=root --password --result-file=$backup YOUR_VIRA_DB_NAME
if ($LASTEXITCODE -ne 0) { throw 'mysqldump failed' }
Get-Item -LiteralPath $backup | Select-Object FullName, Length
Get-FileHash -LiteralPath $backup -Algorithm SHA256
```

`YOUR_VIRA_DB_NAME` و `--user=root` را بر اساس تنظیمات محلی اصلاح کنید. `--password` باید رمز را تعاملی بخواهد؛ رمز را داخل دستور یا Git ننویسید.

## تأیید تحویل

نام فایل، اندازه، SHA-256 و نتیجهٔ امکان خواندن بکاپ ثبت شود؛ ترجیحاً بازیابی روی دیتابیس آزمایشی مستقل آزموده شود. **فایل بکاپ یا اطلاعات خصوصی را در Pull Request بارگذاری نکنید.**

## بازیابی اضطراری

فقط پس از توقف تمام نویسنده‌ها و تهیهٔ بکاپ دوم از وضعیت فعلی، SQLite از نسخه سالم و هم‌مسیر جایگزین می‌شود. برای MySQL، دیتابیس آزمایشی و دستور import کنترل‌شده به‌کار رود؛ بازیابی روی دیتابیس اصلی بدون برنامه و تأیید انجام نشود.
