<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Modules\Catalog\Livewire\ProductsPage;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\ProductGroup;
use Modules\Inventory\Actions\StockLedger;
use Modules\Inventory\Models\Warehouse;
use Modules\Sales\Actions\RecordSale;
use Tests\TestCase;

class CatalogUpgradeTest extends TestCase
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

    private function variants(): array
    {
        return [
            ['id' => null, 'color' => 'آبی', 'size' => 'M', 'sku' => 'SHIRT-B-M', 'barcode' => '', 'price_toman' => '۲۵۰۰۰۰', 'is_active' => true, 'is_published' => true],
            ['id' => null, 'color' => 'آبی', 'size' => 'L', 'sku' => 'SHIRT-B-L', 'barcode' => '', 'price_toman' => '۲۷۰۰۰۰', 'is_active' => true, 'is_published' => false],
        ];
    }

    private function createGroup()
    {
        return Livewire::test(ProductsPage::class)->call('create')->call('chooseKind', 'variants')->set('form.name', 'پیراهن')->set('form.category', 'پوشاک')->set('variants', $this->variants())->call('save')->assertHasNoErrors();
    }

    public function test_group_creates_sellable_variants_with_independent_prices_and_no_fake_parent_stock(): void
    {
        $this->owner();
        $this->createGroup();
        $this->assertDatabaseCount('product_groups', 1);
        $this->assertDatabaseCount('products', 2);
        $this->assertDatabaseHas('products', ['group_id' => ProductGroup::firstOrFail()->id, 'color' => 'آبی', 'size' => 'M', 'price_toman' => 250000, 'name' => 'پیراهن · آبی / M']);
        $this->assertDatabaseCount('stock_balances', 0);
    }

    public function test_repeated_options_and_duplicate_skus_are_rejected_atomically(): void
    {
        $this->owner();
        $variants = $this->variants();
        $variants[1]['size'] = ' M ';
        Livewire::test(ProductsPage::class)->call('create')->call('chooseKind', 'variants')->set('form.name', 'پیراهن')->set('variants', $variants)->call('save')->assertHasErrors('variants');
        $this->assertDatabaseCount('product_groups', 0);
        $variants = $this->variants();
        $variants[1]['sku'] = 'shirt-b-m';
        Livewire::test(ProductsPage::class)->call('create')->call('chooseKind', 'variants')->set('form.name', 'پیراهن')->set('variants', $variants)->call('save')->assertHasErrors();
        $this->assertDatabaseCount('products', 0);
    }

    public function test_editing_group_preserves_variant_ids_stock_and_historical_invoice(): void
    {
        $this->owner();
        $this->createGroup();
        $product = Product::where('sku', 'SHIRT-B-M')->firstOrFail();
        $warehouse = Warehouse::create(['name' => 'اصلی']);
        app(StockLedger::class)->adjust($warehouse->id, $product->id, 3, 'اولیه');
        $sale = app(RecordSale::class)->handle((string) Str::uuid(), $warehouse->id, null, [['product_id' => $product->id, 'quantity' => 1]], 'direct');
        Livewire::test(ProductsPage::class)->call('edit', $product->id)->set('form.name', 'پیراهن نخی')->set('variants.0.price_toman', '300000')->call('save')->assertHasNoErrors();
        $this->assertSame($product->id, Product::where('sku', 'SHIRT-B-M')->firstOrFail()->id);
        $this->assertDatabaseHas('stock_balances', ['product_id' => $product->id, 'quantity' => 2]);
        $this->assertSame('پیراهن · آبی / M', $sale->items->first()->name);
        $this->assertSame(250000, $sale->items->first()->unit_price_toman);
    }

    public function test_variant_ids_cannot_be_borrowed_from_another_product_and_options_are_immutable(): void
    {
        $this->owner();
        $this->createGroup();
        $product = Product::firstOrFail();
        $other = Product::create(['name' => 'مستقل', 'sku' => 'OTHER', 'price_toman' => 1000]);
        Livewire::test(ProductsPage::class)->call('edit', $product->id)->set('variants.0.id', $other->id)->call('save')->assertHasErrors('variants');
        Livewire::test(ProductsPage::class)->call('edit', $product->id)->set('variants.0.color', 'قرمز')->call('save')->assertHasErrors('variants');
        $this->assertSame('مستقل', $other->fresh()->name);
        $this->assertSame('آبی', $product->fresh()->color);
    }

    public function test_existing_variant_cannot_disappear_when_a_form_row_is_removed(): void
    {
        $this->owner();
        $this->createGroup();
        $product = Product::firstOrFail();
        Livewire::test(ProductsPage::class)->call('edit', $product->id)->call('removeVariant', 0)->assertHasErrors('variants');
        $this->assertDatabaseCount('products', 2);
    }

    public function test_duplicate_variant_barcodes_are_rejected_before_any_record_is_saved(): void
    {
        $this->owner();
        $variants = $this->variants();
        $variants[0]['barcode'] = '12345678';
        $variants[1]['barcode'] = '۱۲۳۴۵۶۷۸';
        Livewire::test(ProductsPage::class)->call('create')->call('chooseKind', 'variants')->set('form.name', 'پیراهن')->set('variants', $variants)->call('save')->assertHasErrors();
        $this->assertDatabaseCount('products', 0);
        $this->assertDatabaseCount('product_groups', 0);
    }

    public function test_owner_can_upload_replace_and_remove_a_product_image(): void
    {
        Storage::fake('public');
        $this->owner();
        $page = Livewire::test(ProductsPage::class)->call('create')->set('form.name', 'کیف')->set('form.sku', 'BAG')->set('form.price_toman', '1000')->set('form.is_published', true)->set('productImage', UploadedFile::fake()->image('bag.png'))->call('save')->assertHasNoErrors();
        $product = Product::firstOrFail();
        $this->assertCount(1, $product->getMedia('product-image'));
        $firstPath = $product->getFirstMedia('product-image')->getPathRelativeToRoot();
        $this->get('/shop/'.$product->id)->assertOk()->assertSee('storage/', false);
        $page->call('edit', $product->id)->set('productImage', UploadedFile::fake()->image('new.png'))->call('save')->assertHasNoErrors();
        $this->assertCount(1, $product->fresh()->getMedia('product-image'));
        Storage::disk('public')->assertMissing($firstPath);
        $page->call('edit', $product->id)->call('removeImage')->assertHasNoErrors();
        $this->assertCount(0, $product->fresh()->getMedia('product-image'));
    }

    public function test_group_photo_is_shared_and_only_published_active_siblings_are_public(): void
    {
        Storage::fake('public');
        $this->owner();
        Livewire::test(ProductsPage::class)->call('create')->call('chooseKind', 'variants')->set('form.name', 'پیراهن')->set('variants', $this->variants())->set('productImage', UploadedFile::fake()->image('shirt.jpg'))->call('save')->assertHasNoErrors();
        $products = Product::orderBy('id')->get();
        $this->assertNotSame('', $products[0]->imageUrl());
        $this->assertSame($products[0]->imageUrl(), $products[1]->imageUrl());
        $this->get('/shop/'.$products[0]->id)->assertOk()->assertDontSee('SHIRT-B-L')->assertDontSee('آبی / L');
        $products[1]->update(['is_published' => true]);
        $this->get('/shop/'.$products[0]->id)->assertOk()->assertSee('آبی / L');
        $products[1]->update(['is_active' => false]);
        $this->get('/shop/'.$products[1]->id)->assertNotFound();
    }

    public function test_svg_upload_is_rejected_and_unauthorized_or_disabled_module_cannot_mutate_catalog(): void
    {
        Storage::fake('public');
        $owner = $this->owner();
        Livewire::test(ProductsPage::class)->call('create')->set('form.name', 'کالا')->set('form.sku', 'TEST')->set('form.price_toman', '1000')->set('productImage', UploadedFile::fake()->create('bad.svg', 1, 'image/svg+xml'))->call('save')->assertHasErrors('productImage');
        $reader = User::factory()->create();
        $reader->givePermissionTo(['panel.access', 'catalog.view']);
        Livewire::actingAs($reader)->test(ProductsPage::class)->call('chooseKind', 'variants')->assertForbidden();
        Livewire::actingAs($reader)->test(ProductsPage::class)->call('removeImage')->assertForbidden();
        $page = Livewire::actingAs($owner)->test(ProductsPage::class);
        config(['vira.modules.Catalog.enabled' => false]);
        $page->call('addVariant')->assertNotFound();
        $this->assertDatabaseCount('products', 0);
    }
}
