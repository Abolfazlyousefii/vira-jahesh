<?php

namespace Modules\Catalog\Actions;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;
use Modules\Catalog\Models\Product;
use Modules\Core\Support\WorkspaceAccess;

final class ProductDirectory
{
    public function stockReportProducts()
    {
        return Product::query()->where('products.is_active', true);
    }

    public function search(string $search = '', bool $public = false): Builder
    {
        $search = mb_substr(WorkspaceAccess::digits($search), 0, 160);

        return Product::query()->with(['media', 'group.media'])->where('is_active', true)
            ->when($public, fn ($q) => $q->where('is_published', true)->select(['id', 'group_id', 'color', 'size', 'name', 'category', 'price_toman', 'description']))
            ->when($search !== '', fn ($q) => $q->where(function ($q) use ($search, $public): void {
                $q->where('name', 'like', '%'.$search.'%')->orWhere('category', 'like', '%'.$search.'%');
                if (! $public) {
                    $q->orWhere('sku', 'like', '%'.$search.'%')->orWhere('barcode', $search);
                }
            }))->orderBy('name');
    }

    public function forSale(int $id): Product
    {
        $product = Product::where('is_active', true)->lockForUpdate()->find($id);
        if (! $product) {
            throw ValidationException::withMessages(['items' => 'یکی از کالاهای فاکتور دیگر قابل فروش نیست؛ آن را حذف کنید.']);
        }

        return $product;
    }

    public function publicSiblings(Product $product)
    {
        return $product->group_id ? $this->search('', true)->where('group_id', $product->group_id)->get() : collect();
    }

    public function exists(int $id): bool
    {
        return Product::whereKey($id)->exists();
    }

    public function inventoryOptions(string $search, ?int $selectedId = null)
    {
        $search = mb_substr(WorkspaceAccess::digits($search), 0, 160);
        $products = Product::query()->when($search !== '', fn ($q) => $q->where(fn ($q) => $q->where('name', 'like', '%'.$search.'%')->orWhere('sku', 'like', '%'.$search.'%')->orWhere('barcode', $search)))->orderBy('name')->limit(100)->get();
        if ($selectedId && ! $products->contains('id', $selectedId) && ($selected = Product::find($selectedId))) {
            $products->prepend($selected);
        }

        return $products;
    }
}
