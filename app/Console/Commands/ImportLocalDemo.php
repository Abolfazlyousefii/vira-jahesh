<?php

namespace App\Console\Commands;

use App\Support\DemoImportGuard;
use Database\Seeders\DemoRetailSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Catalog\Models\CatalogCodeAlias;
use Modules\Catalog\Models\Product;
use Modules\Customers\Models\Customer;
use Throwable;

class ImportLocalDemo extends Command
{
    protected $signature = 'vira:demo:import {--yes : Confirm importing fake data into the current local SQLite database}';

    protected $description = 'Safely add sample retail data to the existing local Vira database (never production)';

    public function handle(): int
    {
        $connection = DB::connection();
        $database = (string) $connection->getDatabaseName();

        if (! DemoImportGuard::allows(app()->environment(), $connection->getDriverName(), $database, database_path('database.sqlite'))) {
            $this->error('این دستور فقط روی SQLite اصلی پروژه در محیط local مجاز است. هیچ اطلاعاتی تغییر نکرد.');

            return self::FAILURE;
        }

        foreach (['users', 'roles', 'permissions', 'customers', 'customer_phone_numbers', 'products', 'warehouses', 'stock_balances', 'sales', 'sale_items', 'sale_returns', 'follow_ups'] as $table) {
            if (! Schema::hasTable($table)) {
                $this->error("جدول {$table} وجود ندارد. ابتدا Migrationهای پروژه را با بررسی نسخه پشتیبان اجرا کنید.");

                return self::FAILURE;
            }
        }

        $fixture = json_decode(file_get_contents(database_path('seeders/fixtures/vira-retail-demo.json')), true, 512, JSON_THROW_ON_ERROR);

        // Do not attach demo purchases to a real customer or overwrite an existing product with a colliding key.
        foreach ($fixture['customers'] as $index => $row) {
            $existing = Customer::where('mobile', $row['phones'][0])->first();
            if ($existing && ($existing->name !== $row['name'] || $existing->email !== 'customer'.($index + 1).'@example.invalid')) {
                $this->error('شماره موبایل '.$row['phones'][0].' از قبل به مشتری دیگری تعلق دارد. ورود دمو لغو شد.');

                return self::FAILURE;
            }
        }

        foreach ($fixture['products'] as $row) {
            $sku = 'DEMO-'.$row['sku'];
            $existing = CatalogCodeAlias::findProductByLegacySku($sku)
                ?? Product::where('sku', $sku)->first();
            if ($existing && ($existing->name !== $row['name'] || $existing->is_published)) {
                $this->error("کد کالای {$sku} از قبل برای کالای دیگری استفاده شده است. ورود دمو لغو شد.");

                return self::FAILURE;
            }
        }

        if (! $this->option('yes') && ! $this->confirm('۲۸ مشتری، ۱۲ کالا، ۱۵۶ فاکتور و ۵۰ پیگیری ساختگی به دیتابیس محلی همین پروژه اضافه شود؟', false)) {
            $this->warn('عملیات لغو شد.');

            return self::FAILURE;
        }

        $directory = storage_path('app/private/demo-backups');
        if (! is_dir($directory) && ! mkdir($directory, 0700, true) && ! is_dir($directory)) {
            $this->error('ساخت پوشه نسخه پشتیبان امکان‌پذیر نیست. اطلاعاتی وارد نشد.');

            return self::FAILURE;
        }

        $backup = $directory.'/database-before-demo-'.date('Ymd-His').'-'.bin2hex(random_bytes(4)).'.sqlite';
        try {
            // VACUUM INTO creates a consistent SQLite backup, including when WAL is enabled.
            $connection->unprepared("VACUUM INTO '".str_replace("'", "''", $backup)."'");
            if (! is_file($backup) || filesize($backup) === 0) {
                throw new \RuntimeException('Backup is missing or empty');
            }
        } catch (Throwable $exception) {
            $this->error('نسخه پشتیبان ساخته نشد؛ ورود دیتا انجام نشد: '.$exception->getMessage());

            return self::FAILURE;
        }

        $this->info('نسخه پشتیبان دیتابیس: '.$backup);
        app()->instance('vira.demo.allow-main-local', true);

        try {
            $exitCode = $this->call('db:seed', ['--class' => DemoRetailSeeder::class, '--force' => true]);
            if ($exitCode !== self::SUCCESS) {
                $this->error('ورود دیتا کامل نشد. نسخه پشتیبان حفظ شده است.');

                return self::FAILURE;
            }
        } catch (Throwable $exception) {
            $this->error('ورود دیتا کامل نشد: '.$exception->getMessage());
            $this->warn('نسخه پشتیبان دست‌نخورده باقی مانده است.');

            return self::FAILURE;
        } finally {
            app()->forgetInstance('vira.demo.allow-main-local');
        }

        $this->info('اطلاعات دمو به دیتابیس فعلی ویرا اضافه شد؛ حساب‌های قبلی حفظ شدند. اجرای دوباره رکوردهای دمو را تکرار نمی‌کند.');

        return self::SUCCESS;
    }
}
