<?php

namespace Modules\Storefront\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class StorefrontSection extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = ['section_key', 'type', 'settings', 'position', 'is_visible'];

    protected function casts(): array
    {
        return ['settings' => 'array', 'position' => 'integer', 'is_visible' => 'boolean'];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('hero')->singleFile()->useDisk('public');
    }
}
