<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Modules\Catalog\Livewire\ProductsPage;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\ProductGroup;
use Tests\TestCase;

class CatalogImageDeliveryTest extends TestCase
{
    use RefreshDatabase;

    private function owner(): void
    {
        Storage::fake('public');
        $this->seed();
        $owner = User::factory()->create();
        $owner->assignRole('owner');
        $this->actingAs($owner);
    }

    private function upload(UploadedFile $photo): Product
    {
        Livewire::test(ProductsPage::class)
            ->call('create')
            ->set('form.name', 'کالای تصویردار')
            ->set('form.category', 'کالای نمایشی')
            ->set('form.price_toman', '50000')
            ->set('productImage', $photo)
            ->call('save')
            ->assertHasNoErrors();

        return Product::firstOrFail();
    }

    public function test_catalog_images_use_same_origin_even_if_app_url_points_to_a_different_port(): void
    {
        $this->owner();
        config([
            'app.url' => 'http://127.0.0.1:8017',
            'filesystems.disks.public.url' => 'http://127.0.0.1:8017/storage',
        ]);

        $product = $this->upload(UploadedFile::fake()->image('photo.png'));
        $path = $product->getFirstMedia('product-image')->getPathRelativeToRoot();

        $this->assertSame('/storage/'.$path, $product->fresh()->imageUrl());
        $this->assertTrue(Storage::disk('public')->exists($path));
        $this->get('/panel/catalog')->assertOk()->assertSee('src="/storage/', false)
            ->assertDontSee('127.0.0.1:8017/storage', false);

        Livewire::test(ProductsPage::class)
            ->call('edit', $product->id)
            ->assertSee('src="/storage/', false);
    }

    public function test_existing_missing_media_file_falls_back_to_product_icon(): void
    {
        $this->owner();
        $product = $this->upload(UploadedFile::fake()->image('present.jpg'));
        $path = $product->getFirstMedia('product-image')->getPathRelativeToRoot();
        Storage::disk('public')->delete($path);

        $this->assertSame('', $product->fresh()->imageUrl());
        $this->get('/panel/catalog')->assertOk()->assertSee('data-lucide="package"', false);
    }

    public function test_jpeg_png_gif_and_webp_collections_share_the_five_megabyte_policy(): void
    {
        $this->owner();
        $product = $this->upload(UploadedFile::fake()->image('large.jpg')->size(4800));
        $this->assertNotSame('', $product->imageUrl());
        $this->assertSame('image/jpeg', $product->getFirstMedia('product-image')->mime_type);

        Livewire::test(ProductsPage::class)
            ->call('edit', $product->id)
            ->set('productImage', UploadedFile::fake()->image('animated.gif'))
            ->call('save')->assertHasNoErrors();

        $this->assertSame('image/gif', $product->fresh()->getFirstMedia('product-image')->mime_type);
        $this->assertSame(1, $product->fresh()->getMedia('product-image')->count());
    }

    public function test_image_above_five_megabytes_or_svg_is_rejected_with_persian_feedback(): void
    {
        $this->owner();
        Livewire::test(ProductsPage::class)
            ->call('create')
            ->set('form.name', 'کالای بزرگ')
            ->set('form.price_toman', '50000')
            ->set('productImage', UploadedFile::fake()->image('oversized.png')->size(5121))
            ->call('save')
            ->assertHasErrors(['productImage' => 'max'])
            ->assertSee('۵ مگابایت');

        Livewire::test(ProductsPage::class)
            ->call('create')
            ->set('form.name', 'کالای نامعتبر')
            ->set('form.price_toman', '50000')
            ->set('productImage', UploadedFile::fake()->create('graphic.svg', 15, 'image/svg+xml'))
            ->call('save')
            ->assertHasErrors('productImage');

        $this->assertDatabaseCount('products', 0);
    }

    public function test_group_photo_uses_same_origin_and_is_visible_to_variants(): void
    {
        $this->owner();
        config(['filesystems.disks.public.url' => 'http://127.0.0.1:8017/storage']);
        Livewire::test(ProductsPage::class)
            ->call('create')->call('chooseKind', 'variants')
            ->set('form.name', 'کالای دارای رنگ')
            ->set('form.category', 'کالای نمایشی')
            ->set('form.price_toman', '50000')
            ->set('variantFeatures', [['name' => 'رنگ', 'values' => 'آبی، قرمز']])
            ->call('generateVariants')
            ->set('productImage', UploadedFile::fake()->image('group.png'))
            ->call('save')->assertHasNoErrors();

        $products = Product::orderBy('id')->get();
        $this->assertCount(2, $products);
        $this->assertSame($products[0]->imageUrl(), $products[1]->imageUrl());
        $this->assertStringStartsWith('/storage/', $products[0]->imageUrl());
        $this->assertNotNull(ProductGroup::firstOrFail()->getFirstMedia('product-image'));
    }
}
