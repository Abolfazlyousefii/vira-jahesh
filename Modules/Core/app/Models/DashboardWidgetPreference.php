<?php

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Model;

class DashboardWidgetPreference extends Model
{
    protected $fillable = ['user_id', 'widget_key', 'position', 'size', 'is_visible'];

    protected function casts(): array
    {
        return ['position' => 'integer', 'is_visible' => 'boolean'];
    }
}
