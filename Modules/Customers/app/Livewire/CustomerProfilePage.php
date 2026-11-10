<?php

namespace Modules\Customers\Livewire;

use App\Support\ModuleAccess;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Modules\Core\Livewire\WorkspacePage;
use Modules\Core\Support\WorkspaceAccess;
use Modules\Crm\Actions\CompleteFollowUp;
use Modules\Crm\Actions\CustomerFollowUps;
use Modules\Crm\Actions\SaveFollowUp;
use Modules\Customers\Actions\CustomerDirectory;
use Modules\Customers\Models\CustomerPhoneNumber;
use Modules\Sales\Actions\CustomerSalesHistory;
use Modules\Sales\Actions\CustomerYearlyActivity;
use Morilog\Jalali\Jalalian;

class CustomerProfilePage extends WorkspacePage
{
    protected string $module = 'Customers';

    protected string $permission = 'customers.view';

    #[Locked]
    public int $customerId;

    #[Locked]
    public ?int $selectedSaleId = null;

    public string $followUpFilter = 'open';

    public string $activeTab = 'overview';

    public int $year = 0;

    #[Locked]
    public ?int $editingPhoneId = null;

    public array $phoneForm = ['type' => 'mobile', 'number' => '', 'label' => ''];

    public array $followUpForm = ['title' => '', 'due_date' => '', 'note' => ''];

    public function mount(int $customerId = 0): void
    {
        parent::mount();
        $this->customerId = app(CustomerDirectory::class)->find($customerId)->id;
        $this->year = Jalalian::fromCarbon(now('Asia/Tehran'))->getYear();
        $this->followUpForm['due_date'] = Jalalian::fromCarbon(now()->timezone('Asia/Tehran'))->format('Y/m/d');
    }

    public function setTab(string $tab): void
    {
        $this->guard();
        abort_unless(in_array($tab, ['overview', 'purchases', 'followups', 'details'], true), 422);
        if ($tab === 'purchases') {
            WorkspaceAccess::check('Sales', 'sales.view');
        }
        if ($tab === 'followups') {
            WorkspaceAccess::check('Crm', 'crm.view');
        }
        $this->activeTab = $tab;
    }

    public function setYear(int $year): void
    {
        $this->guard();
        WorkspaceAccess::check('Sales', 'sales.view');
        $this->year = $year;
    }

    public function newPhone(): void
    {
        $this->guard('customers.manage');
        $this->editingPhoneId = null;
        $this->phoneForm = ['type' => 'mobile', 'number' => '', 'label' => ''];
        $this->resetValidation();
    }

    public function editPhone(int $id): void
    {
        $this->guard('customers.manage');
        $phone = CustomerPhoneNumber::where('customer_id', $this->customerId)->findOrFail($id);
        $this->editingPhoneId = $phone->id;
        $this->phoneForm = ['type' => $phone->type, 'number' => $phone->number_normalized, 'label' => $phone->label ?? ''];
        $this->resetValidation();
    }

    public function savePhone(): void
    {
        $this->guard('customers.manage');
        $customer = app(CustomerDirectory::class)->find($this->customerId);
        $this->phoneForm['number'] = preg_replace('/[\s\-().]/u', '', WorkspaceAccess::digits($this->phoneForm['number'] ?? ''));
        $type = $this->phoneForm['type'] ?? '';
        $rule = $type === 'mobile' ? '/^09[0-9]{9}$/' : '/^0[0-9]{9,11}$/';
        $data = $this->validate([
            'phoneForm.type' => 'required|in:mobile,landline,work,other',
            'phoneForm.number' => ['required', 'regex:'.$rule, Rule::notIn([$customer->mobile]), Rule::unique('customer_phone_numbers', 'number_normalized')->where('customer_id', $customer->id)->ignore($this->editingPhoneId)],
            'phoneForm.label' => 'nullable|string|max:60',
        ])['phoneForm'];
        $phone = $this->editingPhoneId ? $customer->phoneNumbers()->findOrFail($this->editingPhoneId) : new CustomerPhoneNumber;
        $phone->fill(['type' => $data['type'], 'number_normalized' => $data['number'], 'label' => $data['label'] ?? null]);
        $customer->phoneNumbers()->save($phone);
        $this->newPhone();
        $this->notice = 'شماره تماس ذخیره شد.';
    }

