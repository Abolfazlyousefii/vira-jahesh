<?php

namespace Modules\Inventory\Livewire;

use Illuminate\Support\Str;
use Livewire\Attributes\Locked;
use Modules\Catalog\Actions\ProductDirectory;
use Modules\Core\Livewire\WorkspacePage;
use Modules\Core\Support\WorkspaceAccess;
use Modules\Inventory\Actions\RecordStockDocument;
use Modules\Inventory\Actions\StockLedger;
use Modules\Inventory\Models\StockDocument;
use Modules\Inventory\Models\Supplier;

class StockDocumentsPage extends WorkspacePage
{
    protected string $module = 'Inventory';

    protected string $permission = 'inventory.view';

    #[Locked]
    public string $requestToken;

    #[Locked]
    public ?int $committedId = null;

    public string $kind = 'purchase';

    public bool $showForm = false;

    public string $warehouseId = '';

    public string $targetWarehouseId = '';

    public string $supplierId = '';

    public string $productId = '';

    public string $productSearch = '';

    public string $quantity = '';

    public string $unitCost = '';

    public string $reason = '';

    public string $supplierName = '';

    public string $supplierPhone = '';

    public bool $deliveryConfirmed = false;

    public function mount(): void
    {
        parent::mount();
        $this->requestToken = (string) Str::uuid();
    }

    public function newDocument(string $kind = 'purchase'): void
    {
        $this->guard('inventory.manage');
        abort_unless(in_array($kind, ['purchase', 'transfer'], true), 422);
        $this->reset('committedId', 'quantity', 'unitCost', 'reason', 'productId', 'productSearch', 'notice', 'targetWarehouseId', 'deliveryConfirmed');
        $this->resetValidation();
        $this->kind = $kind;
        $this->showForm = true;
        $this->requestToken = (string) Str::uuid();
    }

    public function saveSupplier(): void
    {
        $this->guard('inventory.manage');
        $this->supplierName = trim($this->supplierName);
        $this->supplierPhone = WorkspaceAccess::digits($this->supplierPhone);
        $data = $this->validate(['supplierName' => 'required|string|max:120', 'supplierPhone' => ['nullable', 'regex:/^[0-9+() -]{5,20}$/']]);
        $supplier = Supplier::create(['name' => $data['supplierName'], 'phone' => $data['supplierPhone'] ?: null]);
        $this->supplierId = (string) $supplier->id;
        $this->reset('supplierName', 'supplierPhone');
        $this->notice = 'تأمین‌کننده اضافه و برای رسید انتخاب شد.';
    }

    public function saveDocument(): void
    {
        $this->guard('inventory.manage');
        if ($this->committedId) {
            return;
        }
        $this->quantity = WorkspaceAccess::digits($this->quantity);
        $this->unitCost = WorkspaceAccess::digits($this->unitCost);
        $this->validate([
            'kind' => 'required|in:purchase,transfer', 'warehouseId' => 'required|integer|exists:warehouses,id',
            'productId' => 'required|integer|exists:products,id', 'quantity' => 'required|integer|between:1,1000000',
            'reason' => 'required|string|max:255',
            'deliveryConfirmed' => 'accepted',
            'targetWarehouseId' => 'nullable|required_if:kind,transfer|integer|different:warehouseId|exists:warehouses,id',
            'supplierId' => 'nullable|required_if:kind,purchase|integer|exists:suppliers,id',
            'unitCost' => 'nullable|required_if:kind,purchase|integer|between:0,1000000000000',
        ]);
        $doc = app(RecordStockDocument::class)->handle($this->requestToken, $this->kind, (int) $this->warehouseId, (int) $this->productId, (int) $this->quantity, $this->reason,
            $this->kind === 'transfer' ? (int) $this->targetWarehouseId : null,
            $this->kind === 'purchase' ? (int) $this->supplierId : null,
            $this->kind === 'purchase' ? (int) $this->unitCost : null);
        $this->committedId = $doc->id;
        $this->showForm = false;
        $this->notice = 'سند شمارهٔ '.$doc->id.' ثبت شد و موجودی به‌روز شد.';
    }

    public function render()
    {
        $this->guard();
        $canWrite = auth()->user()->can('inventory.manage');
        $quantity = WorkspaceAccess::digits($this->quantity);
        $cost = WorkspaceAccess::digits($this->unitCost);
        $purchaseTotal = null;
        if (ctype_digit($quantity) && strlen($quantity) <= 7 && (int) $quantity <= 1000000 && ctype_digit($cost) && strlen($cost) <= 13 && (int) $cost <= 1000000000000) {
            $candidate = (int) $quantity * (int) $cost;
            $purchaseTotal = $candidate <= 9000000000000000 ? $candidate : null;
        }

        return view('inventory::livewire.stock-documents', [
            'canWrite' => $canWrite,
            'purchaseTotal' => $purchaseTotal,
            'warehouses' => app(StockLedger::class)->warehouses(),
            'suppliers' => Supplier::orderBy('name')->get(),
            'products' => $canWrite ? app(ProductDirectory::class)->inventoryOptions(mb_substr($this->productSearch, 0, 160), (int) $this->productId ?: null) : collect(),
            'documents' => StockDocument::with(['warehouse', 'targetWarehouse', 'user'])->when($this->search !== '', fn ($q) => $q->where(fn ($q) => $q->where('product_name', 'like', '%'.mb_substr($this->search, 0, 160).'%')->orWhere('supplier_name', 'like', '%'.mb_substr($this->search, 0, 160).'%')))->latest('id')->paginate(12),
        ])->layoutData(['title' => 'خرید و انتقال کالا']);
    }
}
