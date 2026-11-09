<?php

namespace Modules\Core\Livewire;

use Modules\Core\Models\BusinessSetting;
use Modules\Core\Support\WorkspaceAccess;

class BusinessSettingsPage extends WorkspacePage
{
    protected string $module = 'Core';

    protected string $permission = 'settings.manage';

    public array $form = ['name' => '', 'phone' => '', 'city' => '', 'address' => ''];

    public function mount(): void
    {
        parent::mount();
        if ($settings = BusinessSetting::find(1)) {
            $this->form = $settings->only(array_keys($this->form));
        }
    }

    public function save(): void
    {
        $this->guard();
        $this->form['phone'] = WorkspaceAccess::digits($this->form['phone'] ?? '');
        $data = $this->validate([
            'form.name' => 'required|string|max:120', 'form.phone' => ['nullable', 'regex:/^[0-9+() -]{5,30}$/'],
            'form.city' => 'nullable|string|max:100', 'form.address' => 'nullable|string|max:1000',
        ])['form'];
        BusinessSetting::updateOrCreate(['id' => 1], $data);
        $this->notice = 'اطلاعات مجموعه ذخیره شد.';
    }

    public function render()
    {
        $this->guard();

        return view('core::livewire.business-settings')->layoutData(['title' => 'تنظیمات مجموعه']);
    }
}
