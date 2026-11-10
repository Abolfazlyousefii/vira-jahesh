<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Database\Seeders\DemoRetailSeeder;
use Livewire\Livewire;
use Modules\Catalog\Models\Product;
use Modules\Customers\Actions\CustomerDirectory;
use Modules\Customers\Livewire\CustomerProfilePage;
use Modules\Customers\Livewire\CustomersPage;
use Modules\Customers\Models\Customer;
use Modules\Customers\Models\CustomerPhoneNumber;
use Modules\Inventory\Actions\StockLedger;
use Modules\Inventory\Models\Warehouse;
use Modules\Sales\Actions\CustomerYearlyActivity;
use Modules\Sales\Actions\RecordReturn;
use Modules\Sales\Actions\RecordSale;
use Tests\TestCase;

class Customer360Test extends TestCase
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

    public function test_secondary_phone_search_accepts_persian_digits_and_does_not_duplicate_customers(): void
    {
        $this->owner();
        $customer = Customer::create(['name' => 'شخص نمونه', 'mobile' => '09121234567']);
        $customer->phoneNumbers()->createMany([
            ['type' => 'mobile', 'number_normalized' => '09990001001'],
            ['type' => 'work', 'number_normalized' => '02112345678'],
        ]);
        Customer::create(['name' => 'دیگری', 'mobile' => '09121234568']);

        $results = app(CustomerDirectory::class)->search('۰۹۹۹۰۰۰۱۰۰۱')->get();
        $this->assertCount(1, $results);
        $this->assertSame($customer->id, $results->first()->id);
        Livewire::test(CustomersPage::class)->set('search', '۰۲۱۱۲۳۴۵۶۷۸')->assertSee('شخص نمونه')->assertDontSee('دیگری');
    }

    public function test_owner_can_add_edit_remove_secondary_phone_but_cannot_add_primary_duplicate(): void
    {
        $this->owner();
        $customer = Customer::create(['name' => 'آزمایشی', 'mobile' => '09121234567']);
        $page = Livewire::test(CustomerProfilePage::class, ['customerId' => $customer->id]);
        $page->set('phoneForm.type', 'work')->set('phoneForm.number', '۰۲۱۱۲۳۴۵۶۷۸')->set('phoneForm.label', 'دفتر')
            ->call('savePhone')->assertHasNoErrors();
        $phone = CustomerPhoneNumber::firstOrFail();
        $this->assertSame('02112345678', $phone->number_normalized);
        $page->call('editPhone', $phone->id)->set('phoneForm.label', 'محل کار')->call('savePhone')->assertHasNoErrors();
        $this->assertDatabaseHas('customer_phone_numbers', ['id' => $phone->id, 'label' => 'محل کار']);
        $page->set('phoneForm.number', '09121234567')->call('savePhone')->assertHasErrors('phoneForm.number');
        $page->call('removePhone', $phone->id)->assertHasNoErrors();
        $this->assertDatabaseMissing('customer_phone_numbers', ['id' => $phone->id]);
    }

    public function test_view_only_user_cannot_change_phones_or_other_customers_records(): void
    {
        $owner = $this->owner();
        $one = Customer::create(['name' => 'اولی', 'mobile' => '09121234567']);
        $two = Customer::create(['name' => 'دومی', 'mobile' => '09121234568']);
        $phone = $two->phoneNumbers()->create(['type' => 'mobile', 'number_normalized' => '09990001002']);
        Livewire::test(CustomerProfilePage::class, ['customerId' => $one->id])->call('removePhone', $phone->id)->assertNotFound();
        $viewer = User::factory()->create();
        $viewer->givePermissionTo(['panel.access', 'customers.view']);
        $this->actingAs($viewer);
        Livewire::test(CustomerProfilePage::class, ['customerId' => $one->id])->call('savePhone')->assertForbidden();
        $this->assertDatabaseHas('customer_phone_numbers', ['id' => $phone->id]);
    }

    public function test_yearly_purchase_report_counts_returns_in_the_year_of_refund_not_the_sale(): void
    {
        $this->owner();
        $customer = Customer::create(['name' => 'خریدار', 'mobile' => '09121234567']);
        $product = Product::create(['name' => 'قلم', 'sku' => 'C360-TEST', 'price_toman' => 100000, 'is_active' => true]);
        $warehouse = Warehouse::create(['name' => 'انبار']);
        app(StockLedger::class)->adjust($warehouse->id, $product->id, 10, 'ابتدای کار');
        $sale = app(RecordSale::class)->handle((string) Str::uuid(), $warehouse->id, $customer->id, [['product_id' => $product->id, 'quantity' => 2]], 'direct');
        $sale->update(['created_at' => '2026-03-19 12:00:00']); // 1404/12
        $return = app(RecordReturn::class)->handle((string) Str::uuid(), $sale->items()->first()->id, 1, 'آزمایش', 'cash', true);
        $return->update(['created_at' => '2026-03-23 12:00:00']); // 1405/01
        $report1404 = app(CustomerYearlyActivity::class)->handle($customer->id, 1404);
        $report1405 = app(CustomerYearlyActivity::class)->handle($customer->id, 1405);
        $this->assertSame(200000, $report1404['gross']);
        $this->assertSame(0, $report1404['refunds']);
        $this->assertSame(0, $report1405['gross']);
        $this->assertSame(100000, $report1405['refunds']);
        $this->assertSame(-100000, $report1405['net']);
        $this->assertCount(12, $report1405['months']);
    }

    public function test_panel_assigns_a_semantic_accent_to_customer_area(): void
    {
        $this->owner();
        $this->get('/panel/customers')->assertOk()->assertSee('data-vira-section="customers"', false);
        $this->get('/panel/crm')->assertOk()->assertSee('data-vira-section="crm"', false);
    }

    public function test_demo_seeder_refuses_the_normal_test_database(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('DemoRetailSeeder');
        (new DemoRetailSeeder)->run();
    }

    public function test_customer_360_keeps_sales_and_crm_private_when_the_modules_are_disabled(): void
    {
        $this->owner();
        $customer = Customer::create(['name' => 'پرونده عمومی', 'mobile' => '09121234567']);
        config(['vira.modules.Sales.enabled' => false, 'vira.modules.Crm.enabled' => false]);
        DB::enableQueryLog();
        $this->get('/panel/customers/'.$customer->id)->assertOk()->assertSee('پرونده عمومی');
        $sql = implode(' ', array_column(DB::getQueryLog(), 'query'));
        $this->assertStringNotContainsString('"sales"', $sql);
        $this->assertStringNotContainsString('"follow_ups"', $sql);
        DB::disableQueryLog();
    }
}
