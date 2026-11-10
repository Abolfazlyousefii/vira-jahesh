<?php

namespace Modules\Customers\Livewire;

use App\Support\ModuleAccess;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Modules\Core\Livewire\WorkspacePage;
use Modules\Core\Support\WorkspaceAccess;
use Modules\Customers\Actions\CustomerDirectory;
use Modules\Customers\Models\Customer;
use Modules\Sales\Models\Sale;

class CustomersPage extends WorkspacePage
{
    protected string $module = 'Customers';

    protected string $permission = 'customers.view';

    #[Locked]
    public ?int $editingId = null;

    public bool $showForm = false;

    public string $filter = 'all';

    public array $form = ['name' => '', 'mobile' => '', 'customer_type' => 'person', 'email' => '', 'city' => '', 'address' => '', 'note' => ''];

    public function create(): void
    {
        $this->guard('customers.manage');
        $this->reset('editingId', 'form', 'notice');
        $this->resetValidation();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $this->guard('customers.manage');
        $customer = Customer::findOrFail($id);
        $this->editingId = $id;
        $this->form = $customer->only(array_keys($this->form));
        $this->resetValidation();
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->guard('customers.manage');
        $this->form['mobile'] = WorkspaceAccess::digits($this->form['mobile'] ?? '');
        $this->form['name'] = trim($this->form['name'] ?? '');
        $this->form['email'] = trim($this->form['email'] ?? '');
        $data = $this->validate([
            'form.name' => 'required|string|max:120',
            'form.mobile' => ['required', 'regex:/^09[0-9]{9}$/', Rule::unique('customers', 'mobile')->ignore($this->editingId)],
            'form.customer_type' => 'required|in:person,business',
            'form.email' => 'nullable|email|max:190',
            'form.city' => 'nullable|string|max:100',
            'form.address' => 'nullable|string|max:1000',
            'form.note' => 'nullable|string|max:2000',
        ])['form'];
        $customer = $this->editingId ? Customer::findOrFail($this->editingId) : new Customer;
        $customer->fill($data)->save();
        $this->showForm = false;
        $this->notice = 'اطلاعات مشتری ذخیره شد.';
    }

    public function updatedFilter(): void
    {
        $this->guard();
        $this->validate(['filter' => 'required|in:all,with_sales,without_sales']);
        $this->resetPage();
    }

    public function render()
    {
        $this->guard();
        $canSales = app(ModuleAccess::class)->available('Sales') && auth()->user()->can('sales.view');
        $canCrm = app(ModuleAccess::class)->available('Crm') && auth()->user()->can('crm.view');
        $query = app(CustomerDirectory::class)->search($this->search)->withCount('phoneNumbers');
        if ($canSales) {
            $query->addSelect(['last_sale_at' => Sale::query()->select('created_at')->whereColumn('sales.customer_id', 'customers.id')->latest('id')->limit(1)]);
            if ($this->filter === 'with_sales') {
                $query->whereExists(fn ($q) => $q->selectRaw('1')->from('sales')->whereColumn('sales.customer_id', 'customers.id'));
            } elseif ($this->filter === 'without_sales') {
                $query->whereNotExists(fn ($q) => $q->selectRaw('1')->from('sales')->whereColumn('sales.customer_id', 'customers.id'));
            }
        }

        return view('customers::livewire.customers', [
            'customers' => $query->paginate(12),
            'count' => Customer::count(),
            'canSales' => $canSales,
            'canCrm' => $canCrm,
        ])->layoutData(['title' => 'مشتریان']);
    }
}
