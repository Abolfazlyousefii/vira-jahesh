<?php

namespace Modules\Sales\Actions;

use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Modules\Catalog\Actions\ProductDirectory;
use Modules\Core\Support\WorkspaceAccess;
use Modules\Customers\Actions\CustomerDirectory;
use Modules\Inventory\Actions\StockLedger;
use Modules\Sales\Models\Sale;

final class RecordSale
{
    public function handle(string $token, int $warehouseId, ?int $customerId, array $items, string $channel, string $paymentMethod = 'cash', ?array $expectedPrices = null): Sale
    {
        abort_unless(in_array($channel, ['direct', 'pos'], true), 422);
        WorkspaceAccess::check($channel === 'pos' ? 'Pos' : 'Sales', $channel === 'pos' ? 'pos.manage' : 'sales.manage');
        Validator::make(compact('token', 'warehouseId', 'customerId', 'items', 'paymentMethod'), [
            'token' => 'required|uuid', 'warehouseId' => 'required|integer|exists:warehouses,id', 'customerId' => 'nullable|integer|exists:customers,id',
            'items' => 'required|array|min:1|max:100', 'items.*.product_id' => 'required|integer|distinct|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1|max:1000000', 'paymentMethod' => 'required|in:cash,card',
        ], ['items.required' => 'ابتدا یک کالا به فاکتور اضافه کنید.', 'items.*.quantity.*' => 'تعداد کالا باید عدد صحیح و بیشتر از صفر باشد.'])->validate();
        $normalized = collect($items)->map(fn ($item) => ['product_id' => (int) $item['product_id'], 'quantity' => (int) $item['quantity']])->sortBy('product_id')->values()->all();
        $hash = hash('sha256', json_encode([$warehouseId, $customerId, $normalized, $channel, $paymentMethod]));
        if ($existing = Sale::where('request_token', $token)->first()) {
            return $this->repeated($existing, $hash);
        }
        try {
            return DB::transaction(function () use ($token, $warehouseId, $customerId, $normalized, $channel, $paymentMethod, $hash, $expectedPrices): Sale {
                $sale = Sale::create([
                    'request_token' => $token, 'request_hash' => $hash, 'warehouse_id' => $warehouseId, 'customer_id' => $customerId,
                    'customer_name' => $customerId ? app(CustomerDirectory::class)->find($customerId)->name : 'مشتری گذری',
                    'user_id' => auth()->id(), 'channel' => $channel, 'payment_method' => $paymentMethod, 'total_toman' => 0,
                ]);
                $total = 0;
                foreach ($normalized as $item) {
                    $product = app(ProductDirectory::class)->forSale($item['product_id']);
                    if ($expectedPrices !== null && ($expectedPrices[$product->id] ?? null) !== $product->price_toman) {
                        throw ValidationException::withMessages(['items' => 'قیمت کالا تغییر کرده است. قیمت‌ها را به‌روز و مبلغ دریافتی را دوباره بررسی کنید.']);
                    }
                    $total += $product->price_toman * $item['quantity'];
                    if ($total > 9000000000000000) {
                        throw ValidationException::withMessages(['items' => 'جمع فاکتور از سقف مجاز بیشتر است.']);
                    }
                    app(StockLedger::class)->sell($sale, $product->id, $item['quantity']);
                    $sale->items()->create(['product_id' => $product->id, 'name' => $product->name, 'sku' => $product->sku, 'unit_price_toman' => $product->price_toman, 'quantity' => $item['quantity']]);
                }
                $sale->update(['total_toman' => $total]);

                return $sale;
            }, 3);
        } catch (UniqueConstraintViolationException $exception) {
            $existing = Sale::where('request_token', $token)->first();
            if (! $existing) {
                throw $exception;
            }

            return $this->repeated($existing, $hash);
        }
    }

    private function repeated(Sale $sale, string $hash): Sale
    {
        abort_unless($sale->user_id === auth()->id(), 403);
        if (! hash_equals($sale->request_hash, $hash)) {
            throw ValidationException::withMessages(['items' => 'این درخواست قبلاً با اطلاعات دیگری ثبت شده است. فاکتور تازه باز کنید.']);
        }

        return $sale;
    }
}
