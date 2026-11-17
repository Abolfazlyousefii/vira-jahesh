<?php

namespace Modules\Core\Support;

use App\Models\User;
use Spatie\Permission\Models\Role;

final class StaffAccess
{
    private const BASE_ROLES = ['operator', 'website_operator', 'staff'];

    private const BASE_ROLE_PERMISSIONS = ['panel.access', 'website.access'];

    public static function editable(User $user): bool
    {
        $roles = $user->roles->pluck('name');

        return $roles->isNotEmpty()
            && $roles->every(fn ($role) => in_array($role, self::BASE_ROLES, true))
            && $user->getPermissionsViaRoles()->every(
                fn ($permission) => in_array($permission->name, self::BASE_ROLE_PERMISSIONS, true)
            );
    }

    public static function operatorRoleReady(): bool
    {
        return self::roleHasPermissions('operator', ['panel.access']);
    }

    public static function websiteOperatorRoleReady(): bool
    {
        return self::roleHasPermissions('website_operator', ['website.access']);
    }

    public static function staffRoleReady(): bool
    {
        return self::roleHasPermissions('staff', []);
    }

    private static function roleHasPermissions(string $name, array $expected): bool
    {
        $actual = Role::findByName($name, 'web')->permissions->pluck('name')->sort()->values()->all();
        sort($expected);

        return $actual === $expected;
    }

    public static function sections(): array
    {
        return [
            'customers' => 'مشتریان',
            'catalog' => 'کالاهای نرم‌افزار',
            'inventory' => 'انبار و موجودی',
            'sales' => 'فروش و فاکتورها',
            'pos' => 'صندوق',
            'crm' => 'پیگیری مشتریان',
            'website' => 'مدیریت سایت',
            'settings' => 'تنظیمات مجموعه',
        ];
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

            if ($section === 'website') {
                $permissions[] = 'website.access';
                if ($level === 'manage') {
                    array_push($permissions, 'website.manage', 'storefront.manage');
                }

                continue;
            }

            if ($section !== 'settings') {
                $permissions[] = $section.'.view';
            }
            if ($level === 'manage') {
                $permissions[] = $section.'.manage';
            }
        }

        return $permissions;
    }
}
