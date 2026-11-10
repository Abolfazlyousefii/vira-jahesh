<?php

namespace Modules\Sales\Actions;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Core\Support\WorkspaceAccess;
use Modules\Customers\Actions\CustomerDirectory;
use Morilog\Jalali\Jalalian;

/** Yearly customer activity: sales and refunds belong to their own transaction dates. */
final class CustomerYearlyActivity
{
    public function handle(int $customerId, int $year): array
    {
        WorkspaceAccess::check('Customers', 'customers.view');
        WorkspaceAccess::check('Sales', 'sales.view');
        app(CustomerDirectory::class)->find($customerId);
        $todayYear = Jalalian::fromCarbon(now('Asia/Tehran'))->getYear();
        abort_unless($year >= 1390 && $year <= $todayYear + 1, 422);

        $start = $this->startOfYear($year);
        $end = $this->startOfYear($year + 1);
        $months = array_fill(1, 12, ['gross' => 0, 'refunds' => 0, 'net' => 0]);

        return DB::transaction(function () use ($customerId, $year, $start, $end, $months) {
            $sales = DB::table('sales')->where('customer_id', $customerId)
                ->where('created_at', '>=', $start)->where('created_at', '<', $end)
                ->select('total_toman', 'created_at')->orderBy('id')->limit(10001)->get();
            $refunds = DB::table('sale_returns')
                ->join('sale_items', 'sale_returns.sale_item_id', '=', 'sale_items.id')
                ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
                ->where('sales.customer_id', $customerId)
                ->where('sale_returns.created_at', '>=', $start)->where('sale_returns.created_at', '<', $end)
                ->select('sale_returns.refund_toman', 'sale_returns.created_at')->orderBy('sale_returns.id')->limit(10001)->get();
            if ($sales->count() > 10000 || $refunds->count() > 10000) {
                throw ValidationException::withMessages(['year' => 'سوابق این سال بسیار زیاد است؛ بازه کوتاه‌تری برای گزارش لازم است.']);
            }

            $gross = 0;
            $refunded = 0;
            foreach ($sales as $sale) {
                $amount = (int) $sale->total_toman;
                $month = Jalalian::fromCarbon(Carbon::parse($sale->created_at, 'UTC')->timezone('Asia/Tehran'))->getMonth();
                $months[$month]['gross'] = $this->add($months[$month]['gross'], $amount);
                $gross = $this->add($gross, $amount);
            }
            foreach ($refunds as $refund) {
                $amount = (int) $refund->refund_toman;
                $month = Jalalian::fromCarbon(Carbon::parse($refund->created_at, 'UTC')->timezone('Asia/Tehran'))->getMonth();
                $months[$month]['refunds'] = $this->add($months[$month]['refunds'], $amount);
                $refunded = $this->add($refunded, $amount);
            }
            foreach ($months as &$row) {
                $row['net'] = $row['gross'] - $row['refunds'];
            }
            unset($row);

            return [
                'year' => $year,
                'gross' => $gross,
                'refunds' => $refunded,
                'net' => $gross - $refunded,
                'sale_count' => $sales->count(),
                'months' => array_values($months),
            ];
        });
    }

    public function favoriteProducts(int $customerId): array
    {
        WorkspaceAccess::check('Customers', 'customers.view');
        WorkspaceAccess::check('Sales', 'sales.view');
        app(CustomerDirectory::class)->find($customerId);

        return DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->where('sales.customer_id', $customerId)
            ->groupBy('sale_items.sku', 'sale_items.name')
            ->select('sale_items.sku', 'sale_items.name')
            ->selectRaw('SUM(sale_items.quantity) as quantity')
            ->orderByDesc('quantity')->limit(5)->get()->all();
    }

    private function startOfYear(int $year): Carbon
    {
        $gregorian = Jalalian::fromFormat('Y/m/d', sprintf('%04d/01/01', $year))->toCarbon()->format('Y-m-d');

        return Carbon::createFromFormat('!Y-m-d', $gregorian, 'Asia/Tehran')->utc();
    }

    private function add(int $total, int $amount): int
    {
        if ($amount < 0 || $total > 9000000000000000 - $amount) {
            throw ValidationException::withMessages(['year' => 'حجم مبالغ این گزارش بیش از سقف مجاز است.']);
        }

        return $total + $amount;
    }
}
