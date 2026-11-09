<?php

namespace Modules\Inventory\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Modules\Catalog\Actions\ProductDirectory;
use Modules\Core\Support\WorkspaceAccess;
use Modules\Inventory\Models\StockBalance;
use Modules\Inventory\Models\StockDocument;
use Modules\Inventory\Models\StockMovement;
use Modules\Inventory\Models\Warehouse;
use Modules\Sales\Models\Sale;
use Modules\Sales\Models\SaleReturn;

final class StockLedger
{
    public function warehouses()
    {
        return Warehouse::orderBy('name')->get();
    }

    public function quantities(int $warehouseId, array $productIds): array
    {
        return StockBalance::where('warehouse_id', $warehouseId)->whereIn('product_id', $productIds)->pluck('quantity', 'product_id')->all();
    }

    public function adjust(int $warehouseId, int $productId, int $delta, string $reason): void
    {
        WorkspaceAccess::check('Inventory', 'inventory.manage');
        Validator::make(compact('delta', 'reason'), ['delta' => 'required|integer|not_in:0|between:-1000000,1000000', 'reason' => 'required|string|max:255'])->validate();
        $this->move($warehouseId, $productId, $delta, $reason, null, 'quantity');
    }

    public function sell(Sale $sale, int $productId, int $quantity): void
    {
        WorkspaceAccess::check($sale->channel === 'pos' ? 'Pos' : 'Sales', $sale->channel === 'pos' ? 'pos.manage' : 'sales.manage');
        abort_unless(DB::transactionLevel() > 0 && $quantity > 0, 422);
        $this->move($sale->warehouse_id, $productId, -$quantity, 'فروش شمارهٔ '.$sale->id, 'sale:'.$sale->id, 'items');
    }

    public function postDocument(StockDocument $document): void
    {
        WorkspaceAccess::check('Inventory', 'inventory.manage');
        abort_unless(DB::transactionLevel() > 0 && $document->exists && $document->quantity > 0, 422);
        $reference = 'stock-document:'.$document->id;
        if ($document->kind === 'transfer') {
            $this->move($document->warehouse_id, $document->product_id, -$document->quantity, 'انتقال خروج #'.$document->id, $reference, 'quantity');
            $this->move($document->target_warehouse_id, $document->product_id, $document->quantity, 'انتقال ورود #'.$document->id, $reference, 'quantity');
        } else {
            abort_unless($document->kind === 'purchase', 422);
            $this->move($document->warehouse_id, $document->product_id, $document->quantity, 'رسید خرید #'.$document->id, $reference, 'quantity');
        }
    }

    public function restockReturn(SaleReturn $return): void
    {
        WorkspaceAccess::check('Sales', 'sales.manage');
        abort_unless(DB::transactionLevel() > 0 && $return->exists && $return->quantity > 0, 422);
        $line = $return->item;
        $this->move($line->sale->warehouse_id, $line->product_id, $return->quantity, 'مرجوعی #'.$return->id.' فاکتور #'.$line->sale_id, 'sale-return:'.$return->id, 'returnQuantity');
    }

    private function move(int $warehouseId, int $productId, int $delta, string $reason, ?string $reference, string $errorKey): void
    {
        DB::transaction(function () use ($warehouseId, $productId, $delta, $reason, $reference, $errorKey): void {
            Warehouse::findOrFail($warehouseId);
            abort_unless(app(ProductDirectory::class)->exists($productId), 404);
            $balance = StockBalance::firstOrCreate(['warehouse_id' => $warehouseId, 'product_id' => $productId], ['quantity' => 0]);
            // The predicate and decrement run as one SQL statement, including under concurrent sales.
            $updated = StockBalance::whereKey($balance->id)
                ->when($delta < 0, fn ($q) => $q->where('quantity', '>=', -$delta))
                ->when($delta > 0, fn ($q) => $q->where('quantity', '<=', 1000000000 - $delta))
                ->increment('quantity', $delta);
            if ($updated !== 1) {
                throw ValidationException::withMessages([$errorKey => $delta < 0 ? 'موجودی این کالا در انبار انتخاب‌شده کافی نیست.' : 'موجودی از سقف مجاز بیشتر می‌شود.']);
            }
            StockMovement::create(['warehouse_id' => $warehouseId, 'product_id' => $productId, 'delta' => $delta, 'reason' => $reason, 'reference' => $reference, 'user_id' => auth()->id()]);
        }, 3);
    }
}
