<?php

namespace Modules\Customers\Livewire;

use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Modules\Core\Livewire\WorkspacePage;
use Modules\Core\Support\WorkspaceAccess;
use Modules\Customers\Actions\CustomerDirectory;
use Modules\Customers\Models\Customer;

class CustomersPage extends WorkspacePage
{
    protected string $module = 'Customers';

    protected string $permission = 'customers.view';

    #[Locked]
    public ?int $editingId = null;

    public bool $showForm = false;

    public array $form = ['name' => '', 'mobile' => '', 'city' => '', 'address' => '', 'note' => ''];

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
        $data = $this->validate([
            'form.name' => 'required|string|max:120',
            'form.mobile' => ['required', 'regex:/^09[0-9]{9}$/', Rule::unique('customers', 'mobile')->ignore($this->editingId)],
            'form.city' => 'nullable|string|max:100', 'form.address' => 'nullable|string|max:1000', 'form.note' => 'nullable|string|max:2000',
        ])['form'];
        $customer = $this->editingId ? Customer::findOrFail($this->editingId) : new Customer;
        $customer->fill($data)->save();
        $this->showForm = false;
        $this->notice = 'اطلاعات مشتری ذخیره شد.';
    }

    public function render()
    {
        $this->guard();

        return view('customers::livewire.customers', ['customers' => app(CustomerDirectory::class)->search($this->search)->paginate(12), 'count' => Customer::count()])->layoutData(['title' => 'مشتریان']);
    }
}
