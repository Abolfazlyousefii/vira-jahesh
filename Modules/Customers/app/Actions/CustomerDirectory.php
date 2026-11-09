<?php

namespace Modules\Customers\Actions;

use Illuminate\Database\Eloquent\Builder;
use Modules\Core\Support\WorkspaceAccess;
use Modules\Customers\Models\Customer;

final class CustomerDirectory
{
    public function search(string $search = ''): Builder
    {
        $search = mb_substr(WorkspaceAccess::digits($search), 0, 120);

        return Customer::query()->when($search !== '', fn ($query) => $query->where(fn ($q) => $q->where('name', 'like', '%'.$search.'%')->orWhere('mobile', 'like', '%'.$search.'%')))->orderBy('name');
    }

    public function find(int $id): Customer
    {
        return Customer::findOrFail($id);
    }

    public function options(string $search, ?int $selectedId = null)
    {
        $customers = $this->search($search)->limit(50)->get();
        if ($selectedId && ! $customers->contains('id', $selectedId) && ($selected = Customer::find($selectedId))) {
            $customers->prepend($selected);
        }

        return $customers;
    }
}
