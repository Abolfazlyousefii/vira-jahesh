<?php

namespace Modules\Crm\Actions;

use Illuminate\Support\Facades\Validator;
use Modules\Core\Support\WorkspaceAccess;
use Modules\Crm\Models\FollowUp;
use Modules\Customers\Actions\CustomerDirectory;

final class CustomerFollowUps
{
    private function query(int $customerId)
    {
        WorkspaceAccess::check('Customers', 'customers.view');
        WorkspaceAccess::check('Crm', 'crm.view');
        app(CustomerDirectory::class)->find($customerId);

        return FollowUp::where('customer_id', $customerId);
    }

    public function summary(int $customerId): array
    {
        $query = $this->query($customerId)->where('status', 'open');

        return ['open' => (clone $query)->count(), 'overdue' => $query->where('due_at', '<', now('Asia/Tehran')->startOfDay()->utc())->count()];
    }

    public function paginate(int $customerId, string $filter)
    {
        Validator::make(compact('filter'), ['filter' => 'required|in:all,open,done'])->validate();

        return $this->query($customerId)->when($filter !== 'all', fn ($q) => $q->where('status', $filter))->orderBy('due_at')->orderBy('id')->paginate(10, ['*'], 'followUpsPage');
    }
}
