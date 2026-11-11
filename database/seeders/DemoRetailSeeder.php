<?php

namespace Database\Seeders;

use App\Models\User;
use App\Support\DemoImportGuard;
use App\Support\ModuleAccess;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Catalog\Actions\InternalBarcode;
use Modules\Catalog\Models\CatalogCodeAlias;
use Modules\Catalog\Models\Product;
use Modules\Crm\Models\FollowUp;
use Modules\Customers\Models\Customer;
use Modules\Inventory\Actions\StockLedger;
use Modules\Inventory\Models\StockBalance;
use Modules\Inventory\Models\StockMovement;
use Modules\Inventory\Models\Warehouse;
use Modules\Sales\Actions\RecordReturn;
use Modules\Sales\Actions\RecordSale;
use Modules\Sales\Models\Sale;
use Modules\Sales\Models\SaleReturn;
use Ramsey\Uuid\Uuid;
use RuntimeException;

/**
 * Explicit demo-only data. Refuses the normal application database and production.
 * Does not use real contacts, send messages, delete rows or reset migrations.
 */
class DemoRetailSeeder extends Seeder
{
    public function run(): void
    {
        $database = (string) DB::connection()->getDatabaseName();
        $dedicatedDemo = preg_match('/^vira-demo(?:-test)?\.sqlite$/', basename($database)) === 1;
        $mainLocalDemo = app()->bound('vira.demo.allow-main-local')
            && app('vira.demo.allow-main-local') === true
            && DemoImportGuard::allows(app()->environment(), DB::connection()->getDriverName(), $database, database_path('database.sqlite'));

        $allowed = app()->environment('local', 'testing')
            && DB::connection()->getDriverName() === 'sqlite'
            && ($dedicatedDemo || $mainLocalDemo);

        if (! $allowed) {
            throw new RuntimeException('DemoRetailSeeder فقط روی SQLite دمو یا با دستور محافظت‌شده vira:demo:import قابل اجراست.');
        }
        foreach (['Customers', 'Catalog', 'Inventory', 'Sales', 'Crm'] as $module) {
            if (! app(ModuleAccess::class)->available($module)) {
                throw new RuntimeException('برای ساخت داده دمو، ماژول '.$module.' باید فعال باشد.');
            }
        }

        $fixture = json_decode(file_get_contents(__DIR__.'/fixtures/vira-retail-demo.json'), true, 512, JSON_THROW_ON_ERROR);
        $oldUser = Auth::user();
        try {
            DB::transaction(function () use ($fixture): void {
                $actor = User::firstOrCreate(['email' => 'demo-seeder@vira.invalid'], [
                    'name' => 'کاربر ثبت‌کننده دمو', 'password' => Str::random(80),
                ]);
                $actor->assignRole('owner');
                Auth::login($actor);

                $warehouse = Warehouse::firstOrCreate(['name' => 'انبار نمایشی ویرا']);
                $products = [];
                foreach ($fixture['products'] as $row) {
                    $oldSku = 'DEMO-'.$row['sku'];
                    $product = CatalogCodeAlias::findProductByLegacySku($oldSku)
                        ?? Product::firstOrCreate(['sku' => $oldSku], [
                            'name' => $row['name'], 'price_toman' => $row['price_toman'],
                            'category' => 'کالای نمایشی', 'is_active' => true, 'is_published' => false,
                        ]);
                    app(InternalBarcode::class)->fillMissing($product);
                    $products[] = $product;
                }
                foreach ($products as $product) {
                    if (! StockBalance::where('warehouse_id', $warehouse->id)->where('product_id', $product->id)->exists()) {
                        app(StockLedger::class)->adjust($warehouse->id, $product->id, 1000, 'موجودی ابتدایی دمو');
                    }
                }

                $customers = [];
                foreach ($fixture['customers'] as $index => $row) {
                    $customers[] = $customer = Customer::firstOrCreate(['mobile' => $row['phones'][0]], [
                        'name' => $row['name'], 'city' => $row['city'], 'note' => $row['note'],
                        'customer_type' => $index % 6 === 0 ? 'business' : 'person',
                        'email' => 'customer'.($index + 1).'@example.invalid',
                    ]);
                    foreach (array_slice($row['phones'], 1) as $phoneIndex => $number) {
                        $customer->phoneNumbers()->firstOrCreate(['number_normalized' => $number], [
                            'type' => $phoneIndex % 2 ? 'work' : 'mobile',
                            'label' => $phoneIndex % 2 ? 'محل کار' : 'شماره دوم',
                        ]);
                    }
                }

                for ($i = 0; $i < 156; $i++) {
                    $token = Uuid::uuid5(Uuid::NAMESPACE_URL, 'vira-retail-demo-sale-'.$i)->toString();
                    if (Sale::where('request_token', $token)->exists()) {
                        continue;
                    }
                    $buyer = $customers[($i * 7 + intdiv($i, 28)) % count($customers)];
                    $first = $products[($i * 5) % count($products)];
                    $items = [['product_id' => $first->id, 'quantity' => 1 + $i % 3]];
                    if ($i % 3 === 0) {
                        $second = $products[($i * 5 + 3) % count($products)];
                        $items[] = ['product_id' => $second->id, 'quantity' => 1 + $i % 2];
                    }
                    $sale = app(RecordSale::class)->handle($token, $warehouse->id, $buyer->id, $items, 'direct');
                    $date = now('Asia/Tehran')->subDays(($i * 11) % 530 + 1)->setTime(10 + $i % 8, 15)->utc();
                    Sale::whereKey($sale->id)->update(['created_at' => $date, 'updated_at' => $date]);
                    StockMovement::where('reference', 'sale:'.$sale->id)->update(['created_at' => $date]);
                }

                for ($i = 20; $i < 156; $i += 15) {
                    $saleToken = Uuid::uuid5(Uuid::NAMESPACE_URL, 'vira-retail-demo-sale-'.$i)->toString();
                    $sale = Sale::where('request_token', $saleToken)->firstOrFail();
                    $returnToken = Uuid::uuid5(Uuid::NAMESPACE_URL, 'vira-retail-demo-return-'.$i)->toString();
                    if (SaleReturn::where('request_token', $returnToken)->exists()) {
                        continue;
                    }
                    $return = app(RecordReturn::class)->handle($returnToken, $sale->items()->firstOrFail()->id, 1, 'مرجوعی آزمایشی', 'cash', true);
                    $date = $sale->created_at->copy()->addDays(2);
                    SaleReturn::whereKey($return->id)->update(['created_at' => $date, 'updated_at' => $date]);
                    StockMovement::where('reference', 'sale-return:'.$return->id)->update(['created_at' => $date]);
                }

                for ($i = 0; $i < 50; $i++) {
                    FollowUp::firstOrCreate(['customer_id' => $customers[$i % count($customers)]->id, 'title' => 'پیگیری نمایشی شماره '.($i + 1)], [
                        'user_id' => $actor->id,
                        'note' => 'یادداشت آزمایشی برای بررسی طراحی و ارتباط مشتری.',
                        'due_at' => now()->addDays($i % 15 - 4),
                        'status' => $i % 5 === 0 ? 'done' : 'open',
                        'completed_at' => $i % 5 === 0 ? now()->subDays(1) : null,
                    ]);
                }
            }, 3);
        } finally {
            Auth::logout();
            if ($oldUser) {
                Auth::login($oldUser);
            }
        }
        $this->command?->info('Demo: 28 customers, 12 products, 156 sales and 50 follow-ups. Re-running is safe.');
    }
}
