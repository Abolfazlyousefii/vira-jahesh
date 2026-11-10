<div class="c360 c360-profile">
    <div class="c360-heading c360-profile-heading">
        <div>
            <div class="c360-crumb"><a href="{{ route('panel.customers') }}">مشتریان</a> / <span>پرونده مشتری</span></div>
            <div class="c360-name-line"><span class="c360-profile-avatar">{{ mb_substr($customer->name, 0, 1) }}</span><div><h1>{{ $customer->name }}</h1><p>{{ $customer->city ?: 'شهر ثبت نشده' }} · {{ $customer->customer_type === 'business' ? 'مشتری حقوقی' : 'مشتری حقیقی' }} · پرونده #{{ $customer->id }}</p></div></div>
        </div>
        <div class="c360-heading-actions">
            <a class="c360-button c360-button-outline" href="{{ route('panel.customers') }}"><i data-lucide="arrow-right"></i> همه مشتریان</a>
            @if($canCrm)@can('crm.manage')<button type="button" class="c360-button c360-button-primary" wire:click="setTab('followups')"><i data-lucide="calendar-plus"></i> پیگیری جدید</button>@endcan @endif
        </div>
    </div>
    <x-workspace.feedback :notice="$notice" />
    @if($summaryError)<div class="c360-alert" role="status">{{ $summaryError }}</div>@endif
    @if($yearlyError)<div class="c360-alert" role="status">گزارش سالانه در دسترس نیست. {{ $yearlyError }}</div>@endif

    <div class="c360-profile-metrics">
        @if($summary)
            <div class="c360-stat c360-accent-teal"><span class="c360-stat-icon"><i data-lucide="wallet"></i></span><div><small>خرید خالص ثبت‌شده</small><strong>{{ number_format($summary['net']) }} <em>تومان</em></strong><span>کل سابقه، پس از مرجوعی · مانده بدهی نیست</span></div></div>
            <div class="c360-stat c360-accent-blue"><span class="c360-stat-icon"><i data-lucide="receipt-text"></i></span><div><small>تعداد فاکتورها</small><strong>{{ number_format($summary['sale_count']) }} <em>فاکتور</em></strong><span>{{ number_format($summary['return_count']) }} سند مرجوعی</span></div></div>
            <div class="c360-stat c360-accent-purple"><span class="c360-stat-icon"><i data-lucide="calendar-days"></i></span><div><small>آخرین خرید</small><strong class="c360-stat-date">{{ $summary['last_sale_at'] ? \Morilog\Jalali\Jalalian::fromCarbon($summary['last_sale_at']->copy()->timezone('Asia/Tehran'))->format('Y/m/d') : '—' }}</strong><span>{{ $summary['last_sale_at'] ? 'آخرین فاکتور ثبت‌شده' : 'هنوز خریدی ثبت نشده است' }}</span></div></div>
        @else
            <div class="c360-stat c360-accent-blue"><span class="c360-stat-icon"><i data-lucide="contact-round"></i></span><div><small>پرونده مشتری</small><strong>#{{ $customer->id }}</strong><span>اطلاعات تماس و مشخصات</span></div></div>
        @endif
    </div>

    @if($selectedSale)
        <section class="c360-panel c360-receipt" wire:key="profile-receipt-{{ $selectedSale->id }}" tabindex="-1" x-init="$nextTick(() => $el.focus())">
            <div class="c360-panel-title"><div><h2>فاکتور #{{ $selectedSale->id }}</h2><small>{{ $selectedSale->customer_name }} · {{ \Morilog\Jalali\Jalalian::fromCarbon($selectedSale->created_at->copy()->timezone('Asia/Tehran'))->format('Y/m/d H:i') }}</small></div><button class="c360-link-button" type="button" wire:click="closeReceipt">بستن <i data-lucide="x"></i></button></div>
            <div class="c360-table-scroll"><table class="c360-table"><thead><tr><th>کالا / کد تاریخی</th><th>تعداد</th><th>قیمت واحد</th><th>مرجوع‌شده</th><th>جمع</th></tr></thead><tbody>
                @foreach($selectedSale->items as $line)
                    <tr><td>{{ $line->name }}<small class="c360-number-more">{{ $line->sku }}</small></td><td>{{ number_format($line->quantity) }}</td><td>{{ number_format($line->unit_price_toman) }}</td><td>{{ number_format($line->returned_quantity) }}</td><td>{{ number_format($line->quantity * $line->unit_price_toman) }} تومان</td></tr>
                @endforeach
            </tbody></table></div>
            <p class="c360-receipt-total">مبلغ فروش اصلی: <strong>{{ number_format($selectedSale->total_toman) }} تومان</strong> · {{ $selectedSale->channel === 'pos' ? 'صندوق' : 'فروش مستقیم' }} · {{ $selectedSale->payment_method === 'cash' ? 'دریافت نقدی' : 'دریافت با کارت — ثبت دستی' }}</p>
            @foreach($selectedSale->items as $line)@foreach($line->returns as $return)<p class="c360-refund-line">مرجوعی #{{ $return->id }} · {{ $line->name }} · {{ number_format($return->refund_toman) }} تومان · {{ $return->reason }} · {{ $return->user->name }}</p>@endforeach @endforeach
        </section>
    @endif

    <div class="c360-profile-grid">
        <div class="c360-profile-content">
            <div class="c360-panel c360-tabs-panel">
                <nav class="c360-tabs" aria-label="بخش‌های پرونده">
                    <button type="button" class="{{ $activeTab === 'overview' ? 'is-current' : '' }}" wire:click="setTab('overview')">نمای کلی</button>
                    @if($canSales)<button type="button" class="{{ $activeTab === 'purchases' ? 'is-current' : '' }}" wire:click="setTab('purchases')">خریدها و فاکتورها</button>@endif
                    @if($canCrm)<button type="button" class="{{ $activeTab === 'followups' ? 'is-current' : '' }}" wire:click="setTab('followups')">پیگیری و ارتباطات</button>@endif
                    <button type="button" class="{{ $activeTab === 'details' ? 'is-current' : '' }}" wire:click="setTab('details')">اطلاعات تکمیلی</button>
                </nav>
                @if($activeTab === 'overview')
                    @if($yearly)
                        <div class="c360-chart-head"><div><h2>گردش خرید در سال {{ $yearly['year'] }}</h2><p>فروش و بازپرداخت‌ها در تاریخ ثبت خودشان محاسبه می‌شوند.</p></div><div class="c360-year-switch">@foreach(range(max(1390, \Morilog\Jalali\Jalalian::fromCarbon(now('Asia/Tehran'))->getYear()-2), \Morilog\Jalali\Jalalian::fromCarbon(now('Asia/Tehran'))->getYear()) as $option)<button type="button" class="{{ $year === $option ? 'is-current' : '' }}" wire:click="setYear({{ $option }})">{{ $option }}</button>@endforeach</div></div>
                        @php $persianMonths = ['فروردین','اردیبهشت','خرداد','تیر','مرداد','شهریور','مهر','آبان','آذر','دی','بهمن','اسفند']; $maxChart = max(1, ...array_map(fn ($m) => max($m['gross'], $m['refunds']), $yearly['months'])); @endphp
                        <div class="c360-chart" role="img" aria-label="نمودار ماهانه فروش و مرجوعی مشتری در سال {{ $year }}">
                            @foreach($yearly['months'] as $index => $month)
                                <div class="c360-month" title="{{ $persianMonths[$index] }}: فروش {{ number_format($month['gross']) }} و بازپرداخت {{ number_format($month['refunds']) }} تومان"><div class="c360-month-bars"><span class="c360-month-sale" style="height: {{ max($month['gross'] ? 4 : 2, round(100 * $month['gross'] / $maxChart)) }}%"></span><span class="c360-month-refund" style="height: {{ max($month['refunds'] ? 4 : 2, round(100 * $month['refunds'] / $maxChart)) }}%"></span></div><span>{{ $persianMonths[$index] }}</span></div>
                            @endforeach
                        </div>
                        <div class="c360-chart-legend"><span><i class="c360-dot-teal"></i> فروش ثبت‌شده</span><span><i class="c360-dot-coral"></i> بازپرداخت ثبت‌شده</span></div>
                        <div class="c360-chart-totals"><div><small>فروش سال</small><strong>{{ number_format($yearly['gross']) }} تومان</strong></div><div><small>بازپرداخت سال</small><strong>{{ number_format($yearly['refunds']) }} تومان</strong></div><div><small>خالص گردش سال</small><strong>{{ number_format($yearly['net']) }} تومان</strong></div></div>
                    @elseif($canSales)
                        <div class="c360-empty-small">هنوز گزارشی برای نمایش نیست.</div>
                    @endif
                    @if($canSales)
                        <div class="c360-section-head"><h2>خریدها و فاکتورها</h2><button type="button" class="c360-link-button" wire:click="setTab('purchases')">مشاهده همه <i data-lucide="arrow-left"></i></button></div>
                        @if($purchases->count())<div class="c360-mini-list">@foreach($purchases->take(3) as $sale)<div class="c360-mini-row"><span class="c360-mini-icon c360-accent-blue"><i data-lucide="receipt-text"></i></span><div><strong>فاکتور #{{ $sale->id }}</strong><small>{{ \Morilog\Jalali\Jalalian::fromCarbon($sale->created_at->copy()->timezone('Asia/Tehran'))->format('Y/m/d') }} · {{ $sale->channel === 'pos' ? 'صندوق' : 'فروش مستقیم' }}</small></div><bdi>{{ number_format($sale->total_toman) }} تومان</bdi><button class="c360-link" type="button" wire:click="selectReceipt({{ $sale->id }})">جزئیات</button></div>@endforeach</div>@else<div class="c360-empty-small">هنوز خریدی ثبت نشده است.</div>@endif
                    @endif
                @elseif($activeTab === 'purchases' && $canSales)
                    <div class="c360-section-head"><div><h2>خریدها و فاکتورها</h2><p>تمام فاکتورهای ثبت‌شده برای این مشتری</p></div><span class="c360-tag">{{ number_format($purchases->total()) }} فاکتور</span></div>
                    @if($purchases->count())
                        <div class="c360-table-scroll"><table class="c360-table"><thead><tr><th>فاکتور / تاریخ</th><th>کانال</th><th>مبلغ اصلی</th><th>جزئیات</th></tr></thead><tbody>@foreach($purchases as $sale)<tr wire:key="customer-sale-{{ $sale->id }}"><td>فاکتور #{{ $sale->id }}<small class="c360-number-more">{{ \Morilog\Jalali\Jalalian::fromCarbon($sale->created_at->copy()->timezone('Asia/Tehran'))->format('Y/m/d H:i') }}</small></td><td>{{ $sale->channel === 'pos' ? 'صندوق' : 'مستقیم' }}</td><td>{{ number_format($sale->total_toman) }} تومان</td><td><button class="c360-link" type="button" wire:click="selectReceipt({{ $sale->id }})">مشاهده فاکتور</button></td></tr>@endforeach</tbody></table></div><div class="c360-footer">{{ $purchases->links() }}</div>
                    @else<div class="c360-empty-small">فاکتوری برای این مشتری ثبت نشده است.</div>@endif
                    <details class="c360-collapse"><summary>مرجوعی‌ها و بازپرداخت‌ها · {{ number_format($refunds->total()) }} سند</summary>@if($refunds->count())<div class="c360-table-scroll"><table class="c360-table"><thead><tr><th>سند / کالا</th><th>تعداد</th><th>بازپرداخت</th><th>دلیل و زمان</th></tr></thead><tbody>@foreach($refunds as $return)<tr wire:key="refund-{{ $return->id }}"><td>مرجوعی #{{ $return->id }} · فاکتور #{{ $return->item->sale_id }}<small class="c360-number-more">{{ $return->item->name }}</small></td><td>{{ $return->quantity }}</td><td>{{ number_format($return->refund_toman) }} تومان</td><td>{{ $return->reason }}<small class="c360-number-more">{{ \Morilog\Jalali\Jalalian::fromCarbon($return->created_at->copy()->timezone('Asia/Tehran'))->format('Y/m/d') }} · {{ $return->user->name }}</small></td></tr>@endforeach</tbody></table></div><div class="c360-footer">{{ $refunds->links() }}</div>@else<p>مرجوعی ثبت‌شده‌ای وجود ندارد.</p>@endif</details>
                @elseif($activeTab === 'followups' && $canCrm)
                    <div class="c360-section-head"><div><h2>پیگیری و ارتباطات</h2><p>{{ $followUpSummary['open'] }} باز · {{ $followUpSummary['overdue'] }} سررسیدشده</p></div></div>
                    @can('crm.manage')
                        <details class="c360-collapse" open><summary>+ ثبت پیگیری جدید برای همین مشتری</summary><form wire:submit="saveFollowUp" class="c360-form-grid"><label class="c360-field">عنوان پیگیری *<input wire:model="followUpForm.title" maxlength="160" required>@error('followUpForm.title')<small class="c360-error">{{ $message }}</small>@enderror</label><label class="c360-field">موعد شمسی *<input wire:model="followUpForm.due_date" dir="ltr" maxlength="10" placeholder="۱۴۰۵/۰۸/۲۰" required>@error('followUpForm.due_date')<small class="c360-error">{{ $message }}</small>@enderror</label><label class="c360-field c360-field-wide">یادداشت<textarea wire:model="followUpForm.note" rows="2" maxlength="3000"></textarea></label><div class="c360-field-wide"><button class="c360-button c360-button-primary" wire:loading.attr="disabled" wire:target="saveFollowUp">ذخیره پیگیری</button></div></form></details>
                    @endcan
                    <label class="c360-field c360-select-inline">وضعیت پیگیری<select wire:model.live="followUpFilter"><option value="open">باز</option><option value="done">انجام‌شده</option><option value="all">همه</option></select></label>
                    <div class="c360-mini-list">@forelse($followUps as $followUp)<div class="c360-mini-row" wire:key="followup-{{ $followUp->id }}"><span class="c360-mini-icon c360-accent-purple"><i data-lucide="message-circle"></i></span><div><strong>{{ $followUp->title }}</strong><small>{{ \Morilog\Jalali\Jalalian::fromCarbon($followUp->due_at->copy()->timezone('Asia/Tehran'))->format('Y/m/d') }} · {{ $followUp->status === 'done' ? 'انجام‌شده' : ($followUp->due_at->lt(now('Asia/Tehran')->startOfDay()) ? 'عقب‌افتاده' : 'باز') }}</small>@if($followUp->note)<p>{{ $followUp->note }}</p>@endif</div>@if($followUp->status === 'open')@can('crm.manage')<button class="c360-link" wire:click="completeFollowUp({{ $followUp->id }})">انجام شد</button>@endcan @endif</div>@empty<div class="c360-empty-small">پیگیری‌ای با این وضعیت ثبت نشده است.</div>@endforelse</div><div class="c360-footer">{{ $followUps->links() }}</div>
                @elseif($activeTab === 'details')
                    <div class="c360-section-head"><h2>اطلاعات تکمیلی مشتری</h2></div>
                    <dl class="c360-details"><div><dt>نام</dt><dd>{{ $customer->name }}</dd></div><div><dt>نوع مشتری</dt><dd>{{ $customer->customer_type === 'business' ? 'حقوقی / مجموعه' : 'حقیقی' }}</dd></div><div><dt>ایمیل</dt><dd><bdi>{{ $customer->email ?: 'ثبت نشده' }}</bdi></dd></div><div><dt>شهر</dt><dd>{{ $customer->city ?: 'ثبت نشده' }}</dd></div><div><dt>نشانی</dt><dd>{{ $customer->address ?: 'ثبت نشده' }}</dd></div><div><dt>یادداشت داخلی</dt><dd>{{ $customer->note ?: 'یادداشتی ثبت نشده' }}</dd></div></dl>
                @endif
            </div>
        </div>
        <aside class="c360-profile-side">
            <section class="c360-panel c360-side-card">
                <div class="c360-panel-title"><h2>اطلاعات تماس</h2><i data-lucide="contact-round"></i></div>
                <div class="c360-contact-row"><span class="c360-contact-icon c360-accent-teal"><i data-lucide="smartphone"></i></span><div><small>شماره اصلی</small><strong dir="ltr">{{ $customer->mobile }}</strong></div><a href="tel:{{ $customer->mobile }}" title="تماس با شماره اصلی" aria-label="تماس با شماره اصلی"><i data-lucide="phone"></i></a></div>
                @foreach($customer->phoneNumbers as $phone)
                    <div class="c360-contact-row" wire:key="phone-{{ $phone->id }}"><span class="c360-contact-icon c360-accent-blue"><i data-lucide="phone"></i></span><div><small>{{ ['mobile' => 'موبایل دیگر', 'landline' => 'تلفن ثابت', 'work' => 'شماره کاری', 'other' => 'شماره دیگر'][$phone->type] ?? 'شماره دیگر' }}{{ $phone->label ? ' · '.$phone->label : '' }}</small><strong dir="ltr">{{ $phone->number_normalized }}</strong></div>@can('customers.manage')<button type="button" wire:click="editPhone({{ $phone->id }})" aria-label="ویرایش شماره" title="ویرایش"><i data-lucide="pencil"></i></button><button type="button" wire:click="removePhone({{ $phone->id }})" wire:confirm="شماره فرعی حذف شود؟" aria-label="حذف شماره" title="حذف"><i data-lucide="trash-2"></i></button>@endcan</div>
                @endforeach
                @if($customer->city)<div class="c360-contact-row"><span class="c360-contact-icon c360-accent-amber"><i data-lucide="map-pin"></i></span><div><small>شهر</small><strong>{{ $customer->city }}</strong></div></div>@endif
                @can('customers.manage')
                    <details class="c360-phone-editor" @if($editingPhoneId) open @endif><summary><i data-lucide="plus"></i> {{ $editingPhoneId ? 'ویرایش شماره تماس' : 'افزودن شماره دیگر' }}</summary><form wire:submit="savePhone"><label class="c360-field">نوع شماره<select wire:model="phoneForm.type"><option value="mobile">موبایل</option><option value="landline">ثابت</option><option value="work">کاری</option><option value="other">دیگر</option></select>@error('phoneForm.type')<small class="c360-error">{{ $message }}</small>@enderror</label><label class="c360-field">شماره تماس *<input type="tel" wire:model="phoneForm.number" dir="ltr" required maxlength="20" placeholder="0912... / 021...">@error('phoneForm.number')<small class="c360-error">{{ $message }}</small>@enderror</label><label class="c360-field">عنوان (اختیاری)<input type="text" wire:model="phoneForm.label" maxlength="60" placeholder="دفتر، منزل و..."></label><div class="c360-form-actions"><button class="c360-button c360-button-primary" wire:loading.attr="disabled" wire:target="savePhone">ذخیره شماره</button>@if($editingPhoneId)<button class="c360-button c360-button-outline" type="button" wire:click="newPhone">انصراف</button>@endif</div></form></details>
                @endcan
            </section>
            @if($canCrm)
                <section class="c360-panel c360-side-card"><div class="c360-panel-title"><h2>پیگیری‌های نزدیک</h2><button type="button" class="c360-link-button" wire:click="setTab('followups')">مشاهده همه</button></div><div class="c360-tiny-list">@forelse($followUps->take(3) as $followUp)<div><span class="c360-dot c360-dot-purple"></span><strong>{{ $followUp->title }}</strong><small>{{ \Morilog\Jalali\Jalalian::fromCarbon($followUp->due_at->copy()->timezone('Asia/Tehran'))->format('Y/m/d') }}</small></div>@empty<p class="c360-muted">پیگیری بازی ثبت نشده است.</p>@endforelse</div></section>
            @endif
            @if($canSales && count($favoriteProducts))
                <section class="c360-panel c360-side-card"><div class="c360-panel-title"><h2>کالاهای بیشتر خریداری‌شده</h2><i data-lucide="package"></i></div><div class="c360-tiny-list">@foreach($favoriteProducts as $product)<div><span class="c360-contact-icon c360-accent-amber"><i data-lucide="package"></i></span><strong>{{ $product->name }}</strong><small>{{ number_format($product->quantity) }} عدد</small></div>@endforeach</div><p class="c360-caption">آمار تعداد اقلام ثبت‌شده، پیش از کسر مرجوعی.</p></section>
            @endif
            @if($customer->note)<section class="c360-panel c360-side-card c360-note"><h2>یادداشت داخلی</h2><p>{{ $customer->note }}</p></section>@endif
        </aside>
    </div>
</div>
