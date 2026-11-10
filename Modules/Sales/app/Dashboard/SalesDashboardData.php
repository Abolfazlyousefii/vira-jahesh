<?php

namespace Modules\Sales\Dashboard;

use Illuminate\Support\Carbon;
use Modules\Sales\Models\Sale;
use Morilog\Jalali\Jalalian;

final class SalesDashboardData
{
    /** Daily gross sales in Tehran, not net revenue. Queries stay bounded to 30 indexed date ranges. */
    public function trend(): array
    {
        $start = now('Asia/Tehran')->startOfDay()->subDays(29);
        $points = [];

        for ($index = 0; $index < 30; $index++) {
            $day = $start->copy()->addDays($index);
            $end = $day->copy()->addDay();
            $gross = Sale::query()
                ->where('created_at', '>=', $day->copy()->utc())
                ->where('created_at', '<', $end->utc())
                ->sum('total_toman');

            $points[] = [
                'label' => Jalalian::fromCarbon($day)->format('m/d'),
                'amount' => (int) $gross,
            ];
        }

        $week = array_slice($points, -7);

        return [
            'week' => $week,
            'month' => $points,
            'formatted' => [
                'week' => $this->safeFormattedTotal($week),
                'month' => $this->safeFormattedTotal($points),
            ],
        ];
    }

    private function safeFormattedTotal(array $points): string
    {
        $total = 0;
        foreach ($points as $point) {
            $amount = $point['amount'];
            if ($amount < 0 || $total > PHP_INT_MAX - $amount) {
                return 'بیش از محدوده';
            }
            $total += $amount;
        }

        return number_format($total);
    }

    public function recent(): array
    {
        return Sale::query()->latest('id')->limit(3)->get([
            'id', 'customer_name', 'total_toman', 'created_at', 'channel',
        ])->map(fn (Sale $sale) => [
            'id' => $sale->id,
            'customer' => $sale->customer_name,
            'amount' => (int) $sale->total_toman,
            'date' => Jalalian::fromCarbon(Carbon::parse($sale->created_at)->timezone('Asia/Tehran'))->format('m/d'),
            'channel' => $sale->channel === 'pos' ? 'صندوق' : 'فروش مستقیم',
        ])->all();
    }
}
