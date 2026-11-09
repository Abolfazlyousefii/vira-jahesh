<?php

namespace Modules\Core\Support;

use App\Models\User;
use Spatie\Permission\Models\Role;

final class StaffAccess
{
    public static function editable(User $user): bool
    {
        return $user->hasRole('operator') && $user->roles->count() === 1
            && $user->getPermissionsViaRoles()->every(fn ($permission) => $permission->name === 'panel.access');
    }

    public static function operatorRoleReady(): bool
    {
        return Role::findByName('operator', 'web')->permissions->every(fn ($permission) => $permission->name === 'panel.access');
    }

    public static function sections(): array
    {
        return ['customers' => 'مشتریان', 'catalog' => 'کالا و قیمت', 'inventory' => 'انبار و موجودی', 'sales' => 'فروش و فاکتورها', 'pos' => 'صندوق', 'crm' => 'پیگیری مشتریان', 'storefront' => 'مدیریت سایت', 'settings' => 'تنظیمات مجموعه'];
    }

    public static function empty(): array
    {
        return array_fill_keys(array_keys(self::sections()), 'none');
    }

    public static function permissions(array $access): array
    {
        $permissions = [];
        foreach (self::sections() as $section => $label) {
            $level = $access[$section] ?? 'none';
            if ($level === 'none') {
                continue;
            }
            if (! in_array($section, ['settings', 'storefront'], true)) {
                $permissions[] = $section.'.view';
            }
            if ($level === 'manage') {
                $permissions[] = $section.'.manage';
            }
        }

        return $permissions;
    }
}
