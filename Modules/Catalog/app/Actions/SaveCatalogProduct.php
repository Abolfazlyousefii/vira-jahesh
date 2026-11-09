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
    public function handle(string $kind, array $form, array $variants, ?int $editingId, ?int $groupId): Product|ProductGroup
    {
        WorkspaceAccess::check('Catalog', 'catalog.manage');
        abort_unless(in_array($kind, ['simple', 'variants'], true), 422);
        $form['name'] = trim($form['name'] ?? '');
        $rules = [
            'form.name' => 'required|string|max:'.($kind === 'variants' ? '120' : '160'),
            'form.category' => 'nullable|string|max:100', 'form.description' => 'nullable|string|max:5000',
        ];
        if ($kind === 'simple') {
            $existing = $editingId ? Product::findOrFail($editingId) : null;
            abort_if($existing?->group_id !== null, 422);
            $form = $this->normalize($form);
            $data = Validator::make(['form' => $form], [...$rules, ...$this->skuRules('form', $editingId)], $this->messages())->validate()['form'];

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
        } else {
            abort_if($editingId !== null, 422);
        }
        $variants = array_values($variants);
        $seen = [];
        $ids = [];
        foreach ($variants as $index => &$variant) {
            $variant = $this->normalize($variant);
            $variant['color'] = trim($variant['color'] ?? '');
            $variant['size'] = strtoupper(WorkspaceAccess::digits($variant['size'] ?? ''));
            $id = $variant['id'] ?? null;
            if ($id !== null) {
                $original = $existing->get($id);
                if (! $original || in_array($id, $ids, true) || $original->color !== $variant['color'] || $original->size !== $variant['size']) {
                    $this->invalidVariants('شناسه، رنگ یا سایز تنوع ثبت‌شده را نمی‌توان تغییر داد. برای ترکیب تازه، تنوع جدید اضافه کنید.');
                }
                $ids[] = $id;
            }
            $combination = mb_strtolower($variant['color'].'|'.$variant['size']);
            if ($combination === '|' || in_array($combination, $seen, true)) {
                $this->invalidVariants('برای هر ردیف، رنگ یا سایز مشخص و غیرتکراری وارد کنید.');
            }
            $seen[] = $combination;
            $rules = [...$rules, ...$this->skuRules('variants.'.$index, $id),
                'variants.'.$index.'.color' => 'present|string|max:20', 'variants.'.$index.'.size' => 'present|string|max:12',
            ];
        }
        unset($variant);
        if ($existing->keys()->diff($ids)->isNotEmpty()) {
            $this->invalidVariants('تنوع ثبت‌شده حذف نمی‌شود؛ برای توقف فروش، آن را غیرفعال کنید.');
        }
        $rules['variants'] = 'required|array|min:1|max:50';
        $rules['variants.*.sku'][] = 'distinct:ignore_case';
        $rules['variants.*.barcode'] = ['nullable', 'distinct'];
        $data = Validator::make(['form' => $form, 'variants' => $variants], $rules, $this->messages())->validate();

        return DB::transaction(function () use ($data, $group, $existing, $variants): ProductGroup {
            $record = $group ?? new ProductGroup;
            $record->fill($data['form'])->save();
            foreach ($data['variants'] as $index => $variant) {
                $product = isset($variants[$index]['id']) ? $existing->get($variants[$index]['id']) : new Product;
                $label = implode(' / ', array_filter([$variant['color'], $variant['size']], fn ($value) => $value !== ''));
                $product->fill([...$variant, 'group_id' => $record->id, 'name' => $record->name.' · '.$label, 'category' => $record->category, 'description' => $record->description])->save();
            }

            return $record;
        });
    }

    private function normalize(array $data): array
    {
        $data['sku'] = strtoupper(WorkspaceAccess::digits($data['sku'] ?? ''));
        $data['barcode'] = WorkspaceAccess::digits($data['barcode'] ?? '') ?: null;
        $data['price_toman'] = WorkspaceAccess::digits((string) ($data['price_toman'] ?? ''));

        return $data;
    }

    private function skuRules(string $prefix, ?int $id): array
    {
        return [
            $prefix.'.sku' => ['required', 'regex:/^[A-Z0-9_-]{1,64}$/', Rule::unique('products', 'sku')->ignore($id)],
            $prefix.'.barcode' => ['nullable', 'regex:/^[0-9]{4,64}$/', Rule::unique('products', 'barcode')->ignore($id)],
            $prefix.'.price_toman' => ['required', 'integer', 'min:0', 'max:1000000000000'],
            $prefix.'.is_active' => ['required', 'boolean'], $prefix.'.is_published' => ['required', 'boolean'],
        ];
    }

    private function invalidVariants(string $message): never
    {
        throw ValidationException::withMessages(['variants' => $message]);
    }

    private function messages(): array
    {
        return ['required' => 'این بخش را تکمیل کنید.', 'unique' => 'کد یا بارکد قبلاً ثبت شده است.', 'distinct' => 'ردیف‌ها نباید کد یا بارکد تکراری داشته باشند.', 'regex' => 'فرمت واردشده معتبر نیست.', 'integer' => 'مبلغ را عدد صحیح وارد کنید.', 'max' => 'مقدار بیش از حد مجاز است.', 'min' => 'مقدار کمتر از حد مجاز است.'];
    }
}
