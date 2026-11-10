<div class="c360 c360-directory">
    <div class="c360-heading">
        <div>
            <div class="c360-crumb">ویرا / <span>مشتریان</span></div>
            <h1>مشتریان</h1>
            <p>هر مشتری، یک پرونده کامل؛ از اطلاعات تماس تا آخرین خرید.</p>
        </div>
        @can('customers.manage')
            <button type="button" class="c360-button c360-button-primary" wire:click="create"><i data-lucide="user-plus"></i> مشتری جدید</button>
        @endcan
    </div>
    <x-workspace.feedback :notice="$notice" />
    @if($showForm)
        <section class="c360-panel c360-editor" wire:key="customer-form-{{ $editingId ?? 'new' }}">
            <div class="c360-panel-title"><h2>{{ $editingId ? 'ویرایش مشتری' : 'مشتری جدید' }}</h2><button type="button" class="c360-link-button" wire:click="$set('showForm', false)">بستن <i data-lucide="x"></i></button></div>
            <form wire:submit="save">
                <div class="c360-form-grid">
                    <label class="c360-field">نام مشتری *<input type="text" wire:model="form.name" maxlength="120" required autocomplete="name" placeholder="نام و نام خانوادگی یا نام مجموعه">@error('form.name')<small class="c360-error">{{ $message }}</small>@enderror</label>
                    <label class="c360-field">شماره موبایل اصلی *<input type="tel" wire:model="form.mobile" dir="ltr" maxlength="11" required placeholder="09120000000" autocomplete="tel">@error('form.mobile')<small class="c360-error">{{ $message }}</small>@enderror</label>
                    <label class="c360-field">نوع مشتری<select wire:model="form.customer_type"><option value="person">شخص حقیقی</option><option value="business">کسب‌وکار / حقوقی</option></select>@error('form.customer_type')<small class="c360-error">{{ $message }}</small>@enderror</label>
                    <label class="c360-field">ایمیل (اختیاری)<input type="email" wire:model="form.email" maxlength="190" dir="ltr" autocomplete="email" placeholder="name@example.com">@error('form.email')<small class="c360-error">{{ $message }}</small>@enderror</label>
                    <label class="c360-field">شهر<input wire:model="form.city" maxlength="100" autocomplete="address-level2">@error('form.city')<small class="c360-error">{{ $message }}</small>@enderror</label>
                    <label class="c360-field">نشانی<input wire:model="form.address" maxlength="1000" autocomplete="street-address">@error('form.address')<small class="c360-error">{{ $message }}</small>@enderror</label>
                    <label class="c360-field c360-field-wide">یادداشت داخلی<textarea wire:model="form.note" rows="2" maxlength="2000" placeholder="اطلاعاتی که فقط کارکنان مجاز می‌بینند"></textarea>@error('form.note')<small class="c360-error">{{ $message }}</small>@enderror</label>
                </div>
                <div class="c360-form-actions"><button class="c360-button c360-button-primary" wire:loading.attr="disabled" wire:target="save">ذخیره مشتری</button><button type="button" class="c360-button c360-button-outline" wire:click="$set('showForm', false)">انصراف</button></div>
            </form>
        </section>
    @endif
    <div class="c360-info-banner">
        <span class="c360-info-icon"><i data-lucide="users-round"></i></span>
        <span><strong>{{ number_format($count) }}</strong> پرونده مشتری ثبت شده است.</span>
        <span class="c360-info-tip">برای دیدن خریدها، شماره‌های دیگر و پیگیری‌ها، پرونده هر مشتری را باز کنید.</span>
    </div>
    <section class="c360-panel c360-list-panel">
        <div class="c360-list-tools">
            <label class="c360-search"><i data-lucide="search"></i><input type="search" wire:model.live.debounce.350ms="search" placeholder="جست‌وجو با نام یا هر شماره تماس..." maxlength="120" aria-label="جست‌وجوی مشتریان"></label>
            @if($canSales)
                <div class="c360-filters" aria-label="فیلتر سابقه خرید">
                    <button type="button" wire:click="$set('filter', 'all')" class="{{ $filter === 'all' ? 'is-current' : '' }}">همه</button>
                    <button type="button" wire:click="$set('filter', 'with_sales')" class="{{ $filter === 'with_sales' ? 'is-current' : '' }}">دارای خرید</button>
                    <button type="button" wire:click="$set('filter', 'without_sales')" class="{{ $filter === 'without_sales' ? 'is-current' : '' }}">بدون خرید</button>
                </div>
            @endif
            <span class="c360-loading" wire:loading wire:target="search,filter">در حال جست‌وجو…</span>
        </div>
        @if($customers->count())
            <div class="c360-table-scroll">
                <table class="c360-table">
                    <thead><tr><th>مشتری</th><th>شماره اصلی</th>@if($canSales)<th>آخرین خرید</th>@endif<th>شهر</th><th>پرونده</th></tr></thead>
                    <tbody>
                        @foreach($customers as $customer)
                            <tr wire:key="customer-{{ $customer->id }}">
                                <td>
                                    <a href="{{ route('panel.customers.show', $customer->id) }}" class="c360-person">
                                        <span class="c360-person-avatar">{{ mb_substr($customer->name, 0, 1) }}</span>
                                        <span><strong>{{ $customer->name }}</strong><small>{{ $customer->customer_type === 'business' ? 'مجموعه / کسب‌وکار' : 'شخص حقیقی' }}</small></span>
                                    </a>
                                </td>
                                <td><bdi class="c360-mobile">{{ $customer->mobile }}</bdi>@if($customer->phone_numbers_count)<small class="c360-number-more">+{{ $customer->phone_numbers_count }} شماره دیگر</small>@endif</td>
                                @if($canSales)<td>@if($customer->last_sale_at)<bdi>{{ \Morilog\Jalali\Jalalian::fromCarbon(\Illuminate\Support\Carbon::parse($customer->last_sale_at)->timezone('Asia/Tehran'))->format('Y/m/d') }}</bdi>@else<span class="c360-muted">هنوز خریدی ندارد</span>@endif</td>@endif
                                <td>{{ $customer->city ?: '—' }}</td>
                                <td class="c360-row-actions"><a href="{{ route('panel.customers.show', $customer->id) }}" class="c360-link">مشاهده پرونده <i data-lucide="arrow-left"></i></a>@can('customers.manage')<button class="c360-edit-icon" type="button" wire:click="edit({{ $customer->id }})" aria-label="ویرایش {{ $customer->name }}"><i data-lucide="pencil"></i></button>@endcan</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="c360-footer">{{ $customers->links() }}</div>
        @else
            <div class="c360-empty"><i data-lucide="search-x"></i><h2>{{ $search !== '' ? 'مشتری پیدا نشد' : 'هنوز مشتری ثبت نشده' }}</h2><p>نام یا شماره دیگری جست‌وجو کنید یا اولین مشتری را اضافه کنید.</p>@if($search !== '')<button class="c360-button c360-button-outline" wire:click="$set('search', '')">پاک کردن جست‌وجو</button>@endif</div>
        @endif
    </section>
</div>
