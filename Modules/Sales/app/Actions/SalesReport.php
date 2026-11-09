<?php

namespace Modules\Sales\Actions;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Modules\Core\Support\WorkspaceAccess;
use Morilog\Jalali\Jalalian;

final class SalesReport
{
    public function filters(array $input): array
    {
        WorkspaceAccess::check('Sales', 'sales.view');
        $values = [];
        foreach (['from', 'to', 'channel', 'warehouse'] as $key) {
            $value = $input[$key] ?? ($key === 'channel' ? 'all' : '');
            $values[$key] = is_string($value) || is_int($value) ? WorkspaceAccess::digits((string) $value) : $value;
        }
        Validator::make($values, [
            'from' => ['required', 'string', 'regex:/^1[34][0-9]{2}\/[0-9]{2}\/[0-9]{2}$/'],
            'to' => ['required', 'string', 'regex:/^1[34][0-9]{2}\/[0-9]{2}\/[0-9]{2}$/'],
            'channel' => 'required|in:all,direct,pos', 'warehouse' => 'nullable|integer|exists:warehouses,id',
        ], ['from.*' => 'تاریخ شروع شمسی معتبر مانند ۱۴۰۵/۰۸/۰۱ وارد کنید.', 'to.*' => 'تاریخ پایان شمسی معتبر وارد کنید.', 'channel.*' => 'کانال فروش معتبر انتخاب کنید.', 'warehouse.*' => 'انبار معتبر انتخاب کنید.'])->validate();
        $start = $this->date($values['from'], 'from');
        $last = $this->date($values['to'], 'to');
        if ($last->lt($start) || $start->diffInDays($last) > 365) {
            throw ValidationException::withMessages(['to' => 'پایان باید بعد از شروع باشد؛ بازه حداکثر ۳۶۶ روز است.']);
        }

        return $values;
    }

