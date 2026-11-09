<?php

namespace Modules\Crm\Livewire;

use Livewire\Attributes\Locked;
use Modules\Core\Livewire\WorkspacePage;
use Modules\Crm\Actions\CompleteFollowUp;
use Modules\Crm\Actions\SaveFollowUp;
use Modules\Crm\Models\FollowUp;
use Modules\Customers\Actions\CustomerDirectory;
use Morilog\Jalali\Jalalian;

class FollowUpsPage extends WorkspacePage
{
    protected string $module = 'Crm';

    protected string $permission = 'crm.view';

    #[Locked]
    public ?int $editingId = null;

    public bool $showForm = false;

    public string $filter = 'open';

    public string $customerSearch = '';

    public array $form = ['customer_id' => '', 'title' => '', 'due_date' => '', 'note' => ''];

    public function create(): void
    {
        $this->guard('crm.manage');
        $this->reset('editingId', 'form', 'notice');
        $this->form['due_date'] = Jalalian::fromCarbon(now()->timezone('Asia/Tehran'))->format('Y/m/d');
        $this->resetValidation();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $this->guard('crm.manage');
        $followup = FollowUp::findOrFail($id);
        $this->editingId = $id;
        $this->form = $followup->only(['customer_id', 'title', 'note']);
        $this->form['due_date'] = Jalalian::fromCarbon($followup->due_at->timezone('Asia/Tehran'))->format('Y/m/d');
        $this->resetValidation();
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->guard('crm.manage');
        app(SaveFollowUp::class)->handle($this->form, $this->editingId);
        $this->showForm = false;
        $this->notice = 'پیگیری ذخیره شد.';
    }

    public function complete(int $id): void
    {
        $this->guard('crm.manage');
        app(CompleteFollowUp::class)->handle($id);
        $this->notice = 'پیگیری انجام شد.';
    }

    public function updatedFilter(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $this->guard();
        $search = mb_substr($this->search, 0, 160);

        return view('crm::livewire.follow-ups', [
            'followups' => FollowUp::with('customer')->when(in_array($this->filter, ['open', 'done'], true), fn ($q) => $q->where('status', $this->filter))->when($search !== '', fn ($q) => $q->where(fn ($q) => $q->where('title', 'like', '%'.$search.'%')->orWhereHas('customer', fn ($q) => $q->where('name', 'like', '%'.$search.'%'))))->orderBy('due_at')->paginate(12),
            'customers' => auth()->user()->can('crm.manage') ? app(CustomerDirectory::class)->options($this->customerSearch, (int) ($this->form['customer_id'] ?? 0) ?: null) : collect(),
        ])->layoutData(['title' => 'پیگیری مشتریان']);
    }
}
