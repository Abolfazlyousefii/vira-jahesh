<?php

namespace Modules\Catalog\Livewire;

use Livewire\Attributes\Locked;
use Livewire\WithFileUploads;
use Modules\Catalog\Actions\SaveCatalogProduct;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\ProductGroup;
use Modules\Core\Livewire\WorkspacePage;
use Modules\Core\Support\WorkspaceAccess;

class ProductsPage extends WorkspacePage
{
    use WithFileUploads;

    protected string $module = 'Catalog';

    protected string $permission = 'catalog.view';

    #[Locked]
    public ?int $editingId = null;

    #[Locked]
    public ?int $groupId = null;

    #[Locked]
    public string $kind = 'simple';

    public bool $showForm = false;

    public $productImage;

    public array $variants = [];

    public array $form = ['name' => '', 'sku' => '', 'barcode' => '', 'category' => '', 'price_toman' => '', 'description' => '', 'is_active' => true, 'is_published' => false];

    public function create(): void
    {
        $this->guard('catalog.manage');
        $this->reset('editingId', 'groupId', 'kind', 'variants', 'productImage', 'form', 'notice');
        $this->resetValidation();
        $this->showForm = true;
    }

    public function chooseKind(string $kind): void
    {
        $this->guard('catalog.manage');
        abort_if($this->editingId !== null || ! in_array($kind, ['simple', 'variants'], true), 422);
        $this->kind = $kind;
        if ($kind === 'variants' && $this->variants === []) {
            $this->addVariant();
            $this->addVariant();
        }
        $this->resetValidation();
    }

    public function addVariant(): void
    {
        $this->guard('catalog.manage');
        abort_unless($this->kind === 'variants' && count($this->variants) < 50, 422);
        $this->variants[] = ['id' => null, 'color' => '', 'size' => '', 'sku' => '', 'barcode' => '', 'price_toman' => '', 'is_active' => true, 'is_published' => false];
    }

    public function removeVariant(int $index): void
    {
        $this->guard('catalog.manage');
        if (isset($this->variants[$index]['id'])) {
            $this->addError('variants', 'تنوع ثبت‌شده حذف نمی‌شود؛ برای توقف فروش، آن را غیرفعال کنید.');

            return;
        }
        unset($this->variants[$index]);
        $this->variants = array_values($this->variants);
    }

    public function edit(int $id): void
    {
        $this->guard('catalog.manage');
        $this->reset('form', 'variants', 'productImage', 'notice', 'groupId');
        $product = Product::with('group.variants')->findOrFail($id);
        $this->editingId = $id;
        $this->kind = $product->group_id ? 'variants' : 'simple';
        if ($product->group_id) {
            $this->groupId = $product->group_id;
            $this->form = [...$this->form, ...$product->group->only(['name', 'category', 'description'])];
            $this->variants = $product->group->variants->map(fn (Product $variant) => $variant->only(['id', 'color', 'size', 'sku', 'barcode', 'price_toman', 'is_active', 'is_published']))->all();
        } else {
            $this->form = $product->only(array_keys($this->form));
        }
        $this->resetValidation();
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->guard('catalog.manage');
        $this->validate(['productImage' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:max_width=4096,max_height=4096']], [
            'productImage.*' => 'تصویر JPG، PNG یا WebP تا ۲ مگابایت و حداکثر ۴۰۹۶ پیکسل انتخاب کنید.',
        ]);
        $record = app(SaveCatalogProduct::class)->handle($this->kind, $this->form, $this->variants, $this->editingId, $this->groupId);
        if ($record instanceof ProductGroup) {
            $this->groupId = $record->id;
            $this->editingId = $record->variants()->firstOrFail()->id;
        } else {
            $this->editingId = $record->id;
        }
        if ($this->productImage) {
            $record->addMedia($this->productImage->getRealPath())->usingFileName($this->productImage->hashName())->toMediaCollection('product-image');
            $this->productImage = null;
        }
        $this->showForm = false;
        $this->notice = 'اطلاعات کالا ذخیره شد.';
    }

    public function removeImage(): void
    {
        $this->guard('catalog.manage');
        $record = $this->imageRecord();
        abort_unless($record, 404);
        $record->clearMediaCollection('product-image');
        $this->notice = 'تصویر کالا حذف شد.';
    }

    private function imageRecord(): Product|ProductGroup|null
    {
        return $this->groupId ? ProductGroup::findOrFail($this->groupId) : ($this->editingId ? Product::findOrFail($this->editingId) : null);
    }

    public function render()
    {
        $this->guard();
        $search = mb_substr(WorkspaceAccess::digits($this->search), 0, 160);
        $products = Product::with(['media', 'group.media'])->when($search !== '', fn ($q) => $q->where(fn ($q) => $q->where('name', 'like', '%'.$search.'%')->orWhere('sku', 'like', '%'.$search.'%')->orWhere('barcode', $search)))->latest('id')->paginate(12);

        return view('catalog::livewire.products', [
            'products' => $products, 'count' => Product::count(),
            'editedImageUrl' => $this->showForm && auth()->user()->can('catalog.manage') ? ($this->imageRecord()?->getFirstMediaUrl('product-image') ?? '') : '',
        ])->layoutData(['title' => 'کالا و قیمت']);
    }
}
