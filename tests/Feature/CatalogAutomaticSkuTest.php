<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Catalog\Livewire\ProductsPage;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\ProductGroup;
use Tests\TestCase;

class CatalogAutomaticSkuTest extends TestCase
{
    use RefreshDatabase;

    private function loginOwner(): void
    {
        $this->seed();
        $owner = User::factory()->create();
        $owner->assignRole('owner');
        $this->actingAs($owner);
    }

    public function test_new_simple_products_get_sequential_meaningful_numeric_codes_per_category(): void
    {
        $this->loginOwner();

        foreach (['روشنایی', 'روشنایی', 'لوازم برقی'] as $index => $category) {
            Livewire::test(ProductsPage::class)->call('create')
                ->set('form.name', 'کالای آزمایشی '.($index + 1))
                ->set('form.category', $category)
                ->set('form.price_toman', '12000')
                ->call('save')->assertHasNoErrors();
        }

        $this->assertSame(['001000001', '001000002', '002000001'], Product::orderBy('id')->pluck('sku')->all());
        $this->assertDatabaseHas('catalog_sku_sequences', [
            'category' => 'روشنایی', 'prefix' => '001', 'next_number' => 3,
        ]);
        $this->assertDatabaseHas('catalog_sku_sequences', [
            'category' => 'لوازم برقی', 'prefix' => '002', 'next_number' => 2,
        ]);
    }

    public function test_new_product_code_cannot_be_overridden_by_manipulating_livewire_fields(): void
    {
        $this->loginOwner();

        Livewire::test(ProductsPage::class)->call('create')
            ->set('form.name', 'لامپ LED')
            ->set('form.category', 'روشنایی')
            ->set('form.price_toman', '24000')
            ->set('form.sku', '99999999')
            ->call('save')->assertHasNoErrors();

        $this->assertSame('001000001', Product::firstOrFail()->sku);
        $this->assertDatabaseMissing('products', ['sku' => '99999999']);
    }

    public function test_group_and_variants_are_generated_as_base_code_plus_numeric_position(): void
    {
        $this->loginOwner();

        Livewire::test(ProductsPage::class)
            ->call('create')->call('chooseKind', 'variants')
            ->set('form.name', 'فیوز')
            ->set('form.category', 'برق')
            ->set('form.price_toman', '180000')
            ->set('variantFeatures', [
                ['name' => 'آمپر', 'values' => '۱۶، ۲۵'],
                ['name' => 'پل', 'values' => 'تک، سه'],
            ])
            ->call('generateVariants')
            ->assertSet('variants.0.sku', '')
            ->set('variants.0.sku', '987654321')
            ->call('save')->assertHasNoErrors();

        $group = ProductGroup::firstOrFail();
        $this->assertSame('001000001', $group->sku);
        $this->assertSame(['00100000101', '00100000102', '00100000103', '00100000104'],
            $group->variants()->orderBy('id')->pluck('sku')->all());
        $this->assertDatabaseCount('stock_balances', 0);
    }

    public function test_allocator_skips_existing_product_and_variant_code_collisions_without_rewriting_old_codes(): void
    {
        $this->loginOwner();
        Product::create(['name' => 'قدیمی یک', 'sku' => '001000001', 'price_toman' => 1000]);
        Product::create(['name' => 'قدیمی دو', 'sku' => '00100000201', 'price_toman' => 1000]);

        Livewire::test(ProductsPage::class)->call('create')
            ->set('form.name', 'جدید')
            ->set('form.category', 'ابزار')
            ->set('form.price_toman', '12000')
            ->call('save')->assertHasNoErrors();

        $this->assertSame('001000002', Product::where('name', 'جدید')->firstOrFail()->sku);
        Livewire::test(ProductsPage::class)->call('create')->call('chooseKind', 'variants')
            ->set('form.name', 'محصول متنوع')
            ->set('form.category', 'ابزار')
            ->set('form.price_toman', '12000')
            ->set('variantFeatures', [['name' => 'مدل', 'values' => 'کوچک، بزرگ']])
            ->call('generateVariants')->call('save')->assertHasNoErrors();

        $this->assertSame('001000003', ProductGroup::firstOrFail()->sku);
        $this->assertDatabaseHas('products', ['sku' => '001000001', 'name' => 'قدیمی یک']);
        $this->assertDatabaseHas('products', ['sku' => '00100000201', 'name' => 'قدیمی دو']);
    }

    public function test_editing_category_does_not_change_code_or_historical_identity(): void
    {
        $this->loginOwner();
        Livewire::test(ProductsPage::class)->call('create')
            ->set('form.name', 'کالا')
            ->set('form.category', 'پوشاک')
            ->set('form.price_toman', '1000')
            ->call('save')->assertHasNoErrors();

        $product = Product::firstOrFail();
        Livewire::test(ProductsPage::class)->call('edit', $product->id)
            ->set('form.category', 'لوازم خانه')
            ->set('form.sku', '999999999')
            ->call('save')->assertHasNoErrors();

        $this->assertSame($product->sku, $product->fresh()->sku);
        $this->assertSame('لوازم خانه', $product->fresh()->category);
    }
}
