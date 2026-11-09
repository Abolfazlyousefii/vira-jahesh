<?php

namespace Modules\Sales\Livewire;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Modules\Core\Livewire\WorkspacePage;
use Modules\Core\Support\WorkspaceAccess;
use Modules\Inventory\Actions\LowStockReport;
use Modules\Inventory\Actions\StockLedger;
use Modules\Sales\Actions\SalesReport;
use Morilog\Jalali\Jalalian;

class ReportsPage extends WorkspacePage
{
    protected string $module = 'Sales';

    protected string $permission = 'sales.view';

    public string $from = '';

    public string $to = '';

    public string $channel = 'all';

    public string $warehouse = '';

    public string $stockThreshold = '5';

    #[Locked]
    public array $applied = [];

    #[Locked]
    public int $appliedThreshold = 5;

    public function mount(): void
    {
        parent::mount();
        $today = now()->timezone('Asia/Tehran');
        $this->from = Jalalian::fromCarbon($today->copy()->subDays(29))->format('Y/m/d');
        $this->to = Jalalian::fromCarbon($today)->format('Y/m/d');
        $this->applied = app(SalesReport::class)->filters($this->input());
    }

    private function input(): array
    {
        return ['from' => $this->from, 'to' => $this->to, 'channel' => $this->channel, 'warehouse' => $this->warehouse];
    }

    public function quickRange(int $days): void
    {
        $this->guard();
        abort_unless(in_array($days, [1, 7, 30], true), 422);
        $today = now()->timezone('Asia/Tehran');
        $this->from = Jalalian::fromCarbon($today->copy()->subDays($days - 1))->format('Y/m/d');
        $this->to = Jalalian::fromCarbon($today)->format('Y/m/d');
        $this->applyFilters();
    }

    public function applyFilters(): void
    {
        $this->guard();
        $filters = app(SalesReport::class)->filters($this->input());
        if (auth()->user()->can('inventory.view')) {
            $this->stockThreshold = WorkspaceAccess::digits($this->stockThreshold);
            $this->validate(['stockThreshold' => 'required|integer|between:0,1000000']);
            $this->appliedThreshold = (int) $this->stockThreshold;
        }
        $this->applied = $filters;
        $this->from = $filters['from'];
        $this->to = $filters['to'];
        $this->resetValidation();
        $this->resetPage('eventsPage');
        $this->resetPage('dailyPage');
    }

    private function paginate(array $rows, int $perPage, string $name): LengthAwarePaginator
    {
        $page = $this->getPage($name);

        return new LengthAwarePaginator(collect($rows)->forPage($page, $perPage)->values(), count($rows), $perPage, $page, ['pageName' => $name, 'path' => request()->url()]);
    }

    public function render()
    {
        $this->guard();
        $report = null;
        try {
            $report = app(SalesReport::class)->handle($this->applied);
        } catch (ValidationException $exception) {
            foreach ($exception->errors() as $key => $messages) {
                $this->addError($key, $messages[0]);
            }
        }
        $stock = auth()->user()->can('inventory.view') ? app(LowStockReport::class)->handle($this->appliedThreshold, (int) ($this->applied['warehouse'] ?? 0) ?: null) : null;

        return view('sales::livewire.reports', [
            'report' => $report, 'stock' => $stock,
            'warehouses' => app(StockLedger::class)->warehouses(),
            'events' => $this->paginate($report['events'] ?? [], 20, 'eventsPage'),
            'daily' => $this->paginate($report['daily'] ?? [], 14, 'dailyPage'),
        ])->layoutData(['title' => 'گزارش‌های فروش']);
    }
}
