<?php

namespace Modules\Catalog\Actions;

use Illuminate\Database\Eloquent\Builder;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\ProductGroup;
use Modules\Core\Support\WorkspaceAccess;

final class CatalogWorkspaceDirectory
{
    public function query(string $search = '', string $category = '', string $status = '', string $kind = '', string $sort = 'newest'): Builder
    {
        $search = mb_substr(WorkspaceAccess::digits($search), 0, 160);

        // Return one representative per product group, without losing the ability
        // to find a group by any of its variants (SKU, barcode or option label).
        $query = Product::query()
            ->with(['media', 'group.media', 'group.variants'])
            ->where(function (Builder $query): void {
                $query->whereNull('products.group_id')
                    ->orWhereRaw('products.id = (SELECT MIN(child.id) FROM products AS child WHERE child.group_id = products.group_id)');
            });

        if ($search !== '') {
            $query->where(function (Builder $query) use ($search): void {
                $query->where('products.name', 'like', '%'.$search.'%')
                    ->orWhere('products.sku', 'like', '%'.$search.'%')
                    ->orWhere('products.barcode', $search)
                    ->orWhere('products.category', 'like', '%'.$search.'%')
                    ->orWhere('products.brand', 'like', '%'.$search.'%')
                    ->orWhereHas('group', function (Builder $group) use ($search): void {
                        $group->where('name', 'like', '%'.$search.'%')
                            ->orWhere('sku', 'like', '%'.$search.'%')
                            ->orWhere('brand', 'like', '%'.$search.'%');
                    })
                    ->orWhereHas('group.variants', function (Builder $variants) use ($search): void {
                        $variants->where('sku', 'like', '%'.$search.'%')
                            ->orWhere('barcode', $search)
                            ->orWhere('name', 'like', '%'.$search.'%');
                    });
            });
        }

        if ($category !== '') {
            $query->where('products.category', $category);
        }
        if ($kind === 'simple') {
            $query->whereNull('products.group_id');
        } elseif ($kind === 'variants') {
            $query->whereNotNull('products.group_id');
        }
        if ($status === 'active') {
            $query->where(function (Builder $query): void {
                $query->where(fn (Builder $q) => $q->whereNull('products.group_id')->where('products.is_active', true))
                    ->orWhere(fn (Builder $q) => $q->whereNotNull('products.group_id')->whereHas('group.variants', fn (Builder $v) => $v->where('is_active', true)));
            });
        } elseif ($status === 'inactive') {
            $query->where(function (Builder $query): void {
                $query->where(fn (Builder $q) => $q->whereNull('products.group_id')->where('products.is_active', false))
                    ->orWhere(fn (Builder $q) => $q->whereNotNull('products.group_id')->whereDoesntHave('group.variants', fn (Builder $v) => $v->where('is_active', true)));
            });
        } elseif ($status === 'without_price') {
            $query->where('products.price_toman', 0);
        }

        return match ($sort) {
            'name' => $query->orderBy('products.name')->orderBy('products.id'),
            'price' => $query->orderBy('products.price_toman')->orderBy('products.id'),
            default => $query->orderByDesc('products.id'),
        };
    }

    public function statistics(): array
    {
        return [
            'total' => Product::whereNull('group_id')->count() + ProductGroup::count(),
            'active' => Product::whereNull('group_id')->where('is_active', true)->count()
                + ProductGroup::whereHas('variants', fn (Builder $query) => $query->where('is_active', true))->count(),
            'with_variants' => ProductGroup::count(),
        ];
    }

    public function categories(): array
    {
        return Product::query()->whereNotNull('category')->where('category', '<>', '')
            ->distinct()->orderBy('category')->pluck('category')->all();
    }
}
