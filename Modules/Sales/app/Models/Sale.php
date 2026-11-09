<?php

namespace Modules\Sales\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sale extends Model
{
    protected $fillable = ['request_token', 'request_hash', 'customer_id', 'customer_name', 'warehouse_id', 'user_id', 'channel', 'payment_method', 'total_toman'];

    protected function casts(): array
    {
        return ['total_toman' => 'integer'];
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }
}
