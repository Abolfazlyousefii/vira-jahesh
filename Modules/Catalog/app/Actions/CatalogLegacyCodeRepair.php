<?php

namespace Modules\Catalog\Actions;

use Illuminate\Support\Facades\DB;
use Modules\Catalog\Models\CatalogCodeAlias;
use Modules\Catalog\Models\Product;

final class CatalogLegacyCodeRepair
{
    /**
     * Only explicitly identified local demo fixtures can have SKUs renamed.
     * All other SKUs, invoice snapshots, stock and product IDs stay untouched.
     *
     * @return array{recode_count: int, barcode_count: int, examples: array<int, array<string, string>>}
     */
    public function preview(): array
    {
        $candidates = $this->demoCandidates();

        return [
            'recode_count' => count($candidates),
            'barcode_count' => Product::query()->where(function ($q): void {
                $q->whereNull('barcode')->orWhere('barcode', '');
            })->count(),
            'examples' => array_map(fn (Product $p): array => [
                'name' => $p->name,
                'old_sku' => $p->sku,
            ], array_slice($candidates, 0, 20)),
        ];
    }

    /** @return array{recoded: int, barcoded: int} */
    public function apply(): array
    {
        return DB::transaction(function (): array {
            $recoded = 0;
            foreach ($this->demoCandidates() as $product) {
                $product = Product::query()->whereKey($product->id)->lockForUpdate()->firstOrFail();
                $old = $product->sku;
                $new = app(CatalogSkuAllocator::class)->next($product->category ?? '');
                CatalogCodeAlias::query()->create(['product_id' => $product->id, 'old_sku' => $old]);
                $product->sku = $new;
                $product->save();
                $recoded++;
            }

            $barcoded = 0;
            Product::query()->where(function ($q): void {
                $q->whereNull('barcode')->orWhere('barcode', '');
            })->orderBy('id')->chunkById(100, function ($products) use (&$barcoded): void {
                foreach ($products as $product) {
                    if (app(InternalBarcode::class)->fillMissing($product)) {
                        $barcoded++;
                    }
                }
            });

            return ['recoded' => $recoded, 'barcoded' => $barcoded];
        }, 3);
    }

    /** @return array<int, Product> */
    private function demoCandidates(): array
    {
        $fixtures = json_decode(
            file_get_contents(database_path('seeders/fixtures/vira-retail-demo.json')), true, 512, JSON_THROW_ON_ERROR
        );

        $rows = [];
        foreach ($fixtures['products'] as $fixture) {
            $product = Product::query()->where('sku', 'DEMO-'.$fixture['sku'])
                ->where('name', $fixture['name'])
                ->where('category', 'کالای نمایشی')
                ->where('is_published', false)
                ->whereNull('group_id')
                ->first();

            if ($product) {
                $rows[] = $product;
            }
        }

        return $rows;
    }
}
