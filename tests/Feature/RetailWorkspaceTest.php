<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Modules\Catalog\Livewire\ProductsPage;
use Modules\Catalog\Models\Product;
use Modules\Core\Dashboard\WidgetRegistry;
use Modules\Core\Livewire\BusinessSettingsPage;
use Modules\Crm\Livewire\FollowUpsPage;
use Modules\Crm\Models\FollowUp;
use Modules\Customers\Livewire\CustomersPage;
use Modules\Customers\Models\Customer;
use Modules\Inventory\Actions\StockLedger;
use Modules\Inventory\Livewire\InventoryPage;
use Modules\Inventory\Models\Warehouse;
use Modules\Pos\Livewire\PosPage;
use Modules\Sales\Actions\RecordSale;
use Modules\Sales\Livewire\SalesPage;
use Morilog\Jalali\Jalalian;
use Spatie\Activitylog\Models\Activity;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class RetailWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    private function owner(): User
    {
        $this->seed();
        $owner = User::factory()->create();
        $owner->assignRole('owner');
        $this->actingAs($owner);

        return $owner;
    }

    private function product(int $price = 125000): Product
    {
        return Product::create(['name' => 'دفتر آبی', 'sku' => '00003001', 'price_toman' => $price, 'category' => 'نوشت‌افزار', 'is_active' => true, 'is_published' => false]);
    }

    private function stockedProduct(int $quantity = 5): array
    {
        $product = $this->product();
        $warehouse = Warehouse::create(['name' => 'انبار اصلی']);
        app(StockLedger::class)->adjust($warehouse->id, $product->id, $quantity, 'موجودی اولیه');

        return [$product, $warehouse];
    }

    public function test_owner_can_save_business_identity_without_changing_the_money_unit(): void
    {
        $this->owner();
        Livewire::test(BusinessSettingsPage::class)->set('form.name', 'فروشگاه نمونه')->set('form.phone', '۰۲۱۱۲۳۴۵۶۷۸')->call('save')->assertHasNoErrors()->assertSee('ذخیره شد');
        $this->assertDatabaseHas('business_settings', ['id' => 1, 'name' => 'فروشگاه نمونه', 'phone' => '02112345678']);
        $this->get('/panel')->assertSee('فروشگاه نمونه');
    }

    public function test_business_settings_always_use_the_reserved_singleton_record(): void
    {
        $this->owner();
        DB::table('business_settings')->insert(['id' => 2, 'name' => 'رکورد قدیمی']);
        Livewire::test(BusinessSettingsPage::class)->set('form.name', 'مجموعهٔ اصلی')->call('save')->assertHasNoErrors();
        $this->assertDatabaseHas('business_settings', ['id' => 1, 'name' => 'مجموعهٔ اصلی']);
    }

    public function test_customer_can_be_created_edited_and_searched_with_persian_digits(): void
    {
        $this->owner();
        $page = Livewire::test(CustomersPage::class)->call('create')->set('form.name', 'مشتری آزمایشی')->set('form.mobile', '۰۹۱۲۱۲۳۴۵۶۷')->call('save')->assertHasNoErrors();
        $customer = Customer::firstOrFail();
        $this->assertSame('09121234567', $customer->mobile);
        $page->call('edit', $customer->id)->set('form.city', 'تهران')->call('save')->set('search', '۰۹۱۲')->assertSee('مشتری آزمایشی');
        $this->assertDatabaseHas('customers', ['id' => $customer->id, 'city' => 'تهران']);
        $page->set('search', 'وجود ندارد')->assertDontSee('مشتری آزمایشی')->assertSee('نتیجه‌ای پیدا نشد');
    }

    public function test_customer_mobile_must_be_valid_and_unique(): void
    {
        $this->owner();
        Customer::create(['name' => 'اول', 'mobile' => '09121234567']);
        Livewire::test(CustomersPage::class)->call('create')->set('form.name', 'دوم')->set('form.mobile', '09121234567')->call('save')->assertHasErrors('form.mobile')->set('form.mobile', '123')->call('save')->assertHasErrors('form.mobile');
        $this->assertDatabaseCount('customers', 1);
    }

    public function test_selected_customer_remains_visible_when_the_search_changes(): void
    {
        $this->owner();
        $customer = Customer::create(['name' => 'مشتری اول', 'mobile' => '09121234567']);
        Livewire::test(FollowUpsPage::class)->set('form.customer_id', (string) $customer->id)->set('customerSearch', 'جست‌وجوی دیگر')->assertViewHas('customers', fn ($customers) => $customers->contains('id', $customer->id));
    }

    public function test_inactive_product_can_still_be_selected_for_inventory_corrections(): void
    {
        $this->owner();
        [$product] = $this->stockedProduct();
        $product->update(['is_active' => false]);
        Livewire::test(InventoryPage::class)->set('productId', (string) $product->id)->set('search', 'جست‌وجوی دیگر')->assertViewHas('products', fn ($products) => $products->contains('id', $product->id));
    }

    public function test_view_permission_does_not_allow_livewire_mutation(): void
    {
        $this->seed();
        $user = User::factory()->create();
        $user->givePermissionTo(['panel.access', 'customers.view']);
        Livewire::actingAs($user)->test(CustomersPage::class)->set('form.name', 'غیرمجاز')->set('form.mobile', '09121234567')->call('save')->assertForbidden();
        $this->assertDatabaseCount('customers', 0);
    }

    public function test_all_workspace_pages_require_login_permissions_and_module_availability(): void
    {
        $pages = ['Core' => ['/panel/settings', BusinessSettingsPage::class], 'Customers' => ['/panel/customers', CustomersPage::class], 'Catalog' => ['/panel/catalog', ProductsPage::class], 'Inventory' => ['/panel/inventory', InventoryPage::class], 'Sales' => ['/panel/sales', SalesPage::class], 'Pos' => ['/panel/pos', PosPage::class], 'Crm' => ['/panel/crm', FollowUpsPage::class]];
        foreach ($pages as [$url]) {
            $this->get($url)->assertRedirect('/login');
        }
        $owner = $this->owner();
        foreach ($pages as $module => [$url, $component]) {
            $this->get($url)->assertOk();
            config(['vira.modules.'.$module.'.enabled' => false]);
            $this->get($url)->assertNotFound();
            Livewire::actingAs($owner)->test($component)->assertNotFound();
            config(['vira.modules.'.$module.'.enabled' => true]);
        }
        $operator = User::factory()->create();
        $operator->assignRole('operator');
        foreach ($pages as [$url]) {
            $this->actingAs($operator)->get($url)->assertForbidden();
        }
    }

    public function test_product_has_exact_integer_toman_price_and_automatic_sku(): void
    {
        $this->owner();
        Livewire::test(ProductsPage::class)->call('create')->set('form.name', 'دفتر آبی')->set('form.sku', '00003001')->set('form.price_toman', '۱۲۵۰۰۰')->call('save')->assertHasNoErrors();
        $this->assertDatabaseHas('products', ['sku' => '001000001', 'price_toman' => 125000]);
        Livewire::test(ProductsPage::class)->call('create')->set('form.name', 'کپی')->set('form.sku', '00003001')->set('form.price_toman', '12.5')->call('save')->assertHasErrors(['form.price_toman']);
    }

    public function test_inventory_records_auditable_movement_and_rejects_negative_stock(): void
    {
        $this->owner();
        [$product, $warehouse] = $this->stockedProduct(3);
        Livewire::test(InventoryPage::class)->set('warehouseId', (string) $warehouse->id)->set('productId', (string) $product->id)->set('quantity', '۲')->set('direction', 'out')->set('reason', 'کالای آسیب‌دیده')->call('saveMovement')->assertHasNoErrors();
        $this->assertDatabaseHas('stock_balances', ['warehouse_id' => $warehouse->id, 'product_id' => $product->id, 'quantity' => 1]);
        $this->assertDatabaseHas('stock_movements', ['delta' => -2, 'reason' => 'کالای آسیب‌دیده']);
        Livewire::test(InventoryPage::class)->set('warehouseId', (string) $warehouse->id)->set('productId', (string) $product->id)->set('quantity', '2')->set('direction', 'out')->set('reason', 'خروج اضافه')->call('saveMovement')->assertHasErrors('quantity');
        $this->assertDatabaseCount('stock_movements', 2);
    }

    public function test_sale_uses_server_prices_and_repeated_submission_does_not_double_charge_stock(): void
    {
        $this->owner();
        [$product, $warehouse] = $this->stockedProduct();
        $token = (string) Str::uuid();
        $action = app(RecordSale::class);
        $sale = $action->handle($token, $warehouse->id, null, [['product_id' => $product->id, 'quantity' => 2, 'price_toman' => 1]], 'direct');
        $repeat = $action->handle($token, $warehouse->id, null, [['product_id' => $product->id, 'quantity' => 2]], 'direct');
        $this->assertSame($sale->id, $repeat->id);
        $this->assertSame(250000, $sale->total_toman);
        $this->assertDatabaseCount('sales', 1);
        $this->assertDatabaseHas('stock_balances', ['quantity' => 3]);
        $this->assertDatabaseHas('sale_items', ['name' => 'دفتر آبی', 'unit_price_toman' => 125000, 'quantity' => 2]);
    }

    public function test_insufficient_stock_rolls_back_the_entire_sale(): void
    {
        $this->owner();
        [$product, $warehouse] = $this->stockedProduct(1);
        try {
            app(RecordSale::class)->handle((string) Str::uuid(), $warehouse->id, null, [['product_id' => $product->id, 'quantity' => 2]], 'direct');
            $this->fail('Overselling must fail.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('items', $exception->errors());
        }
        $this->assertDatabaseCount('sales', 0);
        $this->assertDatabaseCount('sale_items', 0);
        $this->assertDatabaseCount('stock_movements', 1);
        $this->assertDatabaseHas('stock_balances', ['quantity' => 1]);
    }

    public function test_two_sales_cannot_sell_the_same_last_unit(): void
    {
        $this->owner();
        [$product, $warehouse] = $this->stockedProduct(1);
        app(RecordSale::class)->handle((string) Str::uuid(), $warehouse->id, null, [['product_id' => $product->id, 'quantity' => 1]], 'pos');
        try {
            app(RecordSale::class)->handle((string) Str::uuid(), $warehouse->id, null, [['product_id' => $product->id, 'quantity' => 1]], 'direct');
            $this->fail('The last unit was already sold.');
        } catch (ValidationException) {
            $this->assertDatabaseCount('sales', 1);
            $this->assertDatabaseHas('stock_balances', ['quantity' => 0]);
        }
    }

    public function test_sales_and_pos_pages_both_complete_sales_through_shared_action(): void
    {
        $this->owner();
        [$product, $warehouse] = $this->stockedProduct(4);
        foreach ([SalesPage::class => 'direct', PosPage::class => 'pos'] as $component => $channel) {
            Livewire::test($component)->set('warehouseId', (string) $warehouse->id)->call('addItem', $product->id)->call('completeSale')->assertHasNoErrors()->assertSee('فروش ثبت شد');
            $this->assertDatabaseHas('sales', ['channel' => $channel, 'total_toman' => 125000]);
        }
        $this->assertDatabaseHas('stock_balances', ['quantity' => 2]);
    }

    public function test_pos_shows_available_quantity_from_the_selected_warehouse(): void
    {
        $this->owner();
        [$product, $warehouse] = $this->stockedProduct(3);
        Livewire::test(PosPage::class)->set('warehouseId', (string) $warehouse->id)->assertSee('3 عدد موجود');
    }

    public function test_sale_action_authorizes_channel_and_disabled_dependencies(): void
    {
        $owner = $this->owner();
        [$product, $warehouse] = $this->stockedProduct();
        $operator = User::factory()->create();
        $operator->givePermissionTo(['panel.access', 'sales.view']);
        $this->actingAs($operator);
        try {
            app(RecordSale::class)->handle((string) Str::uuid(), $warehouse->id, null, [['product_id' => $product->id, 'quantity' => 1]], 'direct');
            $this->fail('Read-only user cannot sell.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
        $this->actingAs($owner);
        config(['vira.modules.Inventory.enabled' => false]);
        Livewire::test(SalesPage::class)->assertNotFound();
        $this->assertDatabaseCount('sales', 0);
    }

    public function test_crm_can_schedule_and_complete_a_customer_followup(): void
    {
        $this->owner();
        $customer = Customer::create(['name' => 'مشتری', 'mobile' => '09121234567']);
        Livewire::test(FollowUpsPage::class)->call('create')->set('form.customer_id', (string) $customer->id)->set('form.title', 'تماس برای سفارش')->set('form.due_date', '1405/08/20')->set('form.note', 'پرسش دربارهٔ رنگ')->call('save')->assertHasNoErrors()->assertSee('تماس برای سفارش')->call('complete', 1)->assertHasNoErrors();
        $this->assertDatabaseHas('follow_ups', ['title' => 'تماس برای سفارش', 'status' => 'done']);
        config(['vira.modules.Crm.enabled' => false]);
        $this->get('/panel/customers')->assertOk();
    }

    public function test_business_widgets_are_real_and_hidden_without_permissions(): void
    {
        $this->owner();
        Customer::create(['name' => 'مشتری', 'mobile' => '09121234567']);
        $this->product();
        $this->get('/panel')->assertOk()->assertSee('مشتریان ثبت‌شده')->assertSee('کالاهای فعال')->assertSee('فروش امروز')->assertSee('پیگیری‌های باز');
        $user = User::factory()->create();
        $user->givePermissionTo('panel.access');
        $this->actingAs($user)->get('/panel')->assertDontSee('مشتریان ثبت‌شده')->assertDontSee('فروش امروز');
    }

    public function test_default_dashboard_puts_daily_work_before_technical_module_status(): void
    {
        $owner = $this->owner();
        $keys = array_column(app(WidgetRegistry::class)->visibleTo($owner), 'key');
        $this->assertLessThan(array_search('modules', $keys, true), array_search('sales.today', $keys, true));
    }

    public function test_product_price_changes_are_logged_with_the_actor_and_old_value(): void
    {
        $owner = $this->owner();
        $product = $this->product();
        Livewire::test(ProductsPage::class)->call('edit', $product->id)->set('form.price_toman', '150000')->call('save')->assertHasNoErrors();
        $activity = Activity::where('subject_type', Product::class)->where('event', 'updated')->firstOrFail();
        $this->assertSame($owner->id, $activity->causer_id);
        $this->assertSame(125000, $activity->properties['old']['price_toman']);
        $this->assertSame(150000, $activity->properties['attributes']['price_toman']);
    }

    public function test_public_catalog_only_shows_published_active_products_without_internal_data(): void
    {
        $this->owner();
        $product = $this->product();
        $this->get('/shop')->assertOk()->assertDontSee('دفتر آبی');
        $this->get('/shop/'.$product->id)->assertNotFound();
        $product->update(['is_published' => true]);
        $this->get('/shop')->assertOk()->assertSee('دفتر آبی')->assertDontSee('00003001');
        $this->get('/shop/'.$product->id)->assertOk()->assertSee('125,000')->assertDontSee('00003001');
        $product->update(['is_active' => false]);
        $this->get('/shop/'.$product->id)->assertNotFound();
        config(['vira.modules.Catalog.enabled' => false]);
        $this->get('/shop')->assertNotFound();
    }

    public function test_mutations_recheck_permissions_and_module_state_on_every_request(): void
    {
        $owner = $this->owner();
        $cases = [
            [CustomersPage::class, 'Customers', 'customers.view', 'save'],
            [ProductsPage::class, 'Catalog', 'catalog.view', 'save'],
            [InventoryPage::class, 'Inventory', 'inventory.view', 'saveMovement'],
            [SalesPage::class, 'Sales', 'sales.view', 'completeSale'],
            [PosPage::class, 'Pos', 'pos.view', 'completeSale'],
            [FollowUpsPage::class, 'Crm', 'crm.view', 'save'],
        ];
        foreach ($cases as [$component, $module, $viewPermission, $method]) {
            $reader = User::factory()->create();
            $reader->givePermissionTo(['panel.access', $viewPermission]);
            Livewire::actingAs($reader)->test($component)->call($method)->assertForbidden();
            $page = Livewire::actingAs($owner)->test($component);
            config(['vira.modules.'.$module.'.enabled' => false]);
            $page->call($method)->assertNotFound();
            config(['vira.modules.'.$module.'.enabled' => true]);
        }
    }

    public function test_price_change_requires_review_before_recording_sale(): void
    {
        $this->owner();
        [$product, $warehouse] = $this->stockedProduct();
        $page = Livewire::test(PosPage::class)->set('warehouseId', (string) $warehouse->id)->call('addItem', $product->id);
        $product->update(['price_toman' => 150000]);
        $page->call('completeSale')->assertHasErrors('items');
        $this->assertDatabaseCount('sales', 0);
        $page->call('refreshPrices')->call('completeSale')->assertHasNoErrors();
        $this->assertDatabaseHas('sales', ['total_toman' => 150000]);
    }

    public function test_invalid_quantity_and_duplicate_product_cannot_create_a_sale(): void
    {
        $this->owner();
        [$product, $warehouse] = $this->stockedProduct();
        foreach ([0, -1, '1.5', 1000001] as $quantity) {
            try {
                app(RecordSale::class)->handle((string) Str::uuid(), $warehouse->id, null, [['product_id' => $product->id, 'quantity' => $quantity]], 'direct');
                $this->fail('Invalid quantity must fail.');
            } catch (ValidationException) {
                $this->assertDatabaseCount('sales', 0);
            }
        }
        try {
            app(RecordSale::class)->handle((string) Str::uuid(), $warehouse->id, null, array_fill(0, 2, ['product_id' => $product->id, 'quantity' => 1]), 'direct');
            $this->fail('Duplicate items must fail.');
        } catch (ValidationException) {
            $this->assertDatabaseHas('stock_balances', ['quantity' => 5]);
        }
    }

    public function test_failed_second_item_rolls_back_first_item_and_payment_record(): void
    {
        $this->owner();
        [$first, $warehouse] = $this->stockedProduct();
        $second = Product::create(['name' => 'کالای دوم', 'sku' => 'SECOND', 'price_toman' => 2000, 'is_active' => true]);
        try {
            app(RecordSale::class)->handle((string) Str::uuid(), $warehouse->id, null, [['product_id' => $first->id, 'quantity' => 1], ['product_id' => $second->id, 'quantity' => 1]], 'direct');
            $this->fail('Second item is out of stock.');
        } catch (ValidationException) {
            $this->assertDatabaseCount('sales', 0);
            $this->assertDatabaseCount('sale_items', 0);
            $this->assertDatabaseCount('stock_movements', 1);
            $this->assertDatabaseHas('stock_balances', ['product_id' => $first->id, 'quantity' => 5]);
        }
    }

    public function test_request_token_cannot_be_reused_with_different_items(): void
    {
        $this->owner();
        [$product, $warehouse] = $this->stockedProduct();
        $token = (string) Str::uuid();
        app(RecordSale::class)->handle($token, $warehouse->id, null, [['product_id' => $product->id, 'quantity' => 1]], 'direct');
        try {
            app(RecordSale::class)->handle($token, $warehouse->id, null, [['product_id' => $product->id, 'quantity' => 2]], 'direct');
            $this->fail('Token payload cannot change.');
        } catch (ValidationException) {
            $this->assertDatabaseCount('sales', 1);
            $this->assertDatabaseHas('stock_balances', ['quantity' => 4]);
        }
    }

    public function test_cashier_without_sales_permission_only_sees_their_own_receipts(): void
    {
        $this->owner();
        [$product, $warehouse] = $this->stockedProduct();
        $sale = app(RecordSale::class)->handle((string) Str::uuid(), $warehouse->id, null, [['product_id' => $product->id, 'quantity' => 1]], 'direct');
        $cashier = User::factory()->create();
        $cashier->givePermissionTo(['panel.access', 'pos.view', 'pos.manage']);
        Livewire::actingAs($cashier)->test(PosPage::class)->assertViewHas('sales', fn ($sales) => $sales->total() === 0)->call('selectSale', $sale->id)->assertNotFound();
    }

    public function test_jalali_followup_date_is_stored_at_tehran_midnight_in_utc_and_invalid_dates_are_rejected(): void
    {
        $this->owner();
        $customer = Customer::create(['name' => 'مشتری', 'mobile' => '09121234567']);
        $page = Livewire::test(FollowUpsPage::class)->call('create')->set('form.customer_id', (string) $customer->id)->set('form.title', 'تماس')->set('form.due_date', '۱۴۰۵/۰۸/۲۰')->call('save')->assertHasNoErrors();
        $dueAt = FollowUp::firstOrFail()->due_at;
        $this->assertSame('00:00', $dueAt->copy()->timezone('Asia/Tehran')->format('H:i'));
        $this->assertSame('1405/08/20', Jalalian::fromCarbon($dueAt->timezone('Asia/Tehran'))->format('Y/m/d'));
        $page->call('create')->set('form.customer_id', (string) $customer->id)->set('form.title', 'تاریخ نامعتبر')->set('form.due_date', '1405/12/30')->call('save')->assertHasErrors('form.due_date');
        $this->assertDatabaseCount('follow_ups', 1);
    }
}
