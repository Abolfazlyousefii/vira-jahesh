<?php

namespace Modules\Inventory\Actions;

use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Modules\Catalog\Actions\ProductDirectory;
use Modules\Core\Support\WorkspaceAccess;
use Modules\Inventory\Models\StockDocument;
use Modules\Inventory\Models\Supplier;

final class RecordStockDocument
{
    public function handle(string $token, string $kind, int $warehouseId, int $productId, int $quantity, string $reason, ?int $targetWarehouseId = null, ?int $supplierId = null, ?int $unitCost = null): StockDocument
    {
        WorkspaceAccess::check('Inventory', 'inventory.manage');
        $reason = trim($reason);
        Validator::make(compact('token', 'kind', 'warehouseId', 'productId', 'quantity', 'reason', 'targetWarehouseId', 'supplierId', 'unitCost'), [
            'token' => 'required|uuid', 'kind' => 'required|in:purchase,transfer',
            'warehouseId' => 'required|integer|exists:warehouses,id', 'productId' => 'required|integer|exists:products,id',
            'quantity' => 'required|integer|between:1,1000000', 'reason' => 'required|string|max:255',
            'targetWarehouseId' => 'nullable|required_if:kind,transfer|integer|different:warehouseId|exists:warehouses,id',
            'supplierId' => 'nullable|required_if:kind,purchase|integer|exists:suppliers,id',
            'unitCost' => 'nullable|required_if:kind,purchase|integer|between:0,1000000000000',
        ])->validate();
        if ($kind === 'transfer') {
            $supplierId = null;
            $unitCost = null;
        } else {
            $targetWarehouseId = null;
        }
        $total = $unitCost === null ? null : $unitCost * $quantity;
        if ($total > 9000000000000000) {
            throw ValidationException::withMessages(['unitCost' => 'جمع رسید از سقف مجاز بیشتر است.']);
        }
        $hash = hash('sha256', json_encode([$kind, $warehouseId, $productId, $quantity, $reason, $targetWarehouseId, $supplierId, $unitCost]));
        if ($existing = StockDocument::where('request_token', $token)->first()) {
            return $this->repeated($existing, $hash);
        }
        try {
            return DB::transaction(function () use ($token, $kind, $warehouseId, $productId, $quantity, $reason, $targetWarehouseId, $supplierId, $unitCost, $hash, $total) {
                $product = app(ProductDirectory::class)->inventoryOptions('', $productId)->firstWhere('id', $productId);
                abort_unless($product, 404);
                $doc = StockDocument::create([
                    'request_token' => $token, 'request_hash' => $hash, 'kind' => $kind,
                    'warehouse_id' => $warehouseId, 'target_warehouse_id' => $targetWarehouseId,
                    'supplier_id' => $supplierId, 'supplier_name' => $supplierId ? Supplier::findOrFail($supplierId)->name : null,
                    'product_id' => $productId, 'product_name' => $product->name, 'sku' => $product->sku,
                    'quantity' => $quantity, 'unit_cost_toman' => $unitCost, 'total_toman' => $total, 'reason' => $reason, 'user_id' => auth()->id(),
                ]);
                app(StockLedger::class)->postDocument($doc);

                return $doc;
            }, 3);
        } catch (UniqueConstraintViolationException $exception) {
            $existing = StockDocument::where('request_token', $token)->first();
            if (! $existing) {
                throw $exception;
            }

            return $this->repeated($existing, $hash);
        }
    }

    private function repeated(StockDocument $document, string $hash): StockDocument
    {
        abort_unless($document->user_id === auth()->id(), 403);
        if (! hash_equals($document->request_hash, $hash)) {
            throw ValidationException::withMessages(['quantity' => 'این درخواست قبلاً با اطلاعات دیگری ثبت شده؛ رسید تازه باز کنید.']);
        }

        return $document;
    }
}
