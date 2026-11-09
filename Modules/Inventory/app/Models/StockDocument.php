<?php

namespace Modules\Inventory\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class StockDocument extends Model
{
    protected $fillable = ['request_token', 'request_hash', 'kind', 'warehouse_id', 'target_warehouse_id', 'supplier_id', 'supplier_name', 'product_id', 'product_name', 'sku', 'quantity', 'unit_cost_toman', 'total_toman', 'reason', 'user_id'];

    protected function casts(): array
    {
        return ['quantity' => 'integer', 'unit_cost_toman' => 'integer', 'total_toman' => 'integer'];
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function targetWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'target_warehouse_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
