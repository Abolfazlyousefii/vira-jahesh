<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Livewire\Livewire;
use Modules\Catalog\Actions\CatalogLegacyCodeRepair;
use Modules\Catalog\Actions\CatalogWorkspaceDirectory;
use Modules\Catalog\Actions\InternalBarcode;
use Modules\Catalog\Livewire\ProductsPage;
use Modules\Catalog\Models\CatalogCodeAlias;
use Modules\Catalog\Models\Product;
use Modules\Inventory\Actions\StockLedger;
use Modules\Inventory\Models\Warehouse;
use Modules\Sales\Actions\RecordSale;
use Tests\TestCase;

class CatalogLegacyCodeRepairTest extends TestCase
{
    use RefreshDatabase;

    private function demo(): Product
    {
        return Product::create([
            'name' => 'چراغ مطالعه آوا', 'sku' => 'DEMO-P-101',
            'category' => 'کالای نمایشی', 'price_toman' => 980000,
            'is_published' => false,
        ]);
    }

    public function test_preview_does_not_modify_legacy_codes_or_barcodes(): void
    {
        $demo = $this->demo();
        $preview = app(CatalogLegacyCodeRepair::class)->preview();
        $this->assertSame(1, $preview['recode_count']);
        $this->assertSame(1, $preview['barcode_count']);
        $this->assertSame('DEMO-P-101', $demo->fresh()->sku);
        $this->assertNull($demo->fresh()->barcode);
        $this->assertDatabaseCount('catalog_code_aliases', 0);
        $this->assertDatabaseCount('catalog_sku_sequences', 0);
    }

    public function test_recode_demo_and_fill_barcodes_without_changing_other_legacy_codes(): void
    {
        $demo = $this->demo();
        $other = Product::create([
            'name' => 'محصول دیگر', 'sku' => 'LEGACY-A',
            'category' => 'ابزار', 'price_toman' => 1000,
        ]);
        $numeric = Product::create([
            'name' => 'محصول عددی', 'sku' => '012345678910',
            'category' => 'پوشاک', 'price_toman' => 1000, 'barcode' => '0000000000000',
        ]);
        $repair = app(CatalogLegacyCodeRepair::class);
        $result = $repair->apply();
        $this->assertSame(['recoded' => 1, 'barcoded' => 2], $result);
        $this->assertSame('001000001', $demo->fresh()->sku);
        $this->assertSame('LEGACY-A', $other->fresh()->sku);
        $this->assertSame('012345678910', $numeric->fresh()->sku);
        $this->assertSame('0000000000000', $numeric->fresh()->barcode);
        $this->assertDatabaseHas('catalog_code_aliases', [
            'old_sku' => 'DEMO-P-101', 'product_id' => $demo->id,
        ]);
        $this->assertSame($demo->id, CatalogCodeAlias::findProductByLegacySku('DEMO-P-101')?->id);
        $this->assertSame(['recoded' => 0, 'barcoded' => 0], $repair->apply());
        $this->assertSame('001000001', $demo->fresh()->sku);
    }

    public function test_barcode_has_valid_ean13_checksum_and_is_unique_per_product(): void
    {
        $first = $this->demo();
        $second = Product::create(['name' => 'محصول', 'sku' => 'ANOTHER', 'price_toman' => 10000]);
        app(CatalogLegacyCodeRepair::class)->apply();

        foreach ([$first, $second] as $product) {
            $barcode = $product->fresh()->barcode;
            $this->assertMatchesRegularExpression('/^20[0-9]{11}$/', $barcode);
            $this->assertSame($barcode, app(InternalBarcode::class)->forProductId($product->id));
        }
        $this->assertNotSame($first->fresh()->barcode, $second->fresh()->barcode);
    }

    public function test_legacy_alias_is_searchable_after_recode(): void
    {
        $demo = $this->demo();
        app(CatalogLegacyCodeRepair::class)->apply();
        $this->assertSame($demo->id, app(CatalogWorkspaceDirectory::class)->query('DEMO-P-101')->firstOrFail()->id);
    }

    public function test_invoice_snapshot_stock_and_product_id_survive_recode(): void
    {
        $this->seed();
        $owner = User::factory()->create();
        $owner->assignRole('owner');
        $this->actingAs($owner);
        $demo = $this->demo();
        $warehouse = Warehouse::create(['name' => 'اصلی']);
        app(StockLedger::class)->adjust($warehouse->id, $demo->id, 5, 'موجودی اولیه');
        $sale = app(RecordSale::class)->handle(
            (string) \Illuminate\Support\Str::uuid(), $warehouse->id, null,
            [['product_id' => $demo->id, 'quantity' => 1]], 'direct'
        );
        $saleItem = $sale->items()->firstOrFail();
        app(CatalogLegacyCodeRepair::class)->apply();
        $this->assertSame('DEMO-P-101', $saleItem->fresh()->sku);
        $this->assertSame($demo->id, $saleItem->fresh()->product_id);
        $this->assertDatabaseHas('stock_balances', ['warehouse_id' => $warehouse->id, 'product_id' => $demo->id, 'quantity' => 4]);
    }

    public function test_new_product_receives_internal_barcode_without_entering_one(): void
    {
        $this->seed();
        $owner = User::factory()->create();
        $owner->assignRole('owner');
        $this->actingAs($owner);
        Livewire::test(ProductsPage::class)
            ->call('create')->set('form.name', 'لامپ')
            ->set('form.category', 'روشنایی')->set('form.price_toman', '1000')
            ->call('save')->assertHasNoErrors();
        $product = Product::firstOrFail();
        $this->assertSame(app(InternalBarcode::class)->forProductId($product->id), $product->barcode);
    }

    public function test_apply_cli_is_blocked_in_testing_database(): void
    {
        $this->demo();
        $this->assertSame(1, Artisan::call('vira:catalog:repair-codes', ['--apply' => true]));
        $this->assertSame('DEMO-P-101', Product::firstOrFail()->sku);
    }

    public function test_unrelated_demo_prefix_and_published_product_are_not_automatically_recoded(): void
    {
        Product::create(['name' => 'محصول شخصی', 'sku' => 'DEMO-P-101', 'category' => 'ابزار', 'price_toman' => 1000]);
        Product::create(['name' => 'چراغ مطالعه آوا', 'sku' => 'DEMO-P-102', 'category' => 'کالای نمایشی', 'price_toman' => 1000, 'is_published' => true]);
        $this->assertSame(0, app(CatalogLegacyCodeRepair::class)->preview()['recode_count']);
    }
}
