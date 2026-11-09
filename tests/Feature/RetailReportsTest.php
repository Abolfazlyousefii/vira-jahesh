<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Modules\Catalog\Models\Product;
use Modules\Inventory\Actions\LowStockReport;
use Modules\Inventory\Actions\StockLedger;
use Modules\Inventory\Models\Warehouse;
use Modules\Sales\Actions\RecordReturn;
use Modules\Sales\Actions\RecordSale;
use Modules\Sales\Actions\SalesReport;
use Modules\Sales\Livewire\ReportsPage;
use Morilog\Jalali\Jalalian;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class RetailReportsTest extends TestCase
{
    use RefreshDatabase;

    private function fixture(): array
    {
        $this->seed();
        $owner = User::factory()->create();
        $owner->assignRole('owner');
        $this->actingAs($owner);
        $product = Product::create(['name' => 'دفتر آزمون', 'sku' => 'REPORT', 'price_toman' => 70001, 'is_active' => true]);
        $warehouse = Warehouse::create(['name' => '=Unsafe warehouse']);
        app(StockLedger::class)->adjust($warehouse->id, $product->id, 10, 'اولیه');
        $sale = app(RecordSale::class)->handle((string) Str::uuid(), $warehouse->id, null, [['product_id' => $product->id, 'quantity' => 3]], 'direct');
        $sale->forceFill(['created_at' => Carbon::parse('2026-11-08 20:29:59', 'UTC')])->save();
        $return = app(RecordReturn::class)->handle((string) Str::uuid(), $sale->items()->first()->id, 1, 'آزمون', 'cash', true);
        $return->forceFill(['created_at' => Carbon::parse('2026-11-08 20:30:00', 'UTC')])->save();

        return [$product, $warehouse, $sale];
    }

    private function filters(string $day = '2026-11-09'): array
    {
        $date = Jalalian::fromCarbon(Carbon::parse($day))->format('Y/m/d');

        return ['from' => $date, 'to' => $date, 'channel' => 'all', 'warehouse' => ''];
    }

    public function test_returns_from_older_sales_use_refund_date_and_tehran_midnight_boundaries(): void
    {
        $this->fixture();
        $today = app(SalesReport::class)->handle($this->filters());
        $this->assertSame(0, $today['totals']['gross']);
        $this->assertSame(70001, $today['totals']['refunds']);
        $this->assertSame(-70001, $today['totals']['net']);
        $this->assertSame(0, $today['totals']['sale_count']);
        $this->assertSame(1, $today['totals']['return_count']);
        $this->assertSame(-1, $today['products'][0]['net_quantity']);
        $yesterday = app(SalesReport::class)->handle($this->filters('2026-11-08'));
        $this->assertSame(210003, $yesterday['totals']['gross']);
        $this->assertSame(0, $yesterday['totals']['refunds']);
    }

    public function test_channel_warehouse_and_daily_product_totals_do_not_multiply_sales_lines(): void
    {
        [$product, $warehouse] = $this->fixture();
        $sale = app(RecordSale::class)->handle((string) Str::uuid(), $warehouse->id, null, [['product_id' => $product->id, 'quantity' => 2]], 'pos');
        $sale->forceFill(['created_at' => Carbon::parse('2026-11-09 08:00:00', 'UTC')])->save();
        $report = app(SalesReport::class)->handle([...$this->filters(), 'channel' => 'pos', 'warehouse' => (string) $warehouse->id]);
        $this->assertSame(140002, $report['totals']['net']);
        $this->assertSame(1, $report['totals']['sale_count']);
        $this->assertSame(2, $report['products'][0]['net_quantity']);
        $this->assertSame(140002, $report['daily'][0]['net']);
        $this->assertSame('11:30', $report['events'][0]['time']);
        $empty = app(SalesReport::class)->handle([...$this->filters(), 'warehouse' => (string) Warehouse::create(['name' => 'خالی'])->id]);
        $this->assertSame(0, $empty['totals']['net']);
        $this->assertCount(0, $empty['products']);
    }

    public function test_bad_jalali_dates_reversed_ranges_and_unknown_filters_are_rejected(): void
    {
        $this->fixture();
        foreach ([['from' => '1405/12/30'], ['to' => '1405/08/17'], ['from' => '1400/01/01'], ['channel' => 'unknown'], ['warehouse' => '999']] as $change) {
            try {
                app(SalesReport::class)->handle([...$this->filters(), ...$change]);
                $this->fail('Invalid report filters accepted');
            } catch (ValidationException) {
                $this->assertTrue(true);
            }
        }
        $report = app(SalesReport::class)->handle([...$this->filters(), 'from' => '۱۴۰۵/۰۸/۱۸', 'to' => '۱۴۰۵/۰۸/۱۸']);
        $this->assertSame(-70001, $report['totals']['net']);
    }

    public function test_report_and_csv_require_sales_view_and_enabled_dependencies(): void
    {
        $this->fixture();
        $cashier = User::factory()->create();
        $cashier->givePermissionTo(['panel.access', 'pos.view', 'pos.manage']);
        $this->actingAs($cashier);
        $this->get('/panel/reports')->assertForbidden();
        $this->get('/panel/reports/export?'.http_build_query($this->filters()))->assertForbidden();
        $cashier->givePermissionTo('sales.view');
        $this->get('/panel/reports')->assertOk();
        $page = Livewire::test(ReportsPage::class);
        config(['vira.modules.Inventory.enabled' => false]);
        $this->get('/panel/reports')->assertNotFound();
        $this->get('/panel/reports/export?'.http_build_query($this->filters()))->assertNotFound();
        $page->call('applyFilters')->assertNotFound();
    }

    public function test_csv_matches_filtered_events_and_neutralizes_spreadsheet_formulas(): void
    {
        $this->fixture();
        $response = $this->get('/panel/reports/export?'.http_build_query($this->filters()));
        $response->assertOk()->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $content = $response->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $content);
        $this->assertStringContainsString("'=Unsafe warehouse", $content);
        $this->assertStringContainsString('70001', $content);
        $this->assertStringNotContainsString('210003', $content);
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
    }

    public function test_low_stock_includes_active_products_without_balance_and_honors_permission(): void
    {
        [$product, $warehouse] = $this->fixture();
        Product::create(['name' => 'بدون موجودی', 'sku' => 'ZERO', 'price_toman' => 1, 'is_active' => true]);
        Product::create(['name' => 'غیرفعال', 'sku' => 'OFF', 'price_toman' => 1, 'is_active' => false]);
        $report = app(LowStockReport::class)->handle(0, $warehouse->id);
        $this->assertSame('بدون موجودی', $report['rows'][0]->name);
        $this->assertSame(0, (int) $report['rows'][0]->quantity);
        $this->assertCount(1, $report['rows']);
        $viewer = User::factory()->create();
        $viewer->givePermissionTo(['panel.access', 'sales.view']);
        $this->actingAs($viewer);
        $this->get('/panel/reports')->assertOk()->assertDontSee('هشدار موجودی');
        $this->expectException(HttpException::class);
        app(LowStockReport::class)->handle(5, null);
    }

    public function test_filter_form_keeps_last_applied_report_on_invalid_input(): void
    {
        $this->fixture();
        Livewire::test(ReportsPage::class)->set('from', '۱۴۰۵/۰۸/۱۸')->set('to', '۱۴۰۵/۰۸/۱۸')->call('applyFilters')->assertHasNoErrors()
            ->assertSee('-70,001')->set('from', '1405/13/01')->call('applyFilters')->assertHasErrors('from')->assertSee('-70,001');
    }

    public function test_guest_revoked_permission_and_invalid_export_filters_never_expose_report(): void
    {
        $this->get('/panel/reports')->assertRedirect('/login');
        $this->get('/panel/reports/export?'.http_build_query($this->filters()))->assertRedirect('/login');
        $this->fixture();
        $this->getJson('/panel/reports/export?'.http_build_query([...$this->filters(), 'to' => 'wrong']))->assertUnprocessable()->assertJsonValidationErrors('to');
        $operator = User::factory()->create();
        $operator->givePermissionTo(['panel.access', 'sales.view']);
        $this->actingAs($operator);
        $page = Livewire::test(ReportsPage::class);
        $operator->revokePermissionTo('sales.view');
        $page->call('applyFilters')->assertForbidden();
        $this->get('/panel/reports/export?'.http_build_query($this->filters()))->assertForbidden();
    }

    public function test_large_exact_totals_are_never_converted_to_float(): void
    {
        [, , $sale] = $this->fixture();
        $base = $sale->getAttributes();
        unset($base['id']);
        $rows = [];
        for ($index = 0; $index < 1025; $index++) {
            $rows[] = [...$base, 'request_token' => (string) Str::uuid(), 'request_hash' => str_repeat('a', 64), 'total_toman' => 9000000000000000, 'created_at' => '2026-11-09 08:00:00'];
        }
        foreach (array_chunk($rows, 100) as $batch) {
            DB::table('sales')->insert($batch);
        }
        $this->expectException(ValidationException::class);
        app(SalesReport::class)->handle($this->filters());
    }

    public function test_empty_days_are_zero_and_multiple_line_sales_are_counted_once(): void
    {
        [$product, $warehouse] = $this->fixture();
        $second = Product::create(['name' => 'مداد', 'sku' => 'PENCIL', 'price_toman' => 11003, 'is_active' => true]);
        app(StockLedger::class)->adjust($warehouse->id, $second->id, 2, 'ورود');
        $sale = app(RecordSale::class)->handle((string) Str::uuid(), $warehouse->id, null, [['product_id' => $product->id, 'quantity' => 2], ['product_id' => $second->id, 'quantity' => 1]], 'pos');
        $sale->forceFill(['created_at' => '2026-11-09 12:00:00'])->save();
        $report = app(SalesReport::class)->handle([...$this->filters(), 'to' => '1405/08/19', 'channel' => 'pos']);
        $this->assertSame(151005, $report['totals']['gross']);
        $this->assertSame(1, $report['totals']['sale_count']);
        $this->assertCount(2, $report['products']);
        $this->assertSame(0, collect($report['daily'])->firstWhere('date', '1405/08/19')['net']);
    }

    public function test_quick_period_buttons_apply_dates_and_keep_channel_filter(): void
    {
        $this->fixture();
        $this->travelTo(Carbon::parse('2026-11-09 12:00:00', 'UTC'));
        $page = Livewire::test(ReportsPage::class)->set('channel', 'pos')->call('quickRange', 7)->assertHasNoErrors()
            ->assertSet('applied.from', '1405/08/12')->assertSet('applied.to', '1405/08/18')->assertSet('applied.channel', 'pos');
        $page->call('quickRange', 1)->assertSet('applied.from', '1405/08/18');
        $page->call('quickRange', 999)->assertStatus(422);
    }

    public function test_range_limit_uses_tehran_calendar_days_across_historical_dst_changes(): void
    {
        $this->fixture();
        $report = app(SalesReport::class)->handle(['from' => '1401/01/03', 'to' => '1402/01/03', 'channel' => 'all', 'warehouse' => '']);
        $this->assertCount(366, $report['daily']);
    }

    public function test_short_historical_dst_day_excludes_next_day_first_hour(): void
    {
        [$product, $warehouse, $sale] = $this->fixture();
        $sale->forceFill(['created_at' => '2022-03-22 19:15:00'])->save();
        $nextDaySale = app(RecordSale::class)->handle((string) Str::uuid(), $warehouse->id, null, [['product_id' => $product->id, 'quantity' => 1]], 'direct');
        $nextDaySale->forceFill(['created_at' => '2022-03-22 19:45:00'])->save();
        $report = app(SalesReport::class)->handle(['from' => '1401/01/02', 'to' => '1401/01/02', 'channel' => 'all', 'warehouse' => '']);
        $this->assertSame(210003, $report['totals']['gross']);
        $this->assertSame(1, $report['totals']['sale_count']);
    }
}
