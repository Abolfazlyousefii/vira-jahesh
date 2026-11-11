<?php

namespace Modules\Catalog\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\ProductGroup;
use Modules\Core\Support\WorkspaceAccess;

final class SaveCatalogProduct
{
    public function handle(string $kind, array $form, array $variants, ?int $editingId, ?int $groupId, array $attributes = []): Product|ProductGroup
    {
        WorkspaceAccess::check('Catalog', 'catalog.manage');

        // Allocation and persistence share one transaction. Failed validation
        // cannot consume a code, and each category sequence is row-locked.
        return DB::transaction(
            fn (): Product|ProductGroup => $this->saveInTransaction($kind, $form, $variants, $editingId, $groupId, $attributes),
            5
        );
    }

    private function saveInTransaction(string $kind, array $form, array $variants, ?int $editingId, ?int $groupId, array $attributes): Product|ProductGroup
    {
        abort_unless(in_array($kind, ['simple', 'variants'], true), 422);

        $form['name'] = trim($form['name'] ?? '');
        $form['sku'] = WorkspaceAccess::digits((string) ($form['sku'] ?? ''));
        $form['unit'] = trim((string) ($form['unit'] ?? 'عدد')) ?: 'عدد';
        $form['brand'] = trim((string) ($form['brand'] ?? ''));
        $form['category'] = trim((string) ($form['category'] ?? ''));
        $form['description'] = trim((string) ($form['description'] ?? ''));

        $rules = [
            'form.name' => 'required|string|max:'.($kind === 'variants' ? '120' : '160'),
            'form.category' => 'nullable|string|max:100',
            'form.unit' => 'required|string|max:30',
            'form.brand' => 'nullable|string|max:80',
            'form.description' => 'nullable|string|max:5000',
        ];

        if ($kind === 'simple') {
            $existing = $editingId ? Product::findOrFail($editingId) : null;
            abort_if($existing?->group_id !== null, 422);
            // Ignore user-submitted SKU for new and edited products.
            $form['sku'] = $existing?->sku ?? app(CatalogSkuAllocator::class)->next($form['category']);
            $form = $this->normalize($form);
            $rules = [...$rules, ...$this->skuRules('form', $editingId, $existing?->sku === $form['sku'])];
            $data = Validator::make(['form' => $form], $rules, $this->messages())->validate()['form'];
            if (ProductGroup::where('sku', $data['sku'])->exists()) {
                throw ValidationException::withMessages(['form.sku' => 'کد کالا قبلاً استفاده شده است.']);
            }

            return DB::transaction(function () use ($data, $existing): Product {
                $product = $existing ?? new Product;
                $product->fill($data)->save();

                return $product;
            });
        }

        $group = $groupId ? ProductGroup::findOrFail($groupId) : null;
        $existing = $group?->variants()->get()->keyBy('id') ?? collect();
        if ($groupId) {
            abort_unless($editingId && $existing->has($editingId), 422);
            // Historic groups may have no parent code; keep the original identity.
            $form['sku'] = $group->sku;
        } else {
            abort_if($editingId !== null, 422);
            $form['sku'] = app(CatalogSkuAllocator::class)->next($form['category'], count($variants));
        }

        $rules['form.sku'] = $groupId
            ? ['nullable', 'string', 'max:64']
            : ['required', 'regex:/^[0-9]{4,12}$/', Rule::unique('product_groups', 'sku')];
        if (! $groupId && Product::where('sku', $form['sku'])->exists()) {
            throw ValidationException::withMessages(['form.sku' => 'کد کالا قبلاً استفاده شده است.']);
        }

        $variants = array_values($variants);
        $seen = [];
        $ids = [];
        $nextSuffix = 1;
        if ($group && preg_match('/^[0-9]{9}$/', (string) $group->sku)) {
            foreach ($existing as $stored) {
                if (preg_match('/^'.preg_quote($group->sku, '/').'([0-9]{2})$/', $stored->sku, $match)) {
                    $nextSuffix = max($nextSuffix, (int) $match[1] + 1);
                }
            }
        }
        foreach ($variants as $index => &$variant) {
            $variant = $this->normalize($variant);
            if (! $group) {
                $variant['sku'] = $form['sku'].str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT);
            } elseif (isset($variant['id']) && $original = $existing->get($variant['id'])) {
                $variant['sku'] = $original->sku;
            } else {
                if (! preg_match('/^[0-9]{9}$/', (string) $group->sku) || $nextSuffix > 99) {
                    $this->invalidVariants('افزودن تنوع جدید به این گروه با ساختار کد فعلی امکان‌پذیر نیست.');
                }
                $variant['sku'] = $group->sku.str_pad((string) $nextSuffix++, 2, '0', STR_PAD_LEFT);
            }
            $variant['color'] = trim((string) ($variant['color'] ?? ''));
            $variant['size'] = strtoupper(WorkspaceAccess::digits((string) ($variant['size'] ?? '')));
            $options = $variant['options'] ?? [];
            if (! is_array($options)) {
                $this->invalidVariants('ویژگی‌های تنوع باید معتبر باشند.');
            }
            $options = array_filter($options, fn ($value, $name) => is_string($name) && is_string($value) && trim($name) !== '' && trim($value) !== '', ARRAY_FILTER_USE_BOTH);
            $variant['options'] = $options ?: null;
            $id = $variant['id'] ?? null;
            if ($id !== null) {
                $original = $existing->get($id);
                if (! $original || in_array($id, $ids, true)
                    || $original->color !== $variant['color'] || $original->size !== $variant['size']
                    || ($original->options ?? []) !== ($options ?: [])) {
                    $this->invalidVariants('شناسه و ویژگی‌های تنوع ثبت‌شده قابل تغییر نیستند. تنوع جدید تعریف کنید.');
                }
                $ids[] = $id;
            }
            $combination = $options
                ? json_encode($options, JSON_UNESCAPED_UNICODE)
                : mb_strtolower($variant['color'].'|'.$variant['size']);
            if ($combination === '|' || in_array($combination, $seen, true)) {
                $this->invalidVariants('ترکیب ویژگی‌های تنوع نباید خالی یا تکراری باشد.');
            }
            $seen[] = $combination;
            if (ProductGroup::where('sku', $variant['sku'])->exists()) {
                $this->invalidVariants('کد یکی از تنوع‌ها با کد کالای دیگری تکراری است.');
            }
            $legacy = $id !== null && $existing->get($id)?->sku === $variant['sku'];
            $rules = [...$rules, ...$this->skuRules('variants.'.$index, $id, $legacy),
                'variants.'.$index.'.color' => 'present|string|max:20',
                'variants.'.$index.'.size' => 'present|string|max:12',
                'variants.'.$index.'.options' => 'nullable|array',
            ];
        }
        unset($variant);

