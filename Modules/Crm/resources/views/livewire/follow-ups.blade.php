<div class="v-workspace">
    <div class="s-heading"><div><span class="v-eyebrow">هیچ ارتباطی فراموش نشود</span><h1>پیگیری مشتریان</h1><p class="s-muted">تماس بعدی، درخواست مشتری یا کار باقی‌مانده را یادداشت کنید.</p></div>@can('crm.manage')<button class="s-btn s-mainbtn" wire:click="create"><i data-lucide="contact-round"></i> پیگیری جدید</button>@endcan</div>
    <x-workspace.feedback :notice="$notice" />
    @if($showForm)
        <form wire:submit="save" class="s-panel v-form-panel" wire:key="followup-form-{{ $editingId ?? 'new' }}">
            <div class="s-panelhead"><h2>{{ $editingId ? 'ویرایش پیگیری' : 'افزودن پیگیری' }}</h2><button type="button" class="s-btn" wire:click="$set('showForm', false)">بستن فرم</button></div>
            <x-workspace.field label="جست‌وجوی مشتری"><input wire:model.live.debounce.300ms="customerSearch" placeholder="نام یا شمارهٔ موبایل…" maxlength="120"></x-workspace.field>
            <div class="v-form-grid">
                <x-workspace.field label="مشتری *" name="form.customer_id"><select wire:model="form.customer_id" required><option value="">انتخاب مشتری — تا ۵۰ نتیجهٔ جست‌وجو</option>@foreach($customers as $customer)<option value="{{ $customer->id }}">{{ $customer->name }} · {{ $customer->mobile }}</option>@endforeach</select></x-workspace.field>
                <x-workspace.field label="تاریخ پیگیری — شمسی *" name="form.due_date" hint="سال/ماه/روز؛ مثال: ۱۴۰۵/۰۸/۲۰"><input wire:model="form.due_date" dir="ltr" maxlength="10" required></x-workspace.field>
            </div>
            <x-workspace.field label="چه کاری باید انجام شود؟ *" name="form.title"><input wire:model="form.title" maxlength="160" placeholder="مثلاً تماس دربارهٔ سفارش جدید" required></x-workspace.field>
            <x-workspace.field label="یادداشت" name="form.note"><textarea wire:model="form.note" rows="3" maxlength="3000"></textarea></x-workspace.field>
            <div class="v-form-footer"><button class="s-btn s-mainbtn" wire:loading.attr="disabled" wire:target="save">ذخیرهٔ پیگیری</button><button type="button" class="s-btn" wire:click="$set('showForm', false)">انصراف</button></div>
        </form>
    @endif
    <section class="s-panel">
        <div class="v-toolbar"><div class="v-segments" role="group" aria-label="وضعیت پیگیری"><button class="s-btn {{ $filter === 'open' ? 's-mainbtn' : '' }}" wire:click="$set('filter', 'open')" aria-pressed="{{ $filter === 'open' ? 'true' : 'false' }}">باز</button><button class="s-btn {{ $filter === 'done' ? 's-mainbtn' : '' }}" wire:click="$set('filter', 'done')" aria-pressed="{{ $filter === 'done' ? 'true' : 'false' }}">انجام‌شده</button><button class="s-btn {{ $filter === 'all' ? 's-mainbtn' : '' }}" wire:click="$set('filter', 'all')" aria-pressed="{{ $filter === 'all' ? 'true' : 'false' }}">همه</button></div><label class="v-search"><span class="v-sr-only">جست‌وجوی پیگیری</span><input wire:model.live.debounce.300ms="search" placeholder="عنوان پیگیری یا نام مشتری…" type="search" maxlength="160"></label></div>
        <div class="v-followups">@forelse($followups as $followup)
            <article class="v-followup" wire:key="followup-{{ $followup->id }}"><span class="s-avatar"><i data-lucide="{{ $followup->status === 'done' ? 'check-check' : 'contact-round' }}"></i></span><div class="v-followup-copy"><h2>{{ $followup->title }}</h2><p class="s-muted">{{ $followup->customer->name }} · <bdi>{{ $followup->customer->mobile }}</bdi></p>@if($followup->note)<p class="v-note">{{ $followup->note }}</p>@endif</div><div><span class="s-pill {{ $followup->status === 'done' ? 'green' : ($followup->due_at->lt(now()->timezone('Asia/Tehran')->startOfDay()) ? 'orange' : '') }}">{{ $followup->status === 'done' ? 'انجام‌شده' : ($followup->due_at->lt(now()->timezone('Asia/Tehran')->startOfDay()) ? 'عقب‌افتاده · ' : '') }}{{ \Morilog\Jalali\Jalalian::fromCarbon($followup->due_at->timezone('Asia/Tehran'))->format('Y/m/d') }}</span><div class="s-controls v-row-actions">@can('crm.manage')@if($followup->status === 'open')<button class="s-btn" wire:click="complete({{ $followup->id }})">انجام شد ✓</button>@endif<button class="s-btn" wire:click="edit({{ $followup->id }})">ویرایش</button>@endcan</div></div></article>
        @empty<x-workspace.empty title="پیگیری‌ای در این فهرست نیست" description="برای مشتری، یک کار و تاریخ مشخص ثبت کنید." icon="contact-round" />@endforelse</div>
        <div class="v-pagination">{{ $followups->links() }}</div>
    </section>
</div>
