<?php

namespace Modules\Customers\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerPhoneNumber extends Model
{
    protected $fillable = ['type', 'number_normalized', 'label'];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
