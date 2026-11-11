<?php

namespace Modules\Catalog\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use LogicException;
use Modules\Catalog\Models\CatalogSkuSequence;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\ProductGroup;

final class CatalogSkuAllocator
{
    /**
     * Call only inside the same transaction that inserts the product or group.
     *
     * 3 digits: immutable category code (assigned on first use)
     * 6 digits: sequence within that category
     * 2 digits: variant position (01-50), appended by the caller
     */
    public function next(string $category, int $variantCount = 0): string
    {
        if (DB::transactionLevel() < 1) {
            throw new LogicException('Catalog code allocation requires a transaction.');
        }

        if ($variantCount < 0 || $variantCount > 50) {
            throw ValidationException::withMessages(['variants' => 'تعداد تنوع‌ها معتبر نیست.']);
        }

        $category = trim($category);
        $sequence = CatalogSkuSequence::firstOrCreate(
            ['category' => $category],
            ['next_number' => 1]
        );

        $sequence = CatalogSkuSequence::query()->whereKey($sequence->id)->lockForUpdate()->firstOrFail();

        if ($sequence->prefix === null) {
            if ($sequence->id > 999) {
                throw ValidationException::withMessages(['form.category' => 'ظرفیت کد دسته‌بندی‌ها تکمیل شده است.']);
            }
            $sequence->prefix = str_pad((string) $sequence->id, 3, '0', STR_PAD_LEFT);
            $sequence->save();
        }

        for ($number = $sequence->next_number; $number <= 999999; $number++) {
            $base = $sequence->prefix.str_pad((string) $number, 6, '0', STR_PAD_LEFT);
            $codes = [$base];

            for ($index = 1; $index <= $variantCount; $index++) {
                $codes[] = $base.str_pad((string) $index, 2, '0', STR_PAD_LEFT);
            }

            if (Product::query()->whereIn('sku', $codes)->exists()
                || ProductGroup::query()->whereIn('sku', $codes)->exists()) {
                continue;
            }

            $sequence->next_number = $number + 1;
            $sequence->save();

            return $base;
        }

        throw ValidationException::withMessages(['form.category' => 'ظرفیت کد این دسته‌بندی تکمیل شده است.']);
    }
}
