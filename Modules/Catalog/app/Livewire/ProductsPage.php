<?php

namespace Modules\Catalog\Livewire;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\WithFileUploads;
use Modules\Catalog\Actions\CatalogWorkspaceDirectory;
use Modules\Catalog\Actions\SaveCatalogProduct;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\ProductGroup;
use Modules\Core\Livewire\WorkspacePage;

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

    #[Locked]
    public ?int $selectedId = null;

    public $productImage;

    public array $variants = [];

    public array $variantFeatures = [];

    public array $form = [
        'name' => '', 'sku' => '', 'barcode' => '', 'category' => '',
        'unit' => 'عدد', 'brand' => '', 'price_toman' => '',
        'description' => '', 'is_active' => true, 'is_published' => false,
    ];

    public string $categoryFilter = '';

    public string $statusFilter = '';

    public string $kindFilter = '';

    public string $sortBy = 'newest';

    public string $displayMode = 'table';

    public int $visibleCount = 25;

    public function mount(?string $mode = null): void
    {
        parent::mount();

        if ($mode === 'create') {
            $this->create();
        }
    }

    public function create(): void
    {
        $this->guard('catalog.manage');
        $this->reset('editingId', 'groupId', 'kind', 'variants', 'variantFeatures', 'productImage', 'form', 'notice');
        $this->resetValidation();
        $this->showForm = true;
        $this->selectedId = null;
        $this->dispatch('catalog-scroll-top');
    }

    public function cancel(): void
    {
        $this->showForm = false;
        $this->resetValidation();
        $this->dispatch('catalog-scroll-top');
    }

    public function chooseKind(string $kind): void
    {
        $this->guard('catalog.manage');
        abort_if($this->editingId !== null || ! in_array($kind, ['simple', 'variants'], true), 422);
        $this->kind = $kind;
        $this->resetValidation();
    }

    public function addAttribute(): void
    {
        $this->guard('catalog.manage');
        abort_unless($this->showForm && $this->kind === 'variants' && $this->editingId === null && count($this->variantFeatures) < 3, 422);
        $this->variantFeatures[] = ['name' => '', 'values' => ''];
    }

    public function removeAttribute(int $index): void
    {
        $this->guard('catalog.manage');
        abort_unless($this->editingId === null && array_key_exists($index, $this->variantFeatures), 422);
        array_splice($this->variantFeatures, $index, 1);
        $this->variants = [];
    }

    public function generateCode(): void
    {
        $this->guard('catalog.manage');
        abort_unless($this->showForm && $this->editingId === null, 422);
        // Kept as a permission-checked compatibility entry point.
        // The real code is assigned atomically when the new product is saved.
        $this->form['sku'] = '';
        $this->notice = 'کد کالا هنگام ذخیره، براساس دسته‌بندی و شماره ترتیبی ساخته می‌شود.';
    }

    public function generateVariants(): void
    {
        $this->guard('catalog.manage');
        abort_unless($this->showForm && $this->kind === 'variants' && $this->editingId === null, 422);
        $attributes = Validator::make(
            ['variantFeatures' => $this->variantFeatures],
            [
                'variantFeatures' => 'required|array|min:1|max:3',
                'variantFeatures.*.name' => 'required|string|max:40',
                'variantFeatures.*.values' => 'required|string|max:500',
            ],
            [
                'variantFeatures.*.name.required' => 'نام ویژگی را وارد کنید.',
                'variantFeatures.*.values.required' => 'حداقل یک مقدار برای این ویژگی وارد کنید.',
            ],
            [
                'variantFeatures' => 'ویژگی‌های کالا',
                'variantFeatures.*.name' => 'نام ویژگی',
                'variantFeatures.*.values' => 'مقادیر ویژگی',
            ]
        )->validate()['variantFeatures'];

        $sets = [];
        foreach ($attributes as $attribute) {
            $name = trim($attribute['name']);
            if (array_key_exists($name, $sets)) {
                throw ValidationException::withMessages(['variantFeatures' => 'نام ویژگی‌ها نباید تکراری باشد.']);
            }
            $values = array_values(array_unique(array_filter(array_map(
                'trim', preg_split('/[,،\n]+/u', $attribute['values'])
            ))));
            if (! count($values) || count($values) > 10 || collect($values)->contains(fn ($v) => mb_strlen($v) > 50)) {
                throw ValidationException::withMessages(['variantFeatures' => 'برای هر ویژگی، یک تا ده مقدار (حداکثر ۵۰ حرف) وارد کنید.']);
            }
            $sets[$name] = $values;
        }
        $combinations = [[]];
        foreach ($sets as $name => $values) {
            $next = [];
            foreach ($combinations as $combination) {
                foreach ($values as $value) {
                    $next[] = [...$combination, $name => $value];
                    if (count($next) > 50) {
                        throw ValidationException::withMessages(['variantFeatures' => 'حداکثر ۵۰ ترکیب برای هر کالا مجاز است.']);
                    }
                }
            }
            $combinations = $next;
        }

        $this->variants = [];
        foreach ($combinations as $index => $options) {
            // SKU is allocated at save time, never from client input.
            $sku = '';
            $this->variants[] = [
                'id' => null,
                'options' => $options,
                'color' => mb_strlen($options['رنگ'] ?? '') <= 20 ? ($options['رنگ'] ?? '') : '',
                'size' => mb_strlen($options['سایز'] ?? '') <= 12 ? ($options['سایز'] ?? '') : '',
                'sku' => $sku,
                'barcode' => '',
                'price_toman' => $this->form['price_toman'] ?? '',
                'is_active' => (bool) $this->form['is_active'],
                'is_published' => (bool) $this->form['is_published'],
            ];
        }
        $this->resetValidation();
    }

    // Backward-compatible entry point for existing products and authorization tests.
    public function addVariant(): void
    {
        $this->guard('catalog.manage');
        abort_unless($this->kind === 'variants' && count($this->variants) < 50, 422);
        $this->variants[] = [
            'id' => null, 'color' => '', 'size' => '', 'sku' => '', 'barcode' => '',
            'price_toman' => '', 'is_active' => true, 'is_published' => false,
        ];
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
        $this->reset('form', 'variants', 'variantFeatures', 'productImage', 'notice', 'groupId');
        $product = Product::with('group.variants')->findOrFail($id);
        $this->editingId = $id;
        $this->kind = $product->group_id ? 'variants' : 'simple';

        if ($product->group_id) {
            $group = $product->group;
            $this->groupId = $product->group_id;
            $this->form = [...$this->form, ...$group->only(['name', 'sku', 'category', 'unit', 'brand', 'description'])];
            $this->form['price_toman'] = (string) $product->price_toman;
            $this->variantFeatures = collect($group->attributes ?? [])->map(fn ($a) => [
                'name' => $a['name'], 'values' => implode('، ', $a['values']),
            ])->all();
            $this->variants = $group->variants->map(fn (Product $variant) => $variant->only([
                'id', 'color', 'size', 'options', 'sku', 'barcode', 'price_toman', 'is_active', 'is_published',
            ]))->all();
        } else {
            $this->form = $product->only(array_keys($this->form));
        }

        $this->resetValidation();
        $this->showForm = true;
        $this->dispatch('catalog-scroll-top');
    }

    public function save(): void
    {
        $this->guard('catalog.manage');
        $this->validate([
            'productImage' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'],
        ], [
            'productImage.image' => 'فایل انتخاب‌شده تصویر معتبر نیست.',
            'productImage.mimes' => 'فرمت تصویر باید JPG، PNG، WebP یا GIF باشد.',
            'productImage.max' => 'حجم تصویر نباید بیشتر از ۵ مگابایت باشد.',
            'productImage.uploaded' => 'بارگذاری تصویر انجام نشد؛ دوباره تلاش کنید.',
        ]);

        $record = app(SaveCatalogProduct::class)->handle(
            $this->kind, $this->form, $this->variants, $this->editingId, $this->groupId, $this->variantFeatures
        );
        if ($record instanceof ProductGroup) {
            $this->groupId = $record->id;
            $this->editingId = $record->variants()->firstOrFail()->id;
        } else {
            $this->editingId = $record->id;
        }
        if ($this->productImage) {
            $record->addMedia($this->productImage->getRealPath())
                ->usingFileName($this->productImage->hashName())
                ->toMediaCollection('product-image');
            $this->productImage = null;
        }
        $this->showForm = false;
        $this->visibleCount = 25;
        $this->notice = 'کالا با کد '.($record->sku ?? '').' با موفقیت ذخیره شد.';
        $this->dispatch('catalog-scroll-top');
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
        return $this->groupId
            ? ProductGroup::findOrFail($this->groupId)
            : ($this->editingId ? Product::findOrFail($this->editingId) : null);
    }

    public function setDisplayMode(string $mode): void
    {
        $this->guard();
        abort_unless(in_array($mode, ['table', 'grid'], true), 422);
        $this->displayMode = $mode;
    }

    public function exportCsv()
    {
        $this->guard('catalog.manage');
        $filters = [$this->search, $this->categoryFilter, $this->statusFilter, $this->kindFilter, $this->sortBy];

        return response()->streamDownload(function () use ($filters): void {
            $stream = fopen('php://output', 'wb');
            fwrite($stream, "\xEF\xBB\xBF");
            fputcsv($stream, [
                'ردیف', 'نام کالا', 'کد کالا', 'بارکد', 'دسته‌بندی', 'نوع کالا',
                'ویژگی‌ها', 'قیمت فروش (تومان)', 'واحد', 'برند', 'وضعیت فروش', 'وضعیت انتشار',
            ], ';', '"', '\\');

            $rowNumber = 0;
            app(CatalogWorkspaceDirectory::class)->query(...$filters)->chunk(200, function ($products) use ($stream, &$rowNumber): void {
                foreach ($products as $product) {
                    if ($product->group_id && $product->group) {
                        foreach ($product->group->variants as $variant) {
                            $rowNumber++;
                            fputcsv($stream, [$rowNumber, ...$this->exportRow($product, $variant)], ';', '"', '\\');
                        }
                    } else {
                        $rowNumber++;
                        fputcsv($stream, [$rowNumber, ...$this->exportRow($product, $product)], ';', '"', '\\');
                    }
                }
            });

            fclose($stream);
        }, 'vira-products-'.now()->format('Ymd-His').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store, private',
        ]);
    }

    private function exportRow(Product $representative, Product $sellable): array
    {
        $isGroup = $representative->group_id !== null;
        $options = $sellable->options ?: array_filter([
            'رنگ' => $sellable->color,
            'سایز' => $sellable->size,
        ], fn ($value) => $value !== null && $value !== '');
        $features = collect($options)->map(fn ($value, $name) => $name.': '.$value)->implode('، ');
        $values = [
            $isGroup ? ($representative->group?->name ?? $sellable->name) : $sellable->name,
            $sellable->sku, $sellable->barcode, $sellable->category,
            $isGroup ? 'دارای تنوع' : 'ساده', $features, $sellable->price_toman,
            $sellable->unit ?: 'عدد', $sellable->brand,
            $sellable->is_active ? 'فعال' : 'غیرفعال',
            $sellable->is_published ? 'منتشرشده' : 'منتشرنشده',
        ];

        return array_map(fn ($value) => $this->safeCsvCell($value), $values);
    }

    private function safeCsvCell(mixed $value): string
    {
        $value = (string) ($value ?? '');

        return preg_match('/^[\\x00-\\x20]*[=+@-]/u', $value) === 1 ? "'".$value : $value;
    }

    public function loadMore(): void
    {
        $this->guard();
        $this->visibleCount += 25;
    }

    public function showDetails(int $id): void
    {
        $this->guard();
        $this->selectedId = Product::findOrFail($id)->id;
    }

    public function closeDetails(): void
    {
        $this->selectedId = null;
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->categoryFilter = '';
        $this->statusFilter = '';
        $this->kindFilter = '';
        $this->sortBy = 'newest';
        $this->visibleCount = 25;
    }

    public function updatedSearch(): void
    {
        $this->visibleCount = 25;
    }

    public function updatedCategoryFilter(): void
    {
        $this->visibleCount = 25;
    }

    public function updatedStatusFilter(): void
    {
        $this->visibleCount = 25;
    }

    public function updatedKindFilter(): void
    {
        $this->visibleCount = 25;
    }

    public function updatedSortBy(): void
    {
        $this->visibleCount = 25;
    }

    public function render()
    {
        $this->guard();

        $directory = app(CatalogWorkspaceDirectory::class);
        $statistics = $directory->statistics();
        $products = collect();
        $hasMore = false;

        if (! $this->showForm) {
            $rows = $directory->query(
                $this->search, $this->categoryFilter, $this->statusFilter,
                $this->kindFilter, $this->sortBy
            )->limit($this->visibleCount + 1)->get();
            $hasMore = $rows->count() > $this->visibleCount;
            $products = $rows->take($this->visibleCount);
        }

        return view('catalog::livewire.products', [
            'products' => $products,
            'hasMore' => $hasMore,
            'statistics' => $statistics,
            'categories' => $directory->categories(),
            'details' => $this->selectedId ? Product::with('group.variants')->findOrFail($this->selectedId) : null,
            'editedImageUrl' => $this->showForm && auth()->user()->can('catalog.manage')
                ? ($this->imageRecord()?->imageUrl() ?? '') : '',
        ])->layoutData(['title' => $this->showForm ? 'تعریف کالای جدید' : 'کالاهای نرم‌افزار']);
    }
}
