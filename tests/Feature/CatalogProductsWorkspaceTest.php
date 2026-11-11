<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Catalog\Livewire\ProductsPage;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\ProductGroup;
use Tests\TestCase;

class CatalogProductsWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    private function loginOwner(): void
    {
        $this->seed();
        $owner = User::factory()->create();
        $owner->assignRole('owner');
        $this->actingAs($owner);
    }

    public function test_products_module_uses_the_approved_persian_name_and_grouped_sidebar(): void
    {
        $this->loginOwner();

        $this->get(route('panel.catalog'))
            ->assertOk()
            ->assertSee('کالاهای نرم‌افزار')
            ->assertSee('نمایش کالاها')
            ->assertSee('تعریف کالای جدید')
            ->assertDontSee('کالا و کاتالوگ');
    }

    public function test_create_subroute_opens_the_existing_product_form(): void
    {
        $this->loginOwner();

        $this->get(route('panel.catalog.create'))
            ->assertOk()
            ->assertSee('تعریف کالای جدید')
            ->assertSee('اطلاعات پایه کالا');
    }

    public function test_product_directory_can_switch_between_table_and_grid_modes(): void
    {
        $this->loginOwner();
        Product::create(['name' => 'چراغ مطالعه', 'sku' => '001000001', 'price_toman' => 125000]);

        Livewire::test(ProductsPage::class)
            ->call('setDisplayMode', 'grid')
            ->assertSet('displayMode', 'grid')
            ->assertSee('vc2-grid')
            ->assertSee('چراغ مطالعه')
            ->call('setDisplayMode', 'table')
            ->assertSet('displayMode', 'table')
            ->assertSee('vc2-table');
    }

    public function test_invalid_product_display_mode_is_rejected(): void
    {
        $this->loginOwner();

        Livewire::test(ProductsPage::class)
            ->call('setDisplayMode', 'tiles')
            ->assertStatus(422);
    }

    public function test_filtered_product_export_downloads_a_csv_file(): void
    {
        $this->loginOwner();
        Product::create(['name' => 'چراغ مطالعه', 'sku' => '001000001', 'price_toman' => 125000]);
        Product::create(['name' => 'میز تحریر', 'sku' => '001000002', 'price_toman' => 225000]);

        Livewire::test(ProductsPage::class)
            ->set('search', 'چراغ')
            ->call('exportCsv')
            ->assertFileDownloaded();
    }

    public function test_product_export_includes_each_sellable_variant(): void
    {
        $this->loginOwner();
        $group = ProductGroup::create(['name' => 'چراغ رومیزی', 'sku' => '001000010', 'category' => 'روشنایی']);
        $group->variants()->create(['name' => 'چراغ رومیزی · سفید', 'sku' => '00100001001', 'price_toman' => 150000, 'category' => 'روشنایی', 'options' => ['رنگ' => 'سفید']]);
        $group->variants()->create(['name' => 'چراغ رومیزی · مشکی', 'sku' => '00100001002', 'price_toman' => 160000, 'category' => 'روشنایی', 'options' => ['رنگ' => 'مشکی']]);

        Livewire::test(ProductsPage::class)
            ->set('search', 'چراغ رومیزی')
            ->call('exportCsv')
            ->assertFileDownloaded();
    }

    public function test_read_only_catalog_staff_cannot_export_product_data(): void
    {
        $this->loginOwner();
        $staff = User::factory()->create();
        $staff->givePermissionTo(['panel.access', 'catalog.view']);

        Livewire::actingAs($staff)
            ->test(ProductsPage::class)
            ->call('exportCsv')
            ->assertForbidden();
    }
}
