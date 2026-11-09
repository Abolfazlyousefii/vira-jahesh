<?php

namespace Modules\Customers\Livewire;

use App\Support\ModuleAccess;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Modules\Core\Livewire\WorkspacePage;
use Modules\Core\Support\WorkspaceAccess;
use Modules\Crm\Actions\CompleteFollowUp;
use Modules\Crm\Actions\CustomerFollowUps;
use Modules\Crm\Actions\SaveFollowUp;
use Modules\Customers\Actions\CustomerDirectory;
use Modules\Sales\Actions\CustomerSalesHistory;
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

    public array $followUpForm = ['title' => '', 'due_date' => '', 'note' => ''];

    public function mount(int $customerId = 0): void
    {
        parent::mount();
        $this->customerId = app(CustomerDirectory::class)->find($customerId)->id;
        $this->followUpForm['due_date'] = Jalalian::fromCarbon(now()->timezone('Asia/Tehran'))->format('Y/m/d');
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
        $customer = app(CustomerDirectory::class)->find($this->customerId);
        $modules = app(ModuleAccess::class);
        $canSales = $modules->available('Sales') && auth()->user()->can('sales.view');
        $canCrm = $modules->available('Crm') && auth()->user()->can('crm.view');
        $summary = null;
        $summaryError = '';
        if ($canSales) {
            try {
                $summary = app(CustomerSalesHistory::class)->summary($customer->id);
            } catch (ValidationException $exception) {
                $summaryError = $exception->errors()['profileTotals'][0] ?? throw $exception;
            }
        }

        return view('customers::livewire.customer-profile', [
            'customer' => $customer, 'canSales' => $canSales, 'canCrm' => $canCrm, 'summary' => $summary, 'summaryError' => $summaryError,
            'purchases' => $canSales ? app(CustomerSalesHistory::class)->purchases($customer->id) : null,
            'refunds' => $canSales ? app(CustomerSalesHistory::class)->refunds($customer->id) : null,
            'selectedSale' => $canSales && $this->selectedSaleId ? app(CustomerSalesHistory::class)->receipt($customer->id, $this->selectedSaleId) : null,
            'followUpSummary' => $canCrm ? app(CustomerFollowUps::class)->summary($customer->id) : null,
            'followUps' => $canCrm ? app(CustomerFollowUps::class)->paginate($customer->id, in_array($this->followUpFilter, ['all', 'open', 'done'], true) ? $this->followUpFilter : 'open') : null,
        ])->layoutData(['title' => 'پروندهٔ مشتری']);
    }
}
