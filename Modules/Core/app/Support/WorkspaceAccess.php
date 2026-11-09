<?php

namespace Modules\Core\Support;

use App\Support\ModuleAccess;

final class WorkspaceAccess
{
    public static function check(string $module, string $permission): void
    {
        abort_unless(auth()->check() && auth()->user()->is_active && auth()->user()->can('panel.access'), 403);
        abort_unless(app(ModuleAccess::class)->available($module), 404);
        abort_unless(auth()->user()->can($permission), 403);
    }

    public static function digits(string $value): string
    {
        return strtr(trim($value), array_combine(
            preg_split('//u', '۰۱۲۳۴۵۶۷۸۹٠١٢٣٤٥٦٧٨٩', -1, PREG_SPLIT_NO_EMPTY),
            str_split('01234567890123456789')
        ));
    }
}
