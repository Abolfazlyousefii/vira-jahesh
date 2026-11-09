<?php

namespace Modules\Sales\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class SaleReturn extends Model
{
    protected $fillable = ['request_token', 'request_hash', 'sale_item_id', 'user_id', 'quantity', 'refund_toman', 'refund_method', 'reason'];

    protected function casts(): array
    {
        return ['quantity' => 'integer', 'refund_toman' => 'integer'];
    }

    public function item()
    {
        return $this->belongsTo(SaleItem::class, 'sale_item_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
