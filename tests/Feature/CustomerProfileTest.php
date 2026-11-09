<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Modules\Catalog\Models\Product;
use Modules\Crm\Models\FollowUp;
use Modules\Customers\Livewire\CustomerProfilePage;
use Modules\Customers\Models\Customer;
use Modules\Inventory\Actions\StockLedger;
use Modules\Inventory\Models\Warehouse;
use Modules\Sales\Actions\RecordReturn;
use Modules\Sales\Actions\RecordSale;
use Tests\TestCase;

class CustomerProfileTest extends TestCase
{
    use RefreshDatabase;

    private function fixture(): array
    {
        $this->seed();
        $owner = User::factory()->create();
        $owner->assignRole('owner');
        $this->actingAs($owner);
        $customer = Customer::create(['name' => 'مشتری پرونده', 'mobile' => '09121234567', 'city' => 'تهران', 'note' => 'یادداشت هویت']);
        $other = Customer::create(['name' => 'مشتری دیگر', 'mobile' => '09121234568']);
        $product = Product::create(['name' => 'دفتر آزمون', 'sku' => 'PROFILE', 'price_toman' => 70001, 'is_active' => true]);
        $warehouse = Warehouse::create(['name' => 'اصلی']);
        app(StockLedger::class)->adjust($warehouse->id, $product->id, 20, 'اولیه');
        $sale = app(RecordSale::class)->handle((string) Str::uuid(), $warehouse->id, $customer->id, [['product_id' => $product->id, 'quantity' => 3]], 'direct');
        $otherSale = app(RecordSale::class)->handle((string) Str::uuid(), $warehouse->id, $other->id, [['product_id' => $product->id, 'quantity' => 1]], 'direct');
        app(RecordReturn::class)->handle((string) Str::uuid(), $sale->items()->first()->id, 1, 'انصراف مشتری پرونده', 'cash', true);
        $followUp = FollowUp::create(['customer_id' => $customer->id, 'user_id' => $owner->id, 'title' => 'پیگیری اختصاصی الف', 'note' => 'متن محرمانه پیگیری الف', 'due_at' => now()->subDays(2), 'status' => 'open']);
        $otherFollowUp = FollowUp::create(['customer_id' => $other->id, 'user_id' => $owner->id, 'title' => 'پیگیری اختصاصی ب', 'due_at' => now(), 'status' => 'open']);

        return compact('owner', 'customer', 'other', 'product', 'warehouse', 'sale', 'otherSale', 'followUp', 'otherFollowUp');
    }

    public function test_customer_list_links_to_a_scoped_profile_with_exact_lifetime_totals(): void
    {
        $f = $this->fixture();
        $this->get('/panel/customers')->assertSee(route('panel.customers.show', $f['customer']->id));
        $this->get('/panel/customers/'.$f['customer']->id)->assertOk()->assertSee('مشتری پرونده')->assertSee('یادداشت هویت')
            ->assertSee('210,003')->assertSee('70,001')->assertSee('140,002')->assertSee('پیگیری اختصاصی الف')->assertDontSee('پیگیری اختصاصی ب')->assertDontSee('مشتری دیگر');
    }

    public function test_receipt_preserves_historical_name_price_and_is_limited_to_this_customer(): void
    {
        $f = $this->fixture();
        $f['customer']->update(['name' => 'نام فعلی']);
        $f['product']->update(['name' => 'نام کالای جدید', 'price_toman' => 999]);
        $page = Livewire::test(CustomerProfilePage::class, ['customerId' => $f['customer']->id]);
        $page->call('selectReceipt', $f['sale']->id)->assertSee('دفتر آزمون')->assertSee('مشتری پرونده')->assertSee('70,001')->assertSee('انصراف مشتری پرونده');
        $page->call('selectReceipt', $f['otherSale']->id)->assertNotFound();
    }

    public function test_customer_only_viewer_does_not_query_or_receive_sales_and_crm_details(): void
    {
        $f = $this->fixture();
        $viewer = User::factory()->create();
        $viewer->givePermissionTo(['panel.access', 'customers.view']);
        $this->actingAs($viewer);
        DB::enableQueryLog();
        $this->get('/panel/customers/'.$f['customer']->id)->assertOk()->assertSee('مشتری پرونده')
            ->assertDontSee('210,003')->assertDontSee('پیگیری اختصاصی الف')->assertDontSee('متن محرمانه پیگیری الف');
        $sql = implode(' ', array_column(DB::getQueryLog(), 'query'));
        $this->assertStringNotContainsString('"sales"', $sql);
        $this->assertStringNotContainsString('"follow_ups"', $sql);
        $this->assertStringNotContainsString('"sale_returns"', $sql);
        DB::disableQueryLog();
        Livewire::test(CustomerProfilePage::class, ['customerId' => $f['customer']->id])->call('selectReceipt', $f['sale']->id)->assertForbidden();
    }

    public function test_disabled_crm_leaves_profile_and_sales_working_but_blocks_mutation(): void
    {
        $f = $this->fixture();
        $page = Livewire::test(CustomerProfilePage::class, ['customerId' => $f['customer']->id]);
        config(['vira.modules.Crm.enabled' => false]);
        $this->get('/panel/customers/'.$f['customer']->id)->assertOk()->assertSee('140,002')->assertDontSee('پیگیری اختصاصی الف');
        $page->call('saveFollowUp')->assertNotFound();
        $this->assertSame('open', $f['followUp']->fresh()->status);
    }

