<?php

namespace Modules\Sales\Livewire;

use Illuminate\Support\Str;
use Livewire\Attributes\Locked;
use Modules\Catalog\Actions\ProductDirectory;
use Modules\Core\Livewire\WorkspacePage;
use Modules\Core\Support\WorkspaceAccess;
use Modules\Customers\Actions\CustomerDirectory;
use Modules\Inventory\Actions\StockLedger;
use Modules\Sales\Actions\RecordReturn;
use Modules\Sales\Actions\RecordSale;
use Modules\Sales\Models\Sale;

class SalesPage extends WorkspacePage
{
    protected string $module = 'Sales';

    protected string $permission = 'sales.view';

    protected string $writePermission = 'sales.manage';

    protected string $channel = 'direct';

    #[Locked]
    public string $requestToken;

    #[Locked]
    public ?int $selectedSaleId = null;

    #[Locked]
    public bool $completed = false;

    #[Locked]
    public array $quotedPrices = [];

    public bool $showForm = false;

    public string $warehouseId = '';

    public string $customerId = '';

    public string $customerSearch = '';

    public string $paymentMethod = 'cash';

    public array $items = [];

    #[Locked]
    public ?int $returnItemId = null;

    #[Locked]
    public string $returnToken = '';

    #[Locked]
    public bool $returnCompleted = false;

    public string $returnQuantity = '';

    public string $returnReason = '';

    public string $refundMethod = 'cash';

    public bool $refundConfirmed = false;

    public function beginReturn(int $itemId): void
    {
        $this->guard();
        WorkspaceAccess::check('Sales', 'sales.manage');
        $sale = $this->saleQuery()->findOrFail($this->selectedSaleId);
        $sale->items()->whereKey($itemId)->firstOrFail();
        $this->reset('returnQuantity', 'returnReason', 'refundMethod', 'refundConfirmed', 'returnCompleted');
        $this->resetValidation();
        $this->returnItemId = $itemId;
        $this->returnToken = (string) Str::uuid();
    }

    public function saveReturn(): void
    {
        $this->guard();
        WorkspaceAccess::check('Sales', 'sales.manage');
        if ($this->returnCompleted) {
            return;
        }
        $sale = $this->saleQuery()->findOrFail($this->selectedSaleId);
        $line = $sale->items()->whereKey($this->returnItemId)->firstOrFail();
        $this->returnQuantity = WorkspaceAccess::digits($this->returnQuantity);
        $this->validate(['returnQuantity' => 'required|integer|between:1,1000000', 'returnReason' => 'required|string|max:255', 'refundMethod' => 'required|in:cash,card', 'refundConfirmed' => 'accepted']);
        $return = app(RecordReturn::class)->handle($this->returnToken, $line->id, (int) $this->returnQuantity, $this->returnReason, $this->refundMethod, $this->refundConfirmed);
        $this->returnCompleted = true;
        $this->notice = 'مرجوعی شمارهٔ '.$return->id.' ثبت شد؛ بازپرداخت دستی '.number_format($return->refund_toman).' تومان و بازگشت کالا به انبار اصلی فاکتور ثبت شد.';
    }

    public function mount(): void
    {
        parent::mount();
        $this->requestToken = (string) Str::uuid();
        $this->warehouseId = (string) (app(StockLedger::class)->warehouses()->first()?->id ?? '');
        $this->showForm = $this->channel === 'pos';
    }

    public function newSale(): void
    {
        $this->guard($this->writePermission);
        $this->reset('items', 'quotedPrices', 'notice', 'completed', 'selectedSaleId', 'customerId', 'customerSearch', 'paymentMethod', 'search');
        $this->resetValidation();
        $this->requestToken = (string) Str::uuid();
        $this->showForm = true;
    }

