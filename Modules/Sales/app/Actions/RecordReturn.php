<?php

namespace Modules\Sales\Actions;

use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Modules\Core\Support\WorkspaceAccess;
use Modules\Inventory\Actions\StockLedger;
use Modules\Sales\Models\SaleItem;
use Modules\Sales\Models\SaleReturn;

final class RecordReturn
{
    public function handle(string $token, int $itemId, int $quantity, string $reason, string $method, bool $confirmed): SaleReturn
    {
        WorkspaceAccess::check('Sales', 'sales.manage');
        $reason = trim($reason);
        Validator::make(compact('token', 'itemId', 'quantity', 'reason', 'method', 'confirmed'), [
            'token' => 'required|uuid', 'itemId' => 'required|integer|exists:sale_items,id',
            'quantity' => 'required|integer|between:1,1000000', 'reason' => 'required|string|max:255',
            'method' => 'required|in:cash,card', 'confirmed' => 'accepted',
        ], ['confirmed.accepted' => 'دریافت کالای سالم و بازپرداخت وجه را تأیید کنید.'])->validate();
        $hash = hash('sha256', json_encode([$itemId, $quantity, $reason, $method]));
        if ($existing = SaleReturn::where('request_token', $token)->first()) {
            return $this->repeated($existing, $hash);
        }
        try {
            return DB::transaction(function () use ($token, $itemId, $quantity, $reason, $method, $hash) {
                $line = SaleItem::lockForUpdate()->findOrFail($itemId);
                // Conditional increment also protects SQLite, where row locks are unavailable.
                $updated = SaleItem::whereKey($line->id)->whereRaw('returned_quantity <= quantity - ?', [$quantity])->increment('returned_quantity', $quantity);
                if ($updated !== 1) {
                    throw ValidationException::withMessages(['returnQuantity' => 'تعداد از باقیماندهٔ قابل مرجوع بیشتر است.']);
                }
                $return = SaleReturn::create(['request_token' => $token, 'request_hash' => $hash, 'sale_item_id' => $line->id,
                    'user_id' => auth()->id(), 'quantity' => $quantity, 'refund_toman' => $quantity * $line->unit_price_toman, 'reason' => $reason, 'refund_method' => $method]);
                app(StockLedger::class)->restockReturn($return);

                return $return;
            }, 3);
        } catch (UniqueConstraintViolationException $exception) {
            $existing = SaleReturn::where('request_token', $token)->first();
            if (! $existing) {
                throw $exception;
            }

            return $this->repeated($existing, $hash);
        }
    }

    private function repeated(SaleReturn $return, string $hash): SaleReturn
    {
        abort_unless($return->user_id === auth()->id(), 403);
        if (! hash_equals($return->request_hash, $hash)) {
            throw ValidationException::withMessages(['returnQuantity' => 'این درخواست قبلاً با اطلاعات دیگری ثبت شده؛ مرجوعی تازه باز کنید.']);
        }

        return $return;
    }
}
