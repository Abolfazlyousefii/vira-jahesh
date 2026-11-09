<?php

namespace Modules\Crm\Actions;

use Modules\Core\Support\WorkspaceAccess;
use Modules\Crm\Models\FollowUp;

final class CompleteFollowUp
{
    public function handle(int $id, ?int $customerId = null): void
    {
        WorkspaceAccess::check('Crm', 'crm.manage');
        $followUp = FollowUp::when($customerId !== null, fn ($q) => $q->where('customer_id', $customerId))->findOrFail($id);
        FollowUp::whereKey($followUp->id)->where('status', 'open')->update(['status' => 'done', 'completed_at' => now()]);
    }
}
