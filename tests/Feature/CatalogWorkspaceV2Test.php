<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Catalog\Actions\CatalogWorkspaceDirectory;
use Modules\Catalog\Livewire\ProductsPage;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\ProductGroup;
use Tests\TestCase;

class CatalogWorkspaceV2Test extends TestCase
{
    use RefreshDatabase;

    private function loginOwner(): void
    {
        $this->seed();
        $owner = User::factory()->create();
        $owner->assignRole('owner');
        $this->actingAs($owner);
    }

    public function test_new_skus_are_numeric_and_persian_digits_preserve_leading_zeroes(): void
    {
        $this->loginOwner();
        Livewire::test(ProductsPage::class)
            ->call('create')
            ->set('form.name', 'کلید مینیاتوری')
            ->set('form.sku', '۰۰۰۰۱۲۳۴')
            ->set('form.price_toman', '۲۵۰۰۰۰')
            ->set('form.brand', 'پارس')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('products', [
            'sku' => '00001234', 'name' => 'کلید مینیاتوری', 'price_toman' => 250000, 'brand' => 'پارس',
        ]);
        Livewire::test(ProductsPage::class)
            ->call('create')->set('form.name', 'کالای نامعتبر')
            ->set('form.sku', 'ITEM-22')->set('form.price_toman', '5000')
            ->call('save')->assertHasErrors('form.sku');
        $this->assertDatabaseCount('products', 1);
    }

    public function test_numeric_code_is_unique_and_a_group_code_cannot_be_reused(): void
    {
        $this->loginOwner();
        Product::create(['name' => 'اول', 'sku' => '00002000', 'price_toman' => 12000]);
        Livewire::test(ProductsPage::class)
            ->call('create')->set('form.name', 'دوم')->set('form.sku', '00002000')
            ->set('form.price_toman', '2000')->call('save')->assertHasErrors('form.sku');

        ProductGroup::create(['name' => 'گروه', 'sku' => '00002100']);
        Livewire::test(ProductsPage::class)
            ->call('create')->set('form.name', 'سوم')->set('form.sku', '00002100')
            ->set('form.price_toman', '2000')->call('save')->assertHasErrors('form.sku');
    }

    public function test_custom_attributes_generate_sellable_variants_without_creating_stock(): void
    {
        $this->loginOwner();
        Livewire::test(ProductsPage::class)
            ->call('create')->call('chooseKind', 'variants')
            ->set('form.name', 'فیوز صنعتی')
            ->set('form.sku', '000030')
            ->set('form.category', 'لوازم برقی')
            ->set('form.price_toman', '180000')
            ->set('attributes', [
                ['name' => 'آمپر', 'values' => '۱۶، ۲۵'],
                ['name' => 'پل', 'values' => 'تک، سه'],
            ])
            ->call('generateVariants')
            ->assertSet('variants.0.sku', '00003001')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseCount('product_groups', 1);
        $this->assertDatabaseCount('products', 4);
        $this->assertDatabaseCount('stock_balances', 0);
        $group = ProductGroup::firstOrFail();
        $this->assertSame('000030', $group->sku);
        $this->assertSame('آمپر', $group->attributes[0]['name']);
        $this->assertSame(['آمپر' => '۱۶', 'پل' => 'تک'], $group->variants()->orderBy('id')->firstOrFail()->options);
    }

    public function test_invalid_attribute_cross_product_is_rejected_before_saving(): void
    {
        $this->loginOwner();
        Livewire::test(ProductsPage::class)
            ->call('create')->call('chooseKind', 'variants')
            ->set('form.name', 'فیوز')
            ->set('form.sku', '000031')
            ->set('form.price_toman', '150000')
            ->set('attributes', [['name' => 'آمپر', 'values' => '۱۰، ۲۰'], ['name' => 'پل', 'values' => 'تک، سه']])
            ->call('generateVariants')
            ->set('variants.0.options.آمپر', '۴۰')
            ->call('save')->assertHasErrors('variants');

        $this->assertDatabaseCount('product_groups', 0);
    }

    public function test_directory_searches_a_group_by_any_variant_sku_and_filters_by_category(): void
    {
        $this->loginOwner();
        $group = ProductGroup::create(['name' => 'چراغ', 'sku' => '000040', 'category' => 'روشنایی']);
        $group->variants()->create(['name' => 'چراغ · سفید', 'sku' => '00004001', 'price_toman' => 10000, 'category' => 'روشنایی']);
        $group->variants()->create(['name' => 'چراغ · آبی', 'sku' => '00004002', 'price_toman' => 11000, 'category' => 'روشنایی']);
        Product::create(['name' => 'پیچ گوشتی', 'sku' => '00005000', 'price_toman' => 30000, 'category' => 'ابزار']);

        $directory = app(CatalogWorkspaceDirectory::class);
        $this->assertSame(1, $directory->query('۰۰۰۰۴۰۰۲')->count());
        $this->assertSame(1, $directory->query('', 'روشنایی')->count());
        $this->assertSame(2, $directory->statistics()['total']);
        Livewire::test(ProductsPage::class)->set('search', '۰۰۰۰۴۰۰۲')->assertSee('چراغ')->assertDontSee('پیچ گوشتی');
    }

    public function test_list_is_row_numbered_incremental_and_never_contains_a_pagination_widget(): void
    {
        $this->loginOwner();
        Product::create(['name' => 'کالای خیلی قدیمی', 'sku' => '00006000', 'price_toman' => 1000]);
        for ($i = 1; $i <= 30; $i++) {
            Product::create(['name' => 'محصول '.$i, 'sku' => str_pad((string) (6100 + $i), 8, '0', STR_PAD_LEFT), 'price_toman' => 1000]);
        }

        Livewire::test(ProductsPage::class)
            ->assertSee('ردیف')->assertSee('محصول ۳۰')->assertDontSee('کالای خیلی قدیمی')
            ->assertSee('نمایش کالاهای بیشتر')
            ->call('loadMore')->assertSee('کالای خیلی قدیمی')->assertDontSee('pagination');
    }

    public function test_unchanged_historical_alphanumeric_code_can_be_edited_without_renumbering_sales(): void
    {
        $this->loginOwner();
        $product = Product::create(['name' => 'کالای قدیمی', 'sku' => 'LEGACY-A', 'price_toman' => 1000]);
        Livewire::test(ProductsPage::class)
            ->call('edit', $product->id)->set('form.price_toman', '2000')->call('save')->assertHasNoErrors();
        $this->assertDatabaseHas('products', ['id' => $product->id, 'sku' => 'LEGACY-A', 'price_toman' => 2000]);
        Livewire::test(ProductsPage::class)
            ->call('edit', $product->id)->set('form.sku', 'OTHER-1')->call('save')->assertHasErrors('form.sku');
    }

    public function test_readonly_staff_cannot_mutate_or_expand_an_unavailable_module(): void
    {
        $this->loginOwner();
        $staff = User::factory()->create();
        $staff->givePermissionTo(['panel.access', 'catalog.view']);
        Livewire::actingAs($staff)->test(ProductsPage::class)->call('generateCode')->assertForbidden();
        $page = Livewire::test(ProductsPage::class);
        config(['vira.modules.Catalog.enabled' => false]);
        $page->call('loadMore')->assertNotFound();
    }
}