    public function test_disabled_sales_leaves_customer_and_crm_working(): void
    {
        $f = $this->fixture();
        config(['vira.modules.Sales.enabled' => false]);
        $this->get('/panel/customers/'.$f['customer']->id)->assertOk()->assertSee('پیگیری اختصاصی الف')->assertDontSee('210,003');
        Livewire::test(CustomerProfilePage::class, ['customerId' => $f['customer']->id])->call('selectReceipt', $f['sale']->id)->assertNotFound();
    }

    public function test_followup_create_normalizes_dates_and_ignores_forged_customer_field(): void
    {
        $f = $this->fixture();
        Livewire::test(CustomerProfilePage::class, ['customerId' => $f['customer']->id])
            ->set('followUpForm.title', 'تماس تازه')->set('followUpForm.note', 'جزئیات تماس')
            ->set('followUpForm.due_date', '۱۴۰۵/۰۸/۲۰')->set('followUpForm.customer_id', $f['other']->id)
            ->call('saveFollowUp')->assertHasNoErrors()->assertSee('تماس تازه');
        $this->assertDatabaseHas('follow_ups', ['customer_id' => $f['customer']->id, 'title' => 'تماس تازه', 'due_at' => '2026-11-10 20:30:00']);
        $this->assertDatabaseMissing('follow_ups', ['customer_id' => $f['other']->id, 'title' => 'تماس تازه']);
    }

    public function test_followup_completion_is_authorized_scoped_and_repeat_safe(): void
    {
        $f = $this->fixture();
        $page = Livewire::test(CustomerProfilePage::class, ['customerId' => $f['customer']->id]);
        $page->call('completeFollowUp', $f['followUp']->id)->assertHasNoErrors();
        $completed = $f['followUp']->fresh()->completed_at;
        $this->travel(1)->hours();
        $page->call('completeFollowUp', $f['followUp']->id)->assertHasNoErrors();
        $this->assertTrue($completed->equalTo($f['followUp']->fresh()->completed_at));
        $page->call('completeFollowUp', $f['otherFollowUp']->id)->assertNotFound();
        $this->assertSame('open', $f['otherFollowUp']->fresh()->status);
    }

    public function test_view_only_crm_and_revoked_customer_access_cannot_mutate(): void
    {
        $f = $this->fixture();
        $viewer = User::factory()->create();
        $viewer->givePermissionTo(['panel.access', 'customers.view', 'crm.view']);
        $this->actingAs($viewer);
        Livewire::test(CustomerProfilePage::class, ['customerId' => $f['customer']->id])->call('saveFollowUp')->assertForbidden();
        $page = Livewire::test(CustomerProfilePage::class, ['customerId' => $f['customer']->id]);
        $viewer->revokePermissionTo('customers.view');
        $page->call('completeFollowUp', $f['followUp']->id)->assertForbidden();
    }

    public function test_profile_requires_authentication_customer_permission_and_enabled_module(): void
    {
        $this->get('/panel/customers/1')->assertRedirect('/login');
        $f = $this->fixture();
        $this->get('/panel/customers/999999')->assertNotFound();
        $user = User::factory()->create();
        $user->givePermissionTo('panel.access');
        $this->actingAs($user);
        $this->get('/panel/customers/'.$f['customer']->id)->assertForbidden();
        $this->actingAs($f['owner']);
        config(['vira.modules.Customers.enabled' => false]);
        $this->get('/panel/customers/'.$f['customer']->id)->assertNotFound();
    }

    public function test_walkin_sales_are_never_counted_as_named_customer_purchases(): void
    {
        $f = $this->fixture();
        app(RecordSale::class)->handle((string) Str::uuid(), $f['warehouse']->id, null, [['product_id' => $f['product']->id, 'quantity' => 5]], 'pos');
        $this->get('/panel/customers/'.$f['customer']->id)->assertSee('210,003')->assertDontSee('350,005');
    }

    public function test_invalid_followup_date_does_not_write_and_is_shown_on_profile_form(): void
    {
        $f = $this->fixture();
        Livewire::test(CustomerProfilePage::class, ['customerId' => $f['customer']->id])
            ->set('followUpForm.title', 'پیگیری نامعتبر')->set('followUpForm.due_date', '1405/12/30')->call('saveFollowUp')->assertHasErrors('followUpForm.due_date');
        $this->assertDatabaseMissing('follow_ups', ['title' => 'پیگیری نامعتبر']);
    }

    public function test_lifetime_overflow_does_not_show_approximate_money_or_hide_history(): void
    {
        $f = $this->fixture();
        $base = $f['sale']->getAttributes();
        unset($base['id']);
        $rows = [];
        for ($index = 0; $index < 1025; $index++) {
            $rows[] = [...$base, 'request_token' => (string) Str::uuid(), 'total_toman' => 9000000000000000];
        }
        foreach (array_chunk($rows, 100) as $batch) {
            DB::table('sales')->insert($batch);
        }
        $this->get('/panel/customers/'.$f['customer']->id)->assertOk()->assertSee('مبلغ جمع کل از سقف محاسبه بیشتر است')->assertSee('خریدها و فاکتورها')->assertDontSee('خرید پس از مرجوعی');
    }
}
