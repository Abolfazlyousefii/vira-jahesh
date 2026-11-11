<?php

namespace Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CatalogCodeAlias extends Model
{
    protected $fillable = ['product_id', 'old_sku'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public static function findProductByLegacySku(string $sku): ?Product
    {
        return self::query()->with('product')->where('old_sku', $sku)->first()?->product;
    }
}
