<?php

namespace Modules\Sales\Actions;

use Modules\Sales\Models\Sale;

final class SalesSummary
{
    public function cashierCount(int $userId): int
    {
        return Sale::where('channel', 'pos')->where('user_id', $userId)->count();
    }
}
