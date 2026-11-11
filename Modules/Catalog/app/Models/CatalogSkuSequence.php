<?php

namespace Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Model;

class CatalogSkuSequence extends Model
{
    protected $fillable = ['category', 'prefix', 'next_number'];

    protected function casts(): array
    {
        return ['next_number' => 'integer'];
    }
}