    public function removePhone(int $id): void
    {
        $this->guard('customers.manage');
        CustomerPhoneNumber::where('customer_id', $this->customerId)->findOrFail($id)->delete();
        $this->newPhone();
        $this->notice = 'شماره فرعی حذف شد.';
    }

    public function selectReceipt(int $id): void
    {
        $this->guard();
        app(CustomerSalesHistory::class)->receipt($this->customerId, $id);
        $this->selectedSaleId = $id;
    }

    public function closeReceipt(): void
    {
        $this->guard();
        $this->selectedSaleId = null;
    }

    public function saveFollowUp(): void
    {
        $this->guard();
        WorkspaceAccess::check('Crm', 'crm.view');
        try {
            app(SaveFollowUp::class)->handle([...$this->followUpForm, 'customer_id' => $this->customerId]);
        } catch (ValidationException $exception) {
            $errors = [];
            foreach ($exception->errors() as $key => $messages) {
                $errors[str_replace('form.', 'followUpForm.', $key)] = $messages;
            }
            throw ValidationException::withMessages($errors);
        }
        $this->followUpForm = ['title' => '', 'due_date' => Jalalian::fromCarbon(now()->timezone('Asia/Tehran'))->format('Y/m/d'), 'note' => ''];
        $this->followUpFilter = 'open';
        $this->resetPage('followUpsPage');
        $this->resetValidation();
        $this->notice = 'پیگیری در پروندهٔ همین مشتری ثبت شد.';
    }

    public function completeFollowUp(int $id): void
    {
        $this->guard();
        WorkspaceAccess::check('Crm', 'crm.view');
        app(CompleteFollowUp::class)->handle($id, $this->customerId);
        $this->notice = 'پیگیری انجام شد.';
    }

    public function updatedFollowUpFilter(): void
    {
        $this->guard();
        WorkspaceAccess::check('Crm', 'crm.view');
        $this->validate(['followUpFilter' => 'required|in:all,open,done']);
        $this->resetPage('followUpsPage');
    }

    public function render()
    {
        $this->guard();
        $customer = app(CustomerDirectory::class)->find($this->customerId)->load('phoneNumbers');
        $modules = app(ModuleAccess::class);
        $canSales = $modules->available('Sales') && auth()->user()->can('sales.view');
        $canCrm = $modules->available('Crm') && auth()->user()->can('crm.view');
        $summary = null;
        $summaryError = '';
        $yearly = null;
        $yearlyError = '';
        $favoriteProducts = [];
        if ($canSales) {
            try {
                $summary = app(CustomerSalesHistory::class)->summary($customer->id);
            } catch (ValidationException $exception) {
                $summaryError = $exception->errors()['profileTotals'][0] ?? throw $exception;
            }
            try {
                $yearly = app(CustomerYearlyActivity::class)->handle($customer->id, $this->year);
                $favoriteProducts = app(CustomerYearlyActivity::class)->favoriteProducts($customer->id);
            } catch (ValidationException $exception) {
                $yearlyError = $exception->getMessage();
            }
        }

        return view('customers::livewire.customer-profile', [
            'customer' => $customer, 'canSales' => $canSales, 'canCrm' => $canCrm, 'summary' => $summary, 'summaryError' => $summaryError,
            'yearly' => $yearly, 'yearlyError' => $yearlyError, 'favoriteProducts' => $favoriteProducts,
            'purchases' => $canSales ? app(CustomerSalesHistory::class)->purchases($customer->id) : null,
            'refunds' => $canSales ? app(CustomerSalesHistory::class)->refunds($customer->id) : null,
            'selectedSale' => $canSales && $this->selectedSaleId ? app(CustomerSalesHistory::class)->receipt($customer->id, $this->selectedSaleId) : null,
            'followUpSummary' => $canCrm ? app(CustomerFollowUps::class)->summary($customer->id) : null,
            'followUps' => $canCrm ? app(CustomerFollowUps::class)->paginate($customer->id, in_array($this->followUpFilter, ['all', 'open', 'done'], true) ? $this->followUpFilter : 'open') : null,
        ])->layoutData(['title' => 'پروندهٔ مشتری']);
    }
}
