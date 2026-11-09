<?php

namespace Modules\Inventory\Actions;

use Illuminate\Support\Facades\Validator;
use Modules\Catalog\Actions\ProductDirectory;
use Modules\Core\Support\WorkspaceAccess;

final class LowStockReport
{
    public function handle(int $threshold, ?int $warehouseId): array
    {
        WorkspaceAccess::check('Inventory', 'inventory.view');
        Validator::make(compact('threshold', 'warehouseId'), ['threshold' => 'integer|between:0,1000000', 'warehouseId' => 'nullable|integer|exists:warehouses,id'])->validate();
        $rows = app(ProductDirectory::class)->stockReportProducts()
            ->crossJoin('warehouses')->leftJoin('stock_balances', fn ($join) => $join->on('stock_balances.product_id', '=', 'products.id')->on('stock_balances.warehouse_id', '=', 'warehouses.id'))
            ->when($warehouseId, fn ($q) => $q->where('warehouses.id', $warehouseId))
            ->whereRaw('COALESCE(stock_balances.quantity, 0) <= ?', [$threshold])
            ->select('products.id', 'products.name', 'products.sku', 'warehouses.name as warehouse')
            ->selectRaw('COALESCE(stock_balances.quantity, 0) as quantity')
            ->orderBy('quantity')->orderBy('products.id')->orderBy('warehouses.id')->limit(21)->get();

        return ['rows' => $rows->take(20), 'has_more' => $rows->count() > 20];
    }
}
