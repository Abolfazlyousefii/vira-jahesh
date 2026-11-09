<?php

namespace Modules\Customers\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $fillable = ['name', 'mobile', 'city', 'address', 'note'];
}