    public function handle(array $input): array
    {
        $filters = $this->filters($input);
        $start = $this->date($filters['from'], 'from')->utc();
        $end = $this->date($filters['to'], 'to')->addDay()->startOfDay()->utc();

        return DB::transaction(function () use ($filters, $start, $end) {
            $scope = fn ($query) => $query
                ->when($filters['channel'] !== 'all', fn ($q) => $q->where('sales.channel', $filters['channel']))
                ->when($filters['warehouse'] !== '', fn ($q) => $q->where('sales.warehouse_id', (int) $filters['warehouse']));
            $sales = $scope(DB::table('sales'))->join('warehouses', 'warehouses.id', '=', 'sales.warehouse_id')
                ->where('sales.created_at', '>=', $start)->where('sales.created_at', '<', $end)
                ->select('sales.id', 'sales.total_toman', 'sales.created_at', 'sales.channel', 'warehouses.name as warehouse')->orderBy('sales.id')->limit(10001)->get();
            $returns = $scope(DB::table('sale_returns')->join('sale_items', 'sale_items.id', '=', 'sale_returns.sale_item_id')->join('sales', 'sales.id', '=', 'sale_items.sale_id'))
                ->join('warehouses', 'warehouses.id', '=', 'sales.warehouse_id')
                ->where('sale_returns.created_at', '>=', $start)->where('sale_returns.created_at', '<', $end)
                ->select('sale_returns.id', 'sale_returns.quantity', 'sale_returns.refund_toman', 'sale_returns.created_at', 'sales.channel', 'warehouses.name as warehouse', 'sale_items.sale_id', 'sale_items.product_id', 'sale_items.name', 'sale_items.sku', 'sale_items.id as line_id')
                ->orderBy('sale_returns.id')->limit(10001)->get();
            $lines = $scope(DB::table('sale_items')->join('sales', 'sales.id', '=', 'sale_items.sale_id'))
                ->where('sales.created_at', '>=', $start)->where('sales.created_at', '<', $end)
                ->select('sale_items.*')->orderBy('sale_items.id')->limit(20001)->get();
            if ($sales->count() > 10000 || $returns->count() > 10000 || $lines->count() > 20000) {
                throw ValidationException::withMessages(['from' => 'گزارش بزرگ است؛ بازهٔ کوتاه‌تر، انبار یا کانال مشخص انتخاب کنید.']);
            }
            $totals = ['gross' => 0, 'refunds' => 0, 'net' => 0, 'sale_count' => $sales->count(), 'return_count' => $returns->count()];
            $daily = [];
            for ($day = $start->copy()->timezone('Asia/Tehran'); $day->lt($end); $day->addDay()) {
                $key = $day->format('Y-m-d');
                $daily[$key] = ['date' => Jalalian::fromCarbon($day)->format('Y/m/d'), 'gross' => 0, 'refunds' => 0, 'net' => 0];
            }
            $events = [];
            foreach ([['rows' => $sales, 'type' => 'sale', 'amount' => 'total_toman'], ['rows' => $returns, 'type' => 'return', 'amount' => 'refund_toman']] as $group) {
                foreach ($group['rows'] as $row) {
                    $date = Carbon::parse($row->created_at, 'UTC')->timezone('Asia/Tehran');
                    $field = $group['type'] === 'sale' ? 'gross' : 'refunds';
                    $amount = (int) $row->{$group['amount']};
                    $totals[$field] = $this->add($totals[$field], $amount);
                    $key = $date->format('Y-m-d');
                    $daily[$key][$field] = $this->add($daily[$key][$field], $amount);
                    $events[] = ['type' => $group['type'], 'id' => $row->id, 'sale_id' => $group['type'] === 'sale' ? $row->id : $row->sale_id,
                        'date' => Jalalian::fromCarbon($date)->format('Y/m/d'), 'time' => $date->format('H:i'), 'warehouse' => $row->warehouse, 'channel' => $row->channel,
                        'gross' => $field === 'gross' ? $amount : 0, 'refunds' => $field === 'refunds' ? $amount : 0,
                        'net' => $field === 'gross' ? $amount : -$amount, 'sort' => $row->created_at];
                }
            }
            $totals['net'] = $totals['gross'] - $totals['refunds'];
            foreach ($daily as &$row) {
                $row['net'] = $row['gross'] - $row['refunds'];
            }
            unset($row);
            $products = [];
            foreach ($lines as $line) {
                $this->product($products, $line, (int) $line->quantity, (int) $line->quantity * (int) $line->unit_price_toman, false);
            }
            foreach ($returns as $line) {
                $this->product($products, $line, (int) $line->quantity, (int) $line->refund_toman, true);
            }
            foreach ($products as &$row) {
                $row['net_quantity'] = $row['sold_quantity'] - $row['returned_quantity'];
                $row['net'] = $row['gross'] - $row['refunds'];
            }
            unset($row);

            return ['filters' => $filters, 'totals' => $totals, 'daily' => array_reverse(array_values($daily)),
                'products' => collect($products)->sortByDesc('net')->values()->all(),
                'events' => collect($events)->sortByDesc('sort')->values()->all()];
        });
    }

    private function date(string $value, string $field): Carbon
    {
        try {
            $date = Jalalian::fromFormat('Y/m/d', $value);
            if ($date->format('Y/m/d') !== $value) {
                throw new \InvalidArgumentException;
            }

            return Carbon::createFromFormat('!Y-m-d', $date->toCarbon()->format('Y-m-d'), 'Asia/Tehran');
        } catch (\InvalidArgumentException|\OutOfRangeException $exception) {
            throw ValidationException::withMessages([$field => 'تاریخ شمسی معتبر وارد کنید.']);
        }
    }

    private function add(int $total, int $amount): int
    {
        if ($amount < 0 || $total > PHP_INT_MAX - $amount) {
            throw ValidationException::withMessages(['from' => 'مبلغ گزارش از سقف محاسبه بیشتر است؛ بازه را کوتاه‌تر کنید.']);
        }

        return $total + $amount;
    }

    private function product(array &$products, object $line, int $quantity, int $amount, bool $refund): void
    {
        $id = $line->product_id;
        $lineId = $line->line_id ?? $line->id;
        $products[$id] ??= ['product_id' => $id, 'name' => $line->name, 'sku' => $line->sku, 'last_line_id' => $lineId,
            'sold_quantity' => 0, 'returned_quantity' => 0, 'gross' => 0, 'refunds' => 0];
        if ($lineId >= $products[$id]['last_line_id']) {
            $products[$id] = [...$products[$id], 'name' => $line->name, 'sku' => $line->sku, 'last_line_id' => $lineId];
        }
        $products[$id][$refund ? 'returned_quantity' : 'sold_quantity'] += $quantity;
        $field = $refund ? 'refunds' : 'gross';
        $products[$id][$field] = $this->add($products[$id][$field], $amount);
    }
}
