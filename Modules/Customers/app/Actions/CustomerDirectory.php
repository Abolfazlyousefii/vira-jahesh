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
        $compact = preg_replace('/[\s\-()]/u', '', $search);
        if (preg_match('/^[0-9]+$/', $compact)) {
            $search = $compact;
        }
        // LIKE wildcards are not user-facing search operators.
        $search = str_replace(['%', '_'], '', $search);

        return Customer::query()->when($search !== '', fn ($query) => $query->where(fn ($q) => $q
            ->where('name', 'like', '%'.$search.'%')
            ->orWhere('mobile', 'like', '%'.$search.'%')
            ->orWhereHas('phoneNumbers', fn ($phones) => $phones->where('number_normalized', 'like', '%'.$search.'%'))
        ))->orderBy('name')->orderBy('id');
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
