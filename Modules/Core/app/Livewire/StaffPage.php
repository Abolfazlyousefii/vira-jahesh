<?php

namespace Modules\Core\Livewire;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Modules\Core\Support\StaffAccess;

class StaffPage extends WorkspacePage
{
    protected string $module = 'Core';

    protected string $permission = 'users.manage';

    #[Locked]
    public ?int $editingId = null;

    public bool $showForm = false;

    public array $form = ['name' => '', 'email' => '', 'is_active' => true, 'access' => []];

    public string $password = '';

    public string $password_confirmation = '';

    protected function guard(?string $permission = null): void
    {
        parent::guard($permission);
        abort_unless(auth()->user()->hasRole('owner'), 403);
    }

    public function create(): void
    {
        $this->guard();
        $this->checkBaseRole();
        $this->reset('editingId', 'form', 'password', 'password_confirmation', 'notice');
        $this->form['access'] = StaffAccess::empty();
        $this->resetValidation();
        $this->showForm = true;
    }

    public function applyPreset(string $preset): void
    {
        $this->guard();
        $this->form['access'] = match ($preset) {
            'cashier' => [...StaffAccess::empty(), 'pos' => 'manage', 'customers' => 'view'],
            'warehouse' => [...StaffAccess::empty(), 'inventory' => 'manage', 'catalog' => 'view'],
            'viewer' => [...StaffAccess::empty(), 'customers' => 'view', 'catalog' => 'view', 'inventory' => 'view', 'sales' => 'view'],
            'empty' => StaffAccess::empty(),
            default => abort(422),
        };
    }

    public function edit(int $id): void
    {
        $this->guard();
        $user = $this->employee($id);
        $this->editingId = $id;
        $this->form = [...$user->only(['name', 'email', 'is_active']), 'access' => StaffAccess::empty()];
        foreach ($this->form['access'] as $section => $level) {
            $this->form['access'][$section] = $user->can($section.'.manage') ? 'manage' : ($user->can($section.'.view') ? 'view' : 'none');
        }
        $this->reset('password', 'password_confirmation', 'notice');
        $this->resetValidation();
        $this->showForm = true;
    }

    private function employee(int $id): User
    {
        $user = User::findOrFail($id);
        abort_unless(StaffAccess::editable($user) && $user->id !== auth()->id(), 403);

        return $user;
    }

    public function save(): void
    {
        $this->guard();
        $this->checkBaseRole();
        $existing = $this->editingId ? $this->employee($this->editingId) : null;
        $this->form['email'] = Str::lower(trim($this->form['email'] ?? ''));
        $this->form['name'] = trim($this->form['name'] ?? '');
        $rules = [
            'form.name' => 'required|string|max:120', 'form.email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->editingId)],
            'form.is_active' => 'required|boolean', 'form.access' => ['required', 'array:'.implode(',', array_keys(StaffAccess::sections()))],
            'password' => [$existing ? 'nullable' : 'required', 'string', 'min:12', 'max:128', 'confirmed'],
        ];
        foreach (StaffAccess::sections() as $section => $label) {
            $rules['form.access.'.$section] = ['required', Rule::in(in_array($section, ['settings', 'storefront'], true) ? ['none', 'manage'] : ['none', 'view', 'manage'])];
        }
        $data = $this->validate($rules, ['password.required' => 'برای حساب جدید رمز اختصاصی تعیین کنید.', 'password.min' => 'رمز باید حداقل ۱۲ نویسه باشد.', 'password.confirmed' => 'تکرار رمز یکسان نیست.', 'form.email.email' => 'ایمیل معتبر وارد کنید.']);
        DB::transaction(function () use ($data, $existing): void {
            $user = $existing ?? new User;
            $old = $existing ? ['is_active' => $existing->is_active, 'permissions' => $existing->getAllPermissions()->pluck('name')->all()] : [];
            $user->fill(['name' => $data['form']['name'], 'email' => $data['form']['email']]);
            $user->is_active = $data['form']['is_active'];
            if ($this->password !== '') {
                $user->password = $this->password;
            }
            if (! $user->is_active || $this->password !== '') {
                $user->remember_token = Str::random(60);
            }
            $user->save();
            $user->assignRole('operator');
            $user->syncPermissions(StaffAccess::permissions($data['form']['access']));
            activity()->causedBy(auth()->user())->performedOn($user)->withProperties(['old' => $old, 'attributes' => ['is_active' => $user->is_active, 'permissions' => $user->getAllPermissions()->pluck('name')->all()]])->log($existing ? 'staff.updated' : 'staff.created');
        });
        $this->reset('password', 'password_confirmation');
        $this->showForm = false;
        $this->notice = 'اطلاعات و دسترسی‌های همکار ذخیره شد.';
    }

    private function checkBaseRole(): void
    {
        if (! StaffAccess::operatorRoleReady()) {
            throw ValidationException::withMessages(['form.access' => 'نقش پایهٔ کارکنان دسترسی سفارشی دارد؛ پیش از ساخت یا ویرایش همکار، تنظیم نقش را بررسی کنید.']);
        }
    }

    public function render()
    {
        $this->guard();
        $search = mb_substr($this->search, 0, 120);

        return view('core::livewire.staff', [
            'staff' => User::role(['owner', 'operator'])->with('roles.permissions')->when($search !== '', fn ($q) => $q->where(fn ($q) => $q->where('name', 'like', '%'.$search.'%')->orWhere('email', 'like', '%'.$search.'%')))->latest('id')->paginate(12),
            'sections' => StaffAccess::sections(),
        ])->layoutData(['title' => 'کارکنان و دسترسی‌ها']);
    }
}
