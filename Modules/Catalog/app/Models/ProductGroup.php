<?php

namespace Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class ProductGroup extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = ['name', 'category', 'description', 'sku', 'unit', 'brand', 'attributes'];

    protected function casts(): array
    {
        return ['attributes' => 'array'];
    }

    public function variants(): HasMany
    {
        return $this->hasMany(Product::class, 'group_id')->orderBy('id');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('product-image')->useDisk('public')->singleFile()->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }
}
