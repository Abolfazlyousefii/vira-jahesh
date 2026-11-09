<?php

namespace Modules\Sales\Models;

use Illuminate\Database\Eloquent\Model;

class SaleItem extends Model
{
    public $timestamps = false;

    protected $fillable = ['product_id', 'name', 'sku', 'quantity', 'unit_price_toman'];

    protected function casts(): array
    {
        return ['quantity' => 'integer', 'unit_price_toman' => 'integer', 'returned_quantity' => 'integer'];
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function returns()
    {
        return $this->hasMany(SaleReturn::class);
    }
}
