<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Livewire\Livewire;
use Modules\Catalog\Livewire\ProductsPage;
use Tests\TestCase;

class PersianFeedbackTest extends TestCase
{
    use RefreshDatabase;

    private function owner(): void
    {
        $this->seed();
        $owner = User::factory()->create();
        $owner->assignRole('owner');
        $this->actingAs($owner);
    }

    public function test_default_validation_messages_use_persian_field_names(): void
    {
        $this->assertSame('fa', app()->getLocale());

        $errors = Validator::make([
            'email' => 'invalid',
            'password' => '',
            'form' => ['sku' => 'abc'],
        ], [
            'email' => ['email'],
            'password' => ['required'],
            'form.sku' => ['regex:/^[0-9]+$/'],
        ])->errors();

        $this->assertSame('ایمیل باید یک نشانی ایمیل معتبر باشد.', $errors->first('email'));
        $this->assertSame('وارد کردن رمز عبور الزامی است.', $errors->first('password'));
        $this->assertSame('قالب کد کالا معتبر نیست.', $errors->first('form.sku'));
    }

    public function test_catalog_missing_feature_fields_are_described_in_persian_next_to_inputs(): void
    {
        $this->owner();
        Livewire::test(ProductsPage::class)
            ->call('create')
            ->call('chooseKind', 'variants')
            ->call('addAttribute')
            ->set('form.sku', '000031')
            ->call('generateVariants')
            ->assertHasErrors(['variantFeatures.0.name' => 'required', 'variantFeatures.0.values' => 'required'])
            ->assertSee('نام ویژگی را وارد کنید.')
            ->assertSee('حداقل یک مقدار برای این ویژگی وارد کنید.')
            ->assertDontSee('The attributes.0.name field is required')
            ->assertDontSee('The variantFeatures.0.name field is required');
    }

    public function test_common_error_pages_render_useful_persian_instead_of_english_http_errors(): void
    {
        $this->get('/panel/this-route-does-not-exist')->assertNotFound()
            ->assertSee('صفحه موردنظر پیدا نشد')
            ->assertDontSee('Not Found');

        $this->owner();
        $other = User::factory()->create();
        $other->givePermissionTo(['panel.access']);
        $this->actingAs($other)->get('/panel/catalog')->assertForbidden()
            ->assertSee('اجازه دسترسی به این بخش را ندارید')
            ->assertDontSee('Forbidden');

        $this->assertStringContainsString('نشست شما منقضی شده است', view('errors.419')->render());
        $this->assertStringContainsString('تعداد درخواست‌ها بیش از حد مجاز است', view('errors.429')->render());
    }

    public function test_password_reset_and_rate_limit_messages_are_in_persian(): void
    {
        $this->assertSame('پیوند بازیابی رمز عبور معتبر نیست یا منقضی شده است.', __('passwords.token'));
        $this->assertStringContainsString('ثانیه', __('auth.throttle', ['seconds' => '۲۰']));
        $this->assertSame('fa', config('app.fallback_locale'));
    }
}
