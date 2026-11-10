<?php

namespace Modules\Crm\Dashboard;

use Modules\Crm\Models\FollowUp;
use Morilog\Jalali\Jalalian;

final class CrmDashboardData
{
    public function due(): array
    {
        $query = FollowUp::query()->where('status', 'open')->where('due_at', '<=', now());

        return [
            'count' => (clone $query)->count(),
            'items' => $query->orderBy('due_at')->limit(3)->get(['id', 'title', 'due_at'])->map(fn (FollowUp $item) => [
                'id' => $item->id,
                'title' => $item->title,
                'date' => Jalalian::fromCarbon($item->due_at->timezone('Asia/Tehran'))->format('m/d'),
            ])->all(),
        ];
    }
}
