<?php

namespace App\Console\Commands;

use App\Support\DemoImportGuard;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Catalog\Actions\CatalogLegacyCodeRepair;
use Throwable;

class RepairCatalogCodes extends Command
{
    protected $signature = 'vira:catalog:repair-codes {--apply : Apply fixes to the local SQLite database} {--yes : Confirm changes without interactive prompt}';

    protected $description = 'Preview or safely repair historic demo product codes and missing internal barcodes';

    public function handle(CatalogLegacyCodeRepair $repair): int
    {
        foreach (['products', 'product_groups', 'catalog_sku_sequences', 'catalog_code_aliases'] as $table) {
            if (! Schema::hasTable($table)) {
                $this->error('ساختار دیتابیس ناقص است. ابتدا Migrationهای پروژه را اجرا کنید.');

                return self::FAILURE;
            }
        }

        $preview = $repair->preview();
        $this->info('کالاهای نمایشی با کد قدیمی: '.$preview['recode_count']);
        $this->info('کالاهای فاقد بارکد: '.$preview['barcode_count']);
        foreach ($preview['examples'] as $row) {
            $this->line($row['old_sku'].' ← '.$row['name']);
        }

        if (! $this->option('apply')) {
            $this->comment('فقط پیش‌نمایش بود؛ هیچ داده‌ای تغییر نکرد. برای اعمال پس از پشتیبان‌گیری از --apply استفاده کنید.');

            return self::SUCCESS;
        }

        $connection = DB::connection();
        if (! DemoImportGuard::allows(
            app()->environment(), $connection->getDriverName(),
            (string) $connection->getDatabaseName(), database_path('database.sqlite')
        )) {
            $this->error('اعمال تغییرات فقط در دیتابیس SQLite اصلی پروژه و محیط local مجاز است.');

            return self::FAILURE;
        }

        if (! $this->option('yes') && ! $this->confirm('کد کالاهای دمو اصلاح شود و برای کالاهای فاقد بارکد، بارکد داخلی ساخته شود؟', false)) {
            $this->warn('عملیات لغو شد.');

            return self::FAILURE;
        }

        if ($preview['recode_count'] === 0 && $preview['barcode_count'] === 0) {
            $this->info('همهٔ کدها و بارکدهای موردنظر از قبل اصلاح شده‌اند.');

            return self::SUCCESS;
        }

        $directory = storage_path('app/private/catalog-backups');
        if (! is_dir($directory) && ! mkdir($directory, 0700, true) && ! is_dir($directory)) {
            $this->error('ساخت پوشه نسخه پشتیبان امکان‌پذیر نیست.');

            return self::FAILURE;
        }

        $backup = $directory.'/database-before-catalog-codes-'.date('Ymd-His').'-'.bin2hex(random_bytes(4)).'.sqlite';

        try {
            // SQLite's consistent online backup includes uncheckpointed WAL writes.
            $connection->unprepared("VACUUM INTO '".str_replace("'", "''", $backup)."'");
            if (! is_file($backup) || filesize($backup) === 0) {
                throw new \RuntimeException('Backup was not created');
            }
        } catch (Throwable $exception) {
            report($exception);
            $this->error('نسخه پشتیبان ساخته نشد. هیچ کدی تغییر نکرد.');

            return self::FAILURE;
        }

        $this->info('نسخه پشتیبان: '.$backup);

        try {
            $result = $repair->apply();
        } catch (Throwable $exception) {
            report($exception);
            $this->error('اصلاح کدها انجام نشد؛ عملیات دیتابیس بازگردانده شد. نسخه پشتیبان محفوظ است.');

            return self::FAILURE;
        }

        $this->info('کدهای دمو اصلاح‌شده: '.$result['recoded']);
        $this->info('بارکدهای داخلی ساخته‌شده: '.$result['barcoded']);
        $this->info('شناسه کالا، فاکتورهای قدیمی و گردش موجودی تغییر نکردند.');

        return self::SUCCESS;
    }
}