        if ($existing->keys()->diff($ids)->isNotEmpty()) {
            $this->invalidVariants('تنوع ثبت‌شده قابل حذف نیست؛ آن را غیرفعال کنید.');
        }
        $rules['variants'] = 'required|array|min:1|max:50';
        $rules['variants.*.sku'][] = 'distinct';
        $rules['variants.*.barcode'] = ['nullable', 'distinct'];
        $data = Validator::make(['form' => $form, 'variants' => $variants], $rules, $this->messages())->validate();

        $normalizedAttributes = $group ? ($group->attributes ?? []) : $this->normalizeAttributes($attributes);
        if ($normalizedAttributes !== []) {
            foreach ($variants as $variant) {
                $options = $variant['options'] ?? [];
                if (count($options ?: []) !== count($normalizedAttributes)) {
                    $this->invalidVariants('ترکیب‌های تنوع با ویژگی‌های تعریف‌شده هماهنگ نیستند.');
                }
                foreach ($normalizedAttributes as $attribute) {
                    if (! isset($options[$attribute['name']]) || ! in_array($options[$attribute['name']], $attribute['values'], true)) {
                        $this->invalidVariants('مقادیر تنوع باید از ویژگی‌های تعریف‌شده انتخاب شوند.');
                    }
                }
            }
        }

