<?php

namespace App\Support;

use App\Models\User;

final class WorkspaceDestinationResolver
{
    public function for(User $user): array
    {
        $modules = app(ModuleAccess::class);

        return [
            'website' => $modules->available('WebsiteAdmin')
                && ($user->can('website.access') || $user->can('storefront.manage')),
            'erp' => $modules->available('Core') && $user->can('panel.access'),
        ];
    }
}
