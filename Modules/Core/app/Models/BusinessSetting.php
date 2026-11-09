<?php

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Model;

class BusinessSetting extends Model
{
    public $incrementing = false;

    protected $fillable = ['id', 'name', 'phone', 'city', 'address'];
}