        return DB::transaction(function () use ($data, $group, $existing, $variants, $normalizedAttributes): ProductGroup {
            $record = $group ?? new ProductGroup;
            $record->fill([...$data['form'], 'attributes' => $normalizedAttributes])->save();

            foreach ($data['variants'] as $index => $variant) {
                $product = isset($variants[$index]['id'])
                    ? $existing->get($variants[$index]['id']) : new Product;
                $label = implode(' / ', $variant['options'] ? array_values($variant['options']) : array_filter(
                    [$variant['color'], $variant['size']], fn ($value) => $value !== ''
                ));
                $product->fill([
                    ...$variant,
                    'group_id' => $record->id,
                    'name' => $record->name.' · '.$label,
                    'category' => $record->category,
                    'description' => $record->description,
                    'unit' => $record->unit,
                    'brand' => $record->brand,
                ])->save();
            }

            return $record;
        });
    }

    private function normalize(array $data): array
    {
        $data['sku'] = WorkspaceAccess::digits((string) ($data['sku'] ?? ''));
        $data['barcode'] = WorkspaceAccess::digits((string) ($data['barcode'] ?? '')) ?: null;
        $data['price_toman'] = WorkspaceAccess::digits((string) ($data['price_toman'] ?? ''));

        return $data;
    }

    private function skuRules(string $prefix, ?int $id, bool $unchangedLegacy = false): array
    {
        return [
            $prefix.'.sku' => ['required', 'regex:'.($unchangedLegacy ? '/^[A-Z0-9_-]{1,64}$/' : '/^[0-9]{4,14}$/'), Rule::unique('products', 'sku')->ignore($id)],
            $prefix.'.barcode' => ['nullable', 'regex:/^[0-9]{4,64}$/', Rule::unique('products', 'barcode')->ignore($id)],
            $prefix.'.price_toman' => ['required', 'integer', 'min:0', 'max:1000000000000'],
            $prefix.'.is_active' => ['required', 'boolean'],
            $prefix.'.is_published' => ['required', 'boolean'],
        ];
    }

    private function normalizeAttributes(array $attributes): array
    {
        if (count($attributes) > 3) {
            $this->invalidVariants('حداکثر سه ویژگی برای این کالا تعریف کنید.');
        }

        $output = [];
        foreach ($attributes as $attribute) {
            $name = trim((string) ($attribute['name'] ?? ''));
            $values = $attribute['values'] ?? '';
            $values = is_array($values) ? $values : preg_split('/[,،\n]+/u', (string) $values);
            $values = array_values(array_unique(array_filter(array_map('trim', $values))));
            if ($name === '' || mb_strlen($name) > 40 || ! count($values) || count($values) > 10) {
                $this->invalidVariants('برای هر ویژگی، نام و یک تا ده مقدار معتبر وارد کنید.');
            }
            foreach ($values as $value) {
                if (mb_strlen($value) > 50) {
                    $this->invalidVariants('مقدار ویژگی نباید بیش از ۵۰ حرف باشد.');
                }
            }
            if (in_array(mb_strtolower($name), array_map(fn ($a) => mb_strtolower($a['name']), $output), true)) {
                $this->invalidVariants('نام ویژگی تکراری است.');
            }
            $output[] = ['name' => $name, 'values' => $values];
        }

        return $output;
    }

    private function invalidVariants(string $message): never
    {
        throw ValidationException::withMessages(['variants' => $message]);
    }

    private function messages(): array
    {
        return [
            'required' => 'این بخش را تکمیل کنید.',
            'unique' => 'کد یا بارکد قبلاً ثبت شده است.',
            'distinct' => 'کد یا بارکد تکراری مجاز نیست.',
            'regex' => 'کد کالا فقط باید شامل ارقام باشد (بدون حروف).',
            'integer' => 'مبلغ را به‌صورت عدد صحیح وارد کنید.',
            'max' => 'مقدار بیش از حد مجاز است.',
            'min' => 'مقدار کمتر از حد مجاز است.',
        ];
    }
}