    public function addItem(int $id): void
    {
        $this->guard($this->writePermission);
        abort_if($this->completed, 422);
        $product = app(ProductDirectory::class)->search()->findOrFail($id);
        foreach ($this->items as $index => $item) {
            if ((int) $item['product_id'] === $product->id) {
                $this->items[$index]['quantity'] = (int) WorkspaceAccess::digits((string) $item['quantity']) + 1;

                return;
            }
        }
        abort_if(count($this->items) >= 100, 422);
        $this->items[] = ['product_id' => $product->id, 'quantity' => 1];
        $this->quotedPrices[$product->id] = $product->price_toman;
        $this->showForm = true;
    }

    public function removeItem(int $index): void
    {
        $this->guard($this->writePermission);
        abort_if($this->completed, 422);
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    public function completeSale(): void
    {
        $this->guard($this->writePermission);
        if ($this->completed) {
            return;
        }
        foreach ($this->items as &$item) {
            $item['quantity'] = WorkspaceAccess::digits((string) ($item['quantity'] ?? ''));
        }
        unset($item);
        $this->validate(['warehouseId' => 'required|integer|exists:warehouses,id', 'customerId' => 'nullable|integer|exists:customers,id']);
        $sale = app(RecordSale::class)->handle($this->requestToken, (int) $this->warehouseId, $this->customerId === '' ? null : (int) $this->customerId, $this->items, $this->channel, $this->paymentMethod, $this->quotedPrices);
        $this->selectedSaleId = $sale->id;
        $this->completed = true;
        $this->showForm = false;
        $this->notice = 'فروش ثبت شد؛ شمارهٔ فاکتور: '.$sale->id;
    }

    public function refreshPrices(): void
    {
        $this->guard($this->writePermission);
        abort_if($this->completed, 422);
        $this->quotedPrices = app(ProductDirectory::class)->search()->whereIn('id', collect($this->items)->pluck('product_id')->take(100))->pluck('price_toman', 'id')->all();
        $this->resetValidation('items');
        $this->notice = 'قیمت‌ها به‌روز شد؛ مبلغ دریافتی را دوباره بررسی کنید.';
    }

    public function selectSale(int $id): void
    {
        $this->guard();
        $this->saleQuery()->findOrFail($id);
        $this->selectedSaleId = $id;
        $this->returnItemId = null;
    }

    public function closeReceipt(): void
    {
        $this->guard();
        $this->selectedSaleId = null;
        $this->returnItemId = null;
    }

    protected function saleQuery()
    {
        return Sale::query()->when($this->channel === 'pos' && ! auth()->user()->can('sales.view'), fn ($q) => $q->where('user_id', auth()->id()));
    }

    public function render()
    {
        $this->guard();
        $canWrite = auth()->user()->can($this->writePermission);
        $directory = app(ProductDirectory::class);
        $products = $canWrite ? $directory->search($this->search)->limit(24)->get() : collect();
        $cartProducts = $canWrite ? $directory->search()->whereIn('id', collect($this->items)->pluck('product_id')->take(100))->get()->keyBy('id') : collect();
        $total = 0;
        foreach ($this->items as $item) {
            $quantity = WorkspaceAccess::digits((string) ($item['quantity'] ?? ''));
            if (ctype_digit($quantity) && strlen($quantity) <= 7) {
                $total += ($this->quotedPrices[$item['product_id'] ?? 0] ?? 0) * (int) $quantity;
            }
        }

        return view('sales::livewire.sales', [
            'isPos' => $this->channel === 'pos', 'canWrite' => $canWrite,
            'products' => $products,
            'stock' => $canWrite ? app(StockLedger::class)->quantities((int) $this->warehouseId, $products->pluck('id')->all()) : [],
            'customers' => $canWrite ? app(CustomerDirectory::class)->options($this->customerSearch, (int) $this->customerId ?: null) : collect(),
            'warehouses' => $canWrite ? app(StockLedger::class)->warehouses() : collect(),
            'cartProducts' => $cartProducts, 'total' => $total,
            'sales' => $this->saleQuery()->latest('id')->paginate(12),
            'selectedSale' => $this->selectedSaleId ? $this->saleQuery()->with('items.returns.user')->findOrFail($this->selectedSaleId) : null,
        ])->layoutData(['title' => $this->channel === 'pos' ? 'صندوق فروش' : 'فروش و فاکتورها']);
    }
}
