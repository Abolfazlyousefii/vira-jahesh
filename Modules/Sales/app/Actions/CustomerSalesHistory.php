<?php

namespace Modules\Sales\Actions;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Core\Support\WorkspaceAccess;
use Modules\Customers\Actions\CustomerDirectory;
use Modules\Sales\Models\Sale;
use Modules\Sales\Models\SaleReturn;

final class CustomerSalesHistory
{
    private function sales(int $customerId): Builder
    {
        WorkspaceAccess::check('Customers', 'customers.view');
        WorkspaceAccess::check('Sales', 'sales.view');
        app(CustomerDirectory::class)->find($customerId);

        return Sale::where('customer_id', $customerId);
    }

    private function returns(int $customerId): Builder
    {
        $this->sales($customerId);

        return SaleReturn::whereHas('item.sale', fn ($q) => $q->where('customer_id', $customerId));
    }

    public function summary(int $customerId): array
    {
        $salesQuery = $this->sales($customerId);

        return DB::transaction(function () use ($salesQuery, $customerId) {
            $sales = $salesQuery->select('id', 'total_toman', 'created_at')->orderByDesc('created_at')->orderByDesc('id')->limit(10001)->get();
            $returns = $this->returns($customerId)->select('id', 'refund_toman')->orderBy('id')->limit(10001)->get();
            if ($sales->count() > 10000 || $returns->count() > 10000) {
                throw ValidationException::withMessages(['profileTotals' => 'تعداد سوابق برای جمع کل این پرونده زیاد است؛ محاسبهٔ جداگانه لازم دارد. فهرست سوابق همچنان در دسترس است.']);
            }
            $gross = $this->sum($sales->pluck('total_toman')->all());
            $refunds = $this->sum($returns->pluck('refund_toman')->all());

            return ['gross' => $gross, 'refunds' => $refunds, 'net' => $gross - $refunds,
                'sale_count' => $sales->count(), 'return_count' => $returns->count(), 'last_sale_at' => $sales->first()?->created_at];
        });
    }

    public function purchases(int $customerId)
    {
        return $this->sales($customerId)->latest('id')->paginate(10, ['*'], 'purchasesPage');
    }

    public function refunds(int $customerId)
    {
        return $this->returns($customerId)->with(['item.sale', 'user'])->latest('id')->paginate(10, ['*'], 'refundsPage');
    }

    public function receipt(int $customerId, int $saleId): Sale
    {
        return $this->sales($customerId)->with(['items.returns.user'])->findOrFail($saleId);
    }

    private function sum(array $amounts): int
    {
        $total = 0;
        foreach ($amounts as $amount) {
            if ($amount < 0 || $total > PHP_INT_MAX - $amount) {
                throw ValidationException::withMessages(['profileTotals' => 'مبلغ جمع کل از سقف محاسبه بیشتر است؛ فهرست سوابق همچنان در دسترس است.']);
            }
            $total += $amount;
        }

        return $total;
    }
}
