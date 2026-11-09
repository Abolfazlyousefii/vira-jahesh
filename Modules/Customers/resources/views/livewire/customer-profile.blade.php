<div class="v-workspace">
    <div class="s-heading"><div><h1>{{ $customer->name }}</h1><p class="s-muted"><bdi>{{ $customer->mobile }}</bdi>@if($customer->city) · {{ $customer->city }}@endif</p></div><a class="s-btn" href="{{ route('panel.customers') }}">فهرست مشتریان</a></div>
    <x-workspace.feedback :notice="$notice" />
    <details class="s-panel v-compact-details" wire:ignore.self><summary>اطلاعات تکمیلی مشتری <small class="s-muted">· پرونده #{{ $customer->id }}</small></summary>
        <dl class="v-profile-details"><div><dt>نشانی</dt><dd>{{ $customer->address ?: 'ثبت نشده' }}</dd></div><div><dt>یادداشت داخلی</dt><dd>{{ $customer->note ?: 'یادداشتی ثبت نشده' }}</dd></div></dl>
    </details>
    @if($summary)
        <div class="v-report-metrics">
            <section class="s-panel"><h2>کل خرید ثبت‌شده</h2><div class="v-metric">{{ number_format($summary['gross']) }} <small>تومان</small></div><p class="s-muted">{{ number_format($summary['sale_count']) }} فاکتور</p></section>
            <section class="s-panel"><h2>بازپرداخت ثبت‌شده</h2><div class="v-metric">{{ number_format($summary['refunds']) }} <small>تومان</small></div><p class="s-muted">{{ number_format($summary['return_count']) }} مرجوعی</p></section>
            <section class="s-panel"><h2>خرید پس از مرجوعی</h2><div class="v-metric">{{ number_format($summary['net']) }} <small>تومان</small></div><p class="s-muted">جمع کل سوابق؛ ماندهٔ بدهی نیست</p></section>
        </div>
        @if($summary['last_sale_at'])<p class="v-profile-caption">آخرین خرید: {{ \Morilog\Jalali\Jalalian::fromCarbon($summary['last_sale_at']->timezone('Asia/Tehran'))->format('Y/m/d H:i') }}</p>@endif
    @endif
    @if($summaryError)<p class="v-error-summary" role="status">{{ $summaryError }}</p>@endif
    @if($selectedSale)
        <section class="s-panel" wire:key="profile-receipt-{{ $selectedSale->id }}" tabindex="-1" aria-labelledby="profile-receipt-title" x-init="$nextTick(() => { $el.focus(); $el.scrollIntoView({block:'start'}); })">
            <div class="s-panelhead"><h2 id="profile-receipt-title">فاکتور #{{ $selectedSale->id }}</h2><button class="s-btn" type="button" wire:click="closeReceipt">بستن فاکتور</button></div>
            <p class="v-help">نام در زمان فروش: {{ $selectedSale->customer_name }} · {{ \Morilog\Jalali\Jalalian::fromCarbon($selectedSale->created_at->timezone('Asia/Tehran'))->format('Y/m/d H:i') }} · {{ $selectedSale->channel === 'pos' ? 'صندوق' : 'فروش مستقیم' }} · {{ $selectedSale->payment_method === 'cash' ? 'دریافت نقدی' : 'کارت — ثبت دستی' }}</p>
            <div class="v-table-wrap"><table class="v-table v-responsive-table"><thead><tr><th>کالا / کد تاریخی</th><th>تعداد</th><th>فی — تومان</th><th>مرجوع‌شده</th><th>جمع فروش — تومان</th></tr></thead><tbody>@foreach($selectedSale->items as $line)<tr><td data-label="کالا / کد تاریخی">{{ $line->name }}<bdi class="v-subtext">{{ $line->sku }}</bdi></td><td data-label="تعداد">{{ number_format($line->quantity) }}</td><td data-label="فی — تومان">{{ number_format($line->unit_price_toman) }}</td><td data-label="مرجوع‌شده">{{ number_format($line->returned_quantity) }}</td><td data-label="جمع فروش — تومان">{{ number_format($line->quantity * $line->unit_price_toman) }}</td></tr>@endforeach</tbody></table></div>
            <p class="v-help">مبلغ فروش اصلی: {{ number_format($selectedSale->total_toman) }} تومان</p>
            @foreach($selectedSale->items as $line)@foreach($line->returns as $return)<p class="v-help">مرجوعی #{{ $return->id }} · {{ $line->name }} · {{ $return->quantity }} عدد · {{ number_format($return->refund_toman) }} تومان · {{ $return->reason }} · {{ $return->user->name }}</p>@endforeach @endforeach
            @can('sales.manage')<a class="s-btn" href="{{ route('panel.sales') }}">رفتن به فروش و مرجوعی</a>@endcan
        </section>
    @endif
    @if($canSales)
        <section class="s-panel" id="customer-purchases"><div class="s-panelhead"><h2>خریدها و فاکتورها</h2><span class="s-muted">{{ number_format($purchases->total()) }} فاکتور</span></div>
            @if($purchases->count())<div class="v-table-wrap"><table class="v-table v-responsive-table"><thead><tr><th>فاکتور / تاریخ</th><th>کانال</th><th>فروش اصلی — تومان</th><th>جزئیات</th></tr></thead><tbody>@foreach($purchases as $sale)<tr wire:key="profile-sale-{{ $sale->id }}"><td data-label="فاکتور / تاریخ"><strong>#{{ $sale->id }}</strong><bdi class="v-subtext">{{ \Morilog\Jalali\Jalalian::fromCarbon($sale->created_at->timezone('Asia/Tehran'))->format('Y/m/d H:i') }}</bdi></td><td data-label="کانال">{{ $sale->channel === 'pos' ? 'صندوق' : 'مستقیم' }}</td><td data-label="فروش اصلی — تومان">{{ number_format($sale->total_toman) }}</td><td data-label="جزئیات"><button class="s-btn" wire:click="selectReceipt({{ $sale->id }})" aria-label="مشاهدهٔ فاکتور {{ $sale->id }}">مشاهدهٔ فاکتور</button></td></tr>@endforeach</tbody></table></div><div class="v-pagination">{{ $purchases->links() }}</div>@else<x-workspace.empty title="هنوز خریدی برای این مشتری ثبت نشده" description="فروش‌های مشتری گذری به این پرونده وصل نمی‌شوند؛ هنگام فروش، مشتری را انتخاب کنید." icon="receipt-text" />@endif
        </section>
        <details class="s-panel v-compact-details" wire:ignore.self><summary>مرجوعی‌ها و بازپرداخت‌ها <small class="s-muted">· {{ number_format($refunds->total()) }} سند</small></summary>
            @if($refunds->count())<div class="v-table-wrap"><table class="v-table v-responsive-table"><thead><tr><th>سند / کالا</th><th>تعداد / روش</th><th>بازپرداخت — تومان</th><th>دلیل / تاریخ</th></tr></thead><tbody>@foreach($refunds as $return)<tr wire:key="profile-refund-{{ $return->id }}"><td data-label="سند / کالا">مرجوعی #{{ $return->id }} · فاکتور #{{ $return->item->sale_id }}<span class="v-subtext">{{ $return->item->name }}</span></td><td data-label="تعداد / روش">{{ $return->quantity }} عدد<span class="v-subtext">{{ $return->refund_method === 'cash' ? 'نقدی' : 'کارت — ثبت دستی' }}</span></td><td data-label="بازپرداخت — تومان">{{ number_format($return->refund_toman) }}</td><td data-label="دلیل / تاریخ">{{ $return->reason }}<span class="v-subtext">{{ \Morilog\Jalali\Jalalian::fromCarbon($return->created_at->timezone('Asia/Tehran'))->format('Y/m/d H:i') }} · {{ $return->user->name }}</span></td></tr>@endforeach</tbody></table></div><div class="v-pagination">{{ $refunds->links() }}</div>@else<p class="v-help">مرجوعی ثبت‌شده‌ای برای این مشتری وجود ندارد.</p>@endif
        </details>
    @endif
    @if($canCrm)
        <details class="s-panel v-compact-details" id="customer-followups" wire:ignore.self><summary>پیگیری‌های این مشتری <small class="s-muted">· {{ number_format($followUpSummary['open']) }} باز · {{ number_format($followUpSummary['overdue']) }} عقب‌افتاده</small></summary>
            @can('crm.manage')<details class="v-customer-select" @if($errors->has('followUpForm.*')) open @endif><summary>+ پیگیری جدید برای همین مشتری</summary><form wire:submit="saveFollowUp">
                <div class="v-form-grid"><x-workspace.field label="عنوان پیگیری *" name="followUpForm.title"><input wire:model="followUpForm.title" maxlength="160" required></x-workspace.field><x-workspace.field label="موعد شمسی پیگیری *" name="followUpForm.due_date"><input wire:model="followUpForm.due_date" placeholder="۱۴۰۵/۰۸/۲۰" dir="ltr" maxlength="10" required></x-workspace.field></div>
                <x-workspace.field label="یادداشت پیگیری" name="followUpForm.note"><textarea wire:model="followUpForm.note" maxlength="3000" rows="2"></textarea></x-workspace.field><button class="s-btn s-mainbtn" wire:loading.attr="disabled" wire:target="saveFollowUp">ذخیرهٔ پیگیری</button>
            </form></details>@endcan
            <x-workspace.field label="وضعیت پیگیری" name="followUpFilter"><select wire:model.live="followUpFilter"><option value="open">باز</option><option value="done">انجام‌شده</option><option value="all">همه</option></select></x-workspace.field>
            @forelse($followUps as $followUp)<article class="v-followup" wire:key="profile-followup-{{ $followUp->id }}"><div class="v-followup-copy"><h3>{{ $followUp->title }}</h3><p class="v-help">{{ \Morilog\Jalali\Jalalian::fromCarbon($followUp->due_at->timezone('Asia/Tehran'))->format('Y/m/d') }} · {{ $followUp->status === 'done' ? 'انجام‌شده' : ($followUp->due_at->lt(now('Asia/Tehran')->startOfDay()) ? 'عقب‌افتاده' : 'باز') }}</p>@if($followUp->note)<p class="v-note">{{ $followUp->note }}</p>@endif</div>@if($followUp->status === 'open')@can('crm.manage')<button class="s-btn" wire:click="completeFollowUp({{ $followUp->id }})" aria-label="انجام پیگیری {{ $followUp->title }}">انجام شد</button>@endcan @endif</article>@empty<p class="v-help">پیگیری‌ای با این وضعیت برای این مشتری ثبت نشده است.</p>@endforelse
            <div class="v-pagination">{{ $followUps->links() }}</div>
        </details>
    @endif
</div>
