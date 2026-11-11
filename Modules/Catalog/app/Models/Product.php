<?php

namespace Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\Catalog\Support\ProductImageUrl;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Product extends Model implements HasMedia
{
    use InteractsWithMedia, LogsActivity;

    protected $fillable = ['name', 'sku', 'barcode', 'category', 'price_toman', 'description', 'is_active', 'is_published', 'group_id', 'color', 'size', 'unit', 'brand', 'options'];

    protected function casts(): array
    {
        return ['price_toman' => 'integer', 'is_active' => 'boolean', 'is_published' => 'boolean', 'options' => 'array'];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['name', 'sku', 'price_toman', 'is_active', 'is_published'])->logOnlyDirty()->dontSubmitEmptyLogs();
    }

    public function legacyCodeAlias(): HasOne
    {
        return $this->hasOne(CatalogCodeAlias::class);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(ProductGroup::class, 'group_id');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('product-image')->useDisk('public')->singleFile()->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp', 'image/gif']);
    }

    public function imageUrl(): string
    {
        $url = app(ProductImageUrl::class)->for($this);

        return $url !== '' ? $url : ($this->group?->imageUrl() ?? '');
    }
}
