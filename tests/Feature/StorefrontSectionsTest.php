<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Modules\Storefront\Livewire\StorefrontEditor;
use Modules\Storefront\Models\StorefrontSection;
use Tests\TestCase;

class StorefrontSectionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_storefront_home_uses_saved_sections(): void
    {
        $this->get('/')->assertOk()->assertSee('از اولین ارتباط');

        StorefrontSection::create([
            'section_key' => 'hero', 'type' => 'hero', 'position' => 0,
            'is_visible' => true,
            'settings' => [
                'title' => 'عنوان آزمایشی سایت',
                'description' => 'معرفی فروشگاه',
                'button' => 'مشاهده ویژگی‌ها',
                'href' => '#site-features',
            ],
        ]);

        $this->get('/')->assertOk()->assertSee('عنوان آزمایشی سایت');
    }

    public function test_only_authorized_owner_can_manage_the_site(): void
    {
        $this->get('/panel/storefront')->assertRedirect('/login');
        $this->seed();

        $operator = User::factory()->create();
        $operator->assignRole('operator');
        $this->actingAs($operator)->get('/panel/storefront')->assertForbidden();

        $owner = User::factory()->create();
        $owner->assignRole('owner');
        $this->actingAs($owner)->get('/panel/storefront')->assertOk();
    }

    public function test_owner_can_save_hero_and_public_page_reflects_it(): void
    {
        $this->seed();
        $owner = User::factory()->create();
        $owner->assignRole('owner');

        Livewire::actingAs($owner)->test(StorefrontEditor::class)
            ->set('form.title', 'فروشگاه اختصاصی جدید')
            ->set('form.href', '#site-features')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('storefront_sections', ['section_key' => 'hero']);
        $this->get('/')->assertSee('فروشگاه اختصاصی جدید');
    }

    public function test_invalid_external_hero_link_is_rejected(): void
    {
        $this->seed();
        $owner = User::factory()->create();
        $owner->assignRole('owner');

        Livewire::actingAs($owner)->test(StorefrontEditor::class)
            ->set('form.href', 'javascript:alert(1)')
            ->call('save')
            ->assertHasErrors(['form.href']);

        $this->assertDatabaseCount('storefront_sections', 0);
    }

    public function test_owner_can_hide_and_reorder_sections(): void
    {
        $this->seed();
        $owner = User::factory()->create();
        $owner->assignRole('owner');

        Livewire::actingAs($owner)->test(StorefrontEditor::class)
            ->call('toggleSection', 'features')
            ->call('moveSection', 'cta', 'up')
            ->assertHasNoErrors();

        $this->get('/')->assertDontSee('همه‌چیز برای مدیریت ساده‌تر');
        $this->assertDatabaseHas('storefront_sections', [
            'section_key' => 'features', 'is_visible' => false,
        ]);
    }

    public function test_disabled_storefront_blocks_management_and_public_page(): void
    {
        $this->seed();
        $owner = User::factory()->create();
        $owner->assignRole('owner');
        config(['vira.modules.Storefront.enabled' => false]);

        $this->actingAs($owner)->get('/panel/storefront')->assertNotFound();
        $this->get('/')->assertNotFound();
    }

    public function test_unauthorized_operator_cannot_invoke_editor_mutations(): void
    {
        $this->seed();
        $operator = User::factory()->create();
        $operator->assignRole('operator');

        Livewire::actingAs($operator)->test(StorefrontEditor::class)->assertForbidden();
        $this->assertDatabaseCount('storefront_sections', 0);
    }

    public function test_owner_can_upload_an_image_to_the_single_hero_media_collection(): void
    {
        Storage::fake('public');
        $this->seed();
        $owner = User::factory()->create();
        $owner->assignRole('owner');

        Livewire::actingAs($owner)->test(StorefrontEditor::class)
            ->set('heroImage', UploadedFile::fake()->image('banner.jpg', 750, 420))
            ->call('save')
            ->assertHasNoErrors();

        $hero = StorefrontSection::where('section_key', 'hero')->firstOrFail();
        $this->assertCount(1, $hero->getMedia('hero'));
        $this->get('/')->assertSee('storage/', false);
    }
}
