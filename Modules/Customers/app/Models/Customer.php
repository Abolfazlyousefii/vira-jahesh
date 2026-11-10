<?php

namespace Modules\Customers\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    protected $fillable = ['name', 'mobile', 'email', 'customer_type', 'city', 'address', 'note'];

    public function phoneNumbers(): HasMany
    {
        return $this->hasMany(CustomerPhoneNumber::class)->orderBy('id');
    }
}
