<div class="v-workspace">
    <div class="s-heading"><div><span class="v-eyebrow">هر همکار، فقط ابزارهای موردنیازش</span><h1>کارکنان و دسترسی‌ها</h1><p class="s-muted">همکار اضافه کنید و مشخص کنید کدام بخش‌ها را ببیند یا مدیریت کند.</p></div><button class="s-btn s-mainbtn" wire:click="create"><i data-lucide="user-plus"></i> همکار جدید</button></div>
    <x-workspace.feedback :notice="$notice" />
    @if($showForm)
        <form wire:submit="save" class="s-panel v-form-panel" wire:key="staff-form-{{ $editingId ?? 'new' }}">
            <div class="s-panelhead"><h2>{{ $editingId ? 'ویرایش همکار' : 'افزودن همکار' }}</h2><button type="button" class="s-btn" wire:click="$set('showForm', false)">بستن فرم</button></div>
            <div class="v-form-grid">
                <x-workspace.field label="نام همکار *" name="form.name"><input wire:model="form.name" maxlength="120" required autocomplete="off" x-init="$nextTick(() => $el.focus())"></x-workspace.field>
                <x-workspace.field label="ایمیل ورود *" name="form.email"><input type="email" wire:model="form.email" maxlength="255" dir="ltr" required autocomplete="off"></x-workspace.field>
                <x-workspace.field :label="$editingId ? 'رمز جدید (اختیاری)' : 'رمز ورود اختصاصی *'" name="password" :hint="$editingId ? 'خالی بگذارید تا رمز فعلی حفظ شود.' : 'حداقل ۱۲ نویسه؛ برای هر همکار رمز جداگانه تعیین کنید.'"><input type="password" wire:model="password" autocomplete="new-password" maxlength="128" dir="ltr" @required(!$editingId)></x-workspace.field>
                <x-workspace.field label="تکرار رمز" name="password_confirmation"><input type="password" wire:model="password_confirmation" autocomplete="new-password" maxlength="128" dir="ltr"></x-workspace.field>
            </div>
            <div class="v-checks"><label><input type="checkbox" wire:model="form.is_active"> حساب فعال باشد</label></div>
            <p class="v-help">حساب غیرفعال اجازهٔ ورود یا ادامهٔ کار ندارد؛ سوابق همکار حفظ می‌شود.</p>
            <div class="v-permission-heading"><h2><i data-lucide="shield-check"></i> دسترسی به بخش‌ها</h2><p class="s-muted">یک الگوی آماده انتخاب کنید؛ سپس در صورت نیاز تغییر دهید.</p><div class="s-controls"><button type="button" class="s-btn" wire:click="applyPreset('cashier')">صندوقدار</button><button type="button" class="s-btn" wire:click="applyPreset('warehouse')">انباردار</button><button type="button" class="s-btn" wire:click="applyPreset('viewer')">فقط مشاهده</button><button type="button" class="s-btn" wire:click="applyPreset('empty')">بدون دسترسی</button></div></div>
            <div class="v-permission-grid">@foreach($sections as $section => $label)
                <x-workspace.field :label="$label" :name="'form.access.'.$section"><select wire:model="form.access.{{ $section }}"><option value="none">دسترسی ندارد</option>@unless(in_array($section, ['settings', 'storefront'], true))<option value="view">فقط مشاهده</option>@endunless<option value="manage">مشاهده و مدیریت</option></select></x-workspace.field>
            @endforeach</div>
            <div class="v-form-footer"><button class="s-btn s-mainbtn" wire:loading.attr="disabled" wire:target="save">ذخیرهٔ همکار و دسترسی‌ها</button><button type="button" class="s-btn" wire:click="$set('showForm', false)">انصراف</button></div>
        </form>
    @endif
    <section class="s-panel"><div class="v-toolbar"><h2>همکاران مجموعه</h2><label class="v-search"><i data-lucide="search"></i><span class="v-sr-only">جست‌وجوی همکار</span><input type="search" wire:model.live.debounce.300ms="search" maxlength="120" placeholder="نام یا ایمیل همکار…"></label></div>
        <div class="v-table-wrap"><table class="v-table v-responsive-table"><thead><tr><th>همکار</th><th>ایمیل ورود</th><th>وضعیت</th><th>دسترسی</th></tr></thead><tbody>@forelse($staff as $person)<tr wire:key="staff-{{ $person->id }}"><td><div class="s-flex"><span class="s-avatar"><i data-lucide="{{ $person->hasRole('owner') ? 'shield-check' : 'user-round' }}"></i></span><strong>{{ $person->name }}</strong></div></td><td data-label="ایمیل"><bdi>{{ $person->email }}</bdi></td><td data-label="وضعیت"><span class="s-pill {{ $person->is_active ? 'green' : 'orange' }}">{{ $person->is_active ? 'فعال' : 'غیرفعال' }}</span></td><td>@if($person->hasRole('owner'))<span class="s-muted">مدیر اصلی · حساب محافظت‌شده</span>@elseif(!\Modules\Core\Support\StaffAccess::editable($person))<span class="s-muted">دسترسی سفارشی · فقط نمایش</span>@else<button class="s-btn" wire:click="edit({{ $person->id }})" aria-label="ویرایش دسترسی {{ $person->name }}">ویرایش و دسترسی</button>@endif</td></tr>@empty<tr><td colspan="4">همکاری با این نام یا ایمیل پیدا نشد.</td></tr>@endforelse</tbody></table></div><div class="v-pagination">{{ $staff->links() }}</div>
    </section>
</div>
