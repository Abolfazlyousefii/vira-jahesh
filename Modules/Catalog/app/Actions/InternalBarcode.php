<?php

namespace Modules\Catalog\Actions;

use Illuminate\Validation\ValidationException;
use Modules\Catalog\Models\Product;

final class InternalBarcode
{
    /**
     * EAN-13 with restricted-circulation prefix 20: usable within a business,
     * not a globally registered GS1 product identifier.
     */
    public function forProductId(int $id): string
    {
        if ($id < 1 || $id > 9999999999) {
            throw ValidationException::withMessages(['barcode' => 'امکان ساخت بارکد برای این کالا وجود ندارد.']);
        }

        $body = '20'.str_pad((string) $id, 10, '0', STR_PAD_LEFT);
        $sum = 0;
        foreach (str_split($body) as $position => $digit) {
            $sum += (int) $digit * ($position % 2 === 0 ? 1 : 3);
        }

        return $body.((10 - $sum % 10) % 10);
    }

    public function fillMissing(Product $product): bool
    {
        if ($product->barcode !== null && $product->barcode !== '') {
            return false;
        }

        $value = $this->forProductId($product->id);
        if (Product::query()->where('barcode', $value)->whereKeyNot($product->id)->exists()) {
            throw ValidationException::withMessages(['barcode' => 'بارکد پیشنهادی قبلاً برای کالای دیگری ثبت شده است.']);
        }

        $product->barcode = $value;
        $product->save();

        return true;
    }
}
