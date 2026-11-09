<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Modules\Catalog\Models\Product;
use Modules\Inventory\Actions\RecordStockDocument;
use Modules\Inventory\Actions\StockLedger;
use Modules\Inventory\Livewire\StockDocumentsPage;
use Modules\Inventory\Models\StockBalance;
use Modules\Inventory\Models\Supplier;
use Modules\Inventory\Models\Warehouse;
use Modules\Sales\Actions\RecordReturn;
use Modules\Sales\Actions\RecordSale;
use Modules\Sales\Livewire\SalesPage;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class StockDocumentsAndReturnsTest extends TestCase
{
    use RefreshDatabase;

    private function setupStock(): array
    {
        $this->seed();
        $user = User::factory()->create();
        $user->assignRole('owner');
        $this->actingAs($user);
        $product = Product::create(['name' => 'کالای آزمون', 'sku' => 'TEST', 'price_toman' => 125000, 'is_active' => true]);
        $source = Warehouse::create(['name' => 'اصلی']);
        $target = Warehouse::create(['name' => 'شعبه']);
        app(StockLedger::class)->adjust($source->id, $product->id, 5, 'اول دوره');

        return [$product, $source, $target];
    }

    public function test_purchase_snapshots_cost_supplier_and_increases_stock_only_once(): void
    {
        [$product, $source] = $this->setupStock();
        $supplier = Supplier::create(['name' => 'تأمین آزمایشی', 'phone' => '02112345678']);
        $token = (string) Str::uuid();
        $action = app(RecordStockDocument::class);
        $doc = $action->handle($token, 'purchase', $source->id, $product->id, 3, 'رسید ۱', null, $supplier->id, 70001);
        $supplier->update(['name' => 'نام تازه']);
        $this->assertSame($doc->id, $action->handle($token, 'purchase', $source->id, $product->id, 3, 'رسید ۱', null, $supplier->id, 70001)->id);
        $this->assertSame(210003, $doc->total_toman);
        $this->assertSame('تأمین آزمایشی', $doc->supplier_name);
        $this->assertDatabaseHas('stock_balances', ['warehouse_id' => $source->id, 'quantity' => 8]);
        $this->assertDatabaseCount('stock_documents', 1);
        $this->assertDatabaseHas('stock_movements', ['reference' => 'stock-document:'.$doc->id, 'delta' => 3]);
    }

    public function test_transfer_is_paired_atomic_and_replay_safe(): void
    {
        [$product, $source, $target] = $this->setupStock();
        $token = (string) Str::uuid();
        $action = app(RecordStockDocument::class);
        $doc = $action->handle($token, 'transfer', $source->id, $product->id, 2, 'ارسال شعبه', $target->id);
        $action->handle($token, 'transfer', $source->id, $product->id, 2, 'ارسال شعبه', $target->id);
        $this->assertDatabaseHas('stock_balances', ['warehouse_id' => $source->id, 'quantity' => 3]);
        $this->assertDatabaseHas('stock_balances', ['warehouse_id' => $target->id, 'quantity' => 2]);
        $this->assertDatabaseCount('stock_movements', 3);
        $this->assertDatabaseHas('stock_movements', ['reference' => 'stock-document:'.$doc->id, 'delta' => -2]);
        try {
            $action->handle((string) Str::uuid(), 'transfer', $source->id, $product->id, 4, 'بیش از موجودی', $target->id);
            $this->fail('Insufficient transfer accepted');
        } catch (ValidationException) {
            $this->assertDatabaseCount('stock_documents', 1);
            $this->assertDatabaseHas('stock_balances', ['warehouse_id' => $target->id, 'quantity' => 2]);
        }
    }

    public function test_transfer_destination_failure_rolls_back_source_and_document(): void
    {
        [$product, $source, $target] = $this->setupStock();
        StockBalance::create(['product_id' => $product->id, 'warehouse_id' => $target->id, 'quantity' => 1000000000]);
        try {
            app(RecordStockDocument::class)->handle((string) Str::uuid(), 'transfer', $source->id, $product->id, 1, 'انتقال', $target->id);
            $this->fail('Overflow accepted');
        } catch (ValidationException) {
            $this->assertDatabaseHas('stock_balances', ['warehouse_id' => $source->id, 'quantity' => 5]);
            $this->assertDatabaseCount('stock_documents', 0);
            $this->assertDatabaseCount('stock_movements', 1);
        }
    }

    public function test_same_warehouse_and_changed_token_payload_are_rejected(): void
    {
        [$product, $source, $target] = $this->setupStock();
        $action = app(RecordStockDocument::class);
        try {
            $action->handle((string) Str::uuid(), 'transfer', $source->id, $product->id, 1, 'انتقال', $source->id);
            $this->fail('Same warehouse accepted');
        } catch (ValidationException) {
            $this->assertDatabaseCount('stock_documents', 0);
        }
        $token = (string) Str::uuid();
        $action->handle($token, 'transfer', $source->id, $product->id, 1, 'انتقال', $target->id);
        $this->expectException(ValidationException::class);
        $action->handle($token, 'transfer', $source->id, $product->id, 2, 'انتقال', $target->id);
    }

    public function test_partial_returns_use_original_price_even_if_product_is_inactive_and_never_duplicate(): void
    {
        [$product, $source] = $this->setupStock();
        $sale = app(RecordSale::class)->handle((string) Str::uuid(), $source->id, null, [['product_id' => $product->id, 'quantity' => 3]], 'direct');
        $line = $sale->items()->firstOrFail();
        $product->update(['price_toman' => 999, 'is_active' => false]);
        $token = (string) Str::uuid();
        $action = app(RecordReturn::class);
        $return = $action->handle($token, $line->id, 2, 'انصراف خریدار', 'cash', true);
        $this->assertSame($return->id, $action->handle($token, $line->id, 2, 'انصراف خریدار', 'cash', true)->id);
        $this->assertSame(250000, $return->refund_toman);
        $this->assertSame(375000, $sale->fresh()->total_toman);
        $this->assertSame(2, $line->fresh()->returned_quantity);
        $this->assertDatabaseHas('stock_balances', ['warehouse_id' => $source->id, 'quantity' => 4]);
        try {
            $action->handle((string) Str::uuid(), $line->id, 2, 'اضافی', 'cash', true);
            $this->fail('Excess return accepted');
        } catch (ValidationException) {
            $this->assertDatabaseCount('sale_returns', 1);
        }
        $action->handle((string) Str::uuid(), $line->id, 1, 'باقیمانده', 'card', true);
        $this->assertSame(3, $line->fresh()->returned_quantity);
    }

    public function test_refund_requires_manual_confirmation(): void
    {
        [$product, $source] = $this->setupStock();
        $sale = app(RecordSale::class)->handle((string) Str::uuid(), $source->id, null, [['product_id' => $product->id, 'quantity' => 1]], 'direct');
        $this->expectException(ValidationException::class);
        app(RecordReturn::class)->handle((string) Str::uuid(), $sale->items()->first()->id, 1, 'دلیل', 'cash', false);
    }

    public function test_viewers_and_pos_only_cashiers_cannot_mutate_documents_or_returns(): void
    {
        [$product, $source] = $this->setupStock();
        $viewer = User::factory()->create();
        $viewer->givePermissionTo(['panel.access', 'inventory.view', 'sales.view', 'pos.view', 'pos.manage']);
        $this->actingAs($viewer);
        $this->get('/panel/inventory/documents')->assertOk();
        Livewire::test(StockDocumentsPage::class)->call('saveSupplier')->assertForbidden();
        Livewire::test(StockDocumentsPage::class)->call('saveDocument')->assertForbidden();
        Livewire::test(SalesPage::class)->call('saveReturn')->assertForbidden();
    }

    public function test_disabled_inventory_blocks_document_route_and_mutation(): void
    {
        $this->setupStock();
        $page = Livewire::test(StockDocumentsPage::class);
        config(['vira.modules.Inventory.enabled' => false]);
        $this->get('/panel/inventory/documents')->assertNotFound();
        $page->call('saveSupplier')->assertNotFound();
    }

    public function test_forms_accept_persian_digits_and_keep_committed_document_readonly(): void
    {
        [$product, $source] = $this->setupStock();
        $page = Livewire::test(StockDocumentsPage::class)->set('supplierName', 'تأمین آزمون')->set('supplierPhone', '۰۲۱۱۲۳۴۵۶۷۸')->call('saveSupplier')->assertHasNoErrors();
        $page->call('newDocument')->set('warehouseId', (string) $source->id)->set('productId', (string) $product->id)->set('quantity', '۲')->set('unitCost', '۷۰۰۰۱')->set('reason', 'رسید آزمون')->assertSee('140,002')->call('saveDocument')->assertHasErrors('deliveryConfirmed');
        $this->assertDatabaseCount('stock_documents', 0);
        $page->set('deliveryConfirmed', true)->call('saveDocument')->assertHasNoErrors()->assertSee('140,002');
        $page->call('saveDocument')->assertHasNoErrors();
        $this->assertDatabaseCount('stock_documents', 1);
        $this->assertDatabaseHas('suppliers', ['phone' => '02112345678']);
    }

    public function test_receipt_return_flow_accepts_persian_quantity_and_displays_audited_refund(): void
    {
        [$product, $source] = $this->setupStock();
        $sale = app(RecordSale::class)->handle((string) Str::uuid(), $source->id, null, [['product_id' => $product->id, 'quantity' => 2]], 'direct');
        Livewire::test(SalesPage::class)->call('selectSale', $sale->id)->call('beginReturn', $sale->items()->first()->id)
            ->set('returnQuantity', '۱')->set('returnReason', 'انصراف')->set('refundConfirmed', true)->call('saveReturn')
            ->assertHasNoErrors()->assertSee('125,000')->assertSee('مرجوعی #1')->call('saveReturn')->assertHasNoErrors();
        $this->assertDatabaseCount('sale_returns', 1);
    }

    public function test_disabled_dependencies_block_return_mutation_without_altering_sale(): void
    {
        [$product, $source] = $this->setupStock();
        $sale = app(RecordSale::class)->handle((string) Str::uuid(), $source->id, null, [['product_id' => $product->id, 'quantity' => 1]], 'direct');
        $page = Livewire::test(SalesPage::class)->call('selectSale', $sale->id)->call('beginReturn', $sale->items()->first()->id)->set('returnQuantity', '1');
        config(['vira.modules.Inventory.enabled' => false]);
        $page->call('saveReturn')->assertNotFound();
        $this->assertDatabaseCount('sale_returns', 0);
    }

    public function test_return_stock_overflow_rolls_back_refund_and_returned_counter(): void
    {
        [$product, $source] = $this->setupStock();
        $sale = app(RecordSale::class)->handle((string) Str::uuid(), $source->id, null, [['product_id' => $product->id, 'quantity' => 1]], 'direct');
        StockBalance::query()->update(['quantity' => 1000000000]);
        try {
            app(RecordReturn::class)->handle((string) Str::uuid(), $sale->items()->first()->id, 1, 'بازگشت', 'cash', true);
            $this->fail('Overflow return accepted');
        } catch (ValidationException) {
            $this->assertDatabaseCount('sale_returns', 0);
            $this->assertSame(0, $sale->items()->first()->returned_quantity);
        }
    }

    public function test_purchase_invalid_cost_and_total_do_not_change_stock(): void
    {
        [$product, $source] = $this->setupStock();
        $supplier = Supplier::create(['name' => 'آزمون']);
        foreach ([-1, 1000000000001] as $cost) {
            try {
                app(RecordStockDocument::class)->handle((string) Str::uuid(), 'purchase', $source->id, $product->id, 1, 'خرید', null, $supplier->id, $cost);
                $this->fail('Invalid cost accepted');
            } catch (ValidationException) {
                $this->assertDatabaseCount('stock_documents', 0);
            }
        }
        $this->assertDatabaseHas('stock_balances', ['warehouse_id' => $source->id, 'quantity' => 5]);
    }

    public function test_request_token_cannot_be_replayed_by_another_operator(): void
    {
        [$product, $source, $target] = $this->setupStock();
        $token = (string) Str::uuid();
        app(RecordStockDocument::class)->handle($token, 'transfer', $source->id, $product->id, 1, 'انتقال', $target->id);
        $another = User::factory()->create();
        $another->assignRole('owner');
        $this->actingAs($another);
        $this->expectException(HttpException::class);
        app(RecordStockDocument::class)->handle($token, 'transfer', $source->id, $product->id, 1, 'انتقال', $target->id);
    }
}
