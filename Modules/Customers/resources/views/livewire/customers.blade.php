<div class="v-workspace">
    <div class="s-heading"><div><span class="v-eyebrow">آدم‌های کسب‌وکار شما</span><h1>مشتریان <small class="s-pill">{{ number_format($count) }} نفر</small></h1><p class="s-muted">نام و شماره کافی است؛ بقیهٔ اطلاعات را هر وقت خواستید اضافه کنید.</p></div>@can('customers.manage')<button class="s-btn s-mainbtn" wire:click="create"><i data-lucide="users"></i> مشتری جدید</button>@endcan</div>
    <x-workspace.feedback :notice="$notice" />
    @if($showForm)
        <form wire:submit="save" class="s-panel v-form-panel" wire:key="customer-form-{{ $editingId ?? 'new' }}">
            <div class="s-panelhead"><h2>{{ $editingId ? 'ویرایش مشتری' : 'افزودن مشتری' }}</h2><button type="button" class="s-btn" wire:click="$set('showForm', false)">بستن فرم</button></div>
            <div class="v-form-grid">
                <x-workspace.field label="نام مشتری *" name="form.name"><input wire:model="form.name" x-init="$nextTick(() => $el.focus())" maxlength="120" autocomplete="name" required></x-workspace.field>
                <x-workspace.field label="شمارهٔ موبایل *" name="form.mobile" hint="مثال: ۰۹۱۲۱۲۳۴۵۶۷"><input wire:model="form.mobile" inputmode="tel" dir="ltr" maxlength="11" autocomplete="tel" required></x-workspace.field>
                <x-workspace.field label="شهر" name="form.city"><input wire:model="form.city" maxlength="100" autocomplete="address-level2"></x-workspace.field>
                <x-workspace.field label="نشانی" name="form.address"><input wire:model="form.address" maxlength="1000" autocomplete="street-address"></x-workspace.field>
            </div>
            <x-workspace.field label="یادداشت داخلی" name="form.note" hint="این یادداشت در سایت نمایش داده نمی‌شود."><textarea wire:model="form.note" rows="2" maxlength="2000"></textarea></x-workspace.field>
            <div class="v-form-footer"><button class="s-btn s-mainbtn" wire:loading.attr="disabled" wire:target="save">ذخیرهٔ مشتری</button><button type="button" class="s-btn" wire:click="$set('showForm', false)">انصراف</button></div>
        </form>
    @endif
    <section class="s-panel">
        <div class="v-toolbar"><label class="v-search"><i data-lucide="users"></i><span class="v-sr-only">جست‌وجوی مشتری</span><input wire:model.live.debounce.300ms="search" placeholder="نام یا شمارهٔ موبایل…" type="search" maxlength="120"></label><span class="s-muted" wire:loading wire:target="search">در حال جست‌وجو…</span></div>
        @if($customers->count())
            <div class="v-table-wrap"><table class="v-table v-responsive-table"><thead><tr><th>مشتری</th><th>موبایل</th><th>شهر</th><th><span class="v-sr-only">عملیات</span></th></tr></thead><tbody>
                @foreach($customers as $customer)<tr wire:key="customer-{{ $customer->id }}"><td><div class="s-flex"><span class="s-avatar">{{ mb_substr($customer->name, 0, 1) }}</span><strong>{{ $customer->name }}</strong></div></td><td data-label="موبایل"><bdi>{{ $customer->mobile }}</bdi></td><td data-label="شهر">{{ $customer->city ?: '—' }}</td><td><div class="s-controls"><a class="s-btn" href="{{ route('panel.customers.show', $customer->id) }}" aria-label="پروندهٔ {{ $customer->name }}">پروندهٔ مشتری</a>@can('customers.manage')<button class="s-btn" wire:click="edit({{ $customer->id }})" aria-label="ویرایش {{ $customer->name }}">ویرایش</button>@endcan</div></td></tr>@endforeach
            </tbody></table></div>
            <div class="v-pagination">{{ $customers->links() }}</div>
        @else
            <x-workspace.empty :title="$search !== '' ? 'نتیجه‌ای پیدا نشد' : 'اولین مشتری را اضافه کنید'" :description="$search !== '' ? 'نام یا شمارهٔ دیگری جست‌وجو کنید.' : 'با ثبت نام و موبایل، پروندهٔ مشتری آماده می‌شود.'" icon="users">@if($search !== '')<button class="s-btn" wire:click="$set('search', '')">پاک کردن جست‌وجو</button>@endif</x-workspace.empty>
        @endif
    </section>
</div>
