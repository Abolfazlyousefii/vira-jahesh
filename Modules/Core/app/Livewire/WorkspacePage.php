<?php

namespace Modules\Core\Livewire;

use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\Core\Support\WorkspaceAccess;

#[Layout('components.layouts.panel')]
abstract class WorkspacePage extends Component
{
    use WithPagination;

    protected string $module;

    protected string $permission;

    public string $search = '';

    public string $notice = '';

    public function mount(): void
    {
        $this->guard();
    }

    protected function guard(?string $permission = null): void
    {
        WorkspaceAccess::check($this->module, $permission ?? $this->permission);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    protected function messages(): array
    {
        return [
            '*.required' => 'این بخش را تکمیل کنید.',
            '*.unique' => 'این مقدار قبلاً ثبت شده است.',
            '*.max' => 'مقدار واردشده بیش از حد مجاز است.',
            '*.min' => 'مقدار واردشده کمتر از حد مجاز است.',
            '*.integer' => 'تعداد یا مبلغ را به صورت عدد صحیح وارد کنید.',
            '*.regex' => 'فرمت واردشده معتبر نیست.',
            '*.exists' => 'گزینهٔ انتخاب‌شده دیگر در دسترس نیست.',
        ];
    }
}
