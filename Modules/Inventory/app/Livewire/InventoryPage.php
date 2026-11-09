<?php

namespace Modules\Inventory\Livewire;

use Modules\Catalog\Actions\ProductDirectory;
use Modules\Core\Livewire\WorkspacePage;
use Modules\Core\Support\WorkspaceAccess;
use Modules\Inventory\Actions\StockLedger;
use Modules\Inventory\Models\StockBalance;
use Modules\Inventory\Models\StockMovement;
use Modules\Inventory\Models\Warehouse;

class InventoryPage extends WorkspacePage
{
    protected string $module = 'Inventory';

    protected string $permission = 'inventory.view';

    public string $warehouseName = '';

    public string $warehouseId = '';

    public string $productId = '';

    public string $quantity = '';

    public string $direction = 'in';

    public string $reason = '';

    public bool $showForm = false;

    public function createWarehouse(): void
    {
        $this->guard('inventory.manage');
        $this->warehouseName = trim($this->warehouseName);
        $data = $this->validate(['warehouseName' => 'required|string|max:100|unique:warehouses,name']);
        $warehouse = Warehouse::create(['name' => $data['warehouseName']]);
        $this->warehouseId = (string) $warehouse->id;
        $this->warehouseName = '';
        $this->notice = 'انبار ساخته شد؛ حالا موجودی کالاها را ثبت کنید.';
    }

    public function saveMovement(): void
    {
        $this->guard('inventory.manage');
        $this->quantity = WorkspaceAccess::digits($this->quantity);
        $this->reason = trim($this->reason);
        $data = $this->validate([
            'warehouseId' => 'required|integer|exists:warehouses,id', 'productId' => 'required|integer|exists:products,id',
            'quantity' => 'required|integer|min:1|max:1000000', 'direction' => 'required|in:in,out', 'reason' => 'required|string|max:255',
        ]);
        app(StockLedger::class)->adjust((int) $data['warehouseId'], (int) $data['productId'], (int) $data['quantity'] * ($data['direction'] === 'out' ? -1 : 1), $data['reason']);
        $this->reset('quantity', 'reason');
        $this->showForm = false;
        $this->notice = 'گردش موجودی ثبت شد.';
    }

    public function render()
    {
        $this->guard();
        $search = mb_substr($this->search, 0, 160);

        return view('inventory::livewire.inventory', [
            'warehouses' => app(StockLedger::class)->warehouses(),
            'products' => auth()->user()->can('inventory.manage') ? app(ProductDirectory::class)->inventoryOptions($search, (int) $this->productId ?: null) : collect(),
            'balances' => StockBalance::with(['product', 'warehouse'])->when($search !== '', fn ($q) => $q->whereHas('product', fn ($q) => $q->where('name', 'like', '%'.$search.'%')))->orderBy('quantity')->paginate(12),
            'movements' => StockMovement::with(['product', 'warehouse', 'user'])->latest('id')->limit(10)->get(),
        ])->layoutData(['title' => 'انبار و موجودی']);
    }
}
