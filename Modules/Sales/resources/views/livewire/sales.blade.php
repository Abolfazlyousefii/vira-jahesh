<div class="v-workspace">
    <div class="s-heading"><div><span class="v-eyebrow">{{ $isPos ? 'فروش سریع، چند قدم روشن' : 'یک سابقه برای همهٔ فروش‌ها' }}</span><h1>{{ $isPos ? 'صندوق فروش' : 'فروش و فاکتورها' }}</h1><p class="s-muted">کالا را انتخاب کنید، دریافت وجه را بررسی کنید و فروش را ثبت کنید.</p></div>@if($canWrite)<button class="s-btn s-mainbtn" wire:click="newSale"><i data-lucide="receipt-text"></i> فروش جدید</button>@endif</div>
    <x-workspace.feedback :notice="$notice" />
    @if($showForm && $canWrite)
        <div class="v-sale-layout">
            <section class="s-panel">
                <div class="s-panelhead"><h2><span class="v-step">۱</span> انتخاب کالا</h2><small class="s-muted">جست‌وجو و افزودن</small></div>
                <label class="v-search"><i data-lucide="scan-line"></i><span class="v-sr-only">جست‌وجوی نام، کد یا بارکد کالا</span><input wire:model.live.debounce.200ms="search" placeholder="نام، کد یا بارکد کالا…" type="search" maxlength="160" x-init="$nextTick(() => $el.focus())"></label>
                <div class="v-product-picker">@forelse($products as $product)
                    <button type="button" class="v-product-choice" wire:click="addItem({{ $product->id }})" wire:loading.attr="disabled" wire:target="addItem" @disabled(($stock[$product->id] ?? 0) < 1) aria-label="افزودن {{ $product->name }} به فاکتور"><x-product-visual :product="$product" /><strong>{{ $product->name }}</strong><small class="s-muted"><bdi>{{ $product->sku }}</bdi></small><span class="v-number">{{ number_format($product->price_toman) }} <small>تومان</small></span><small class="s-muted">{{ ($stock[$product->id] ?? 0) > 0 ? number_format($stock[$product->id]).' عدد موجود' : 'ناموجود در این انبار' }}</small><span class="s-pill">{{ ($stock[$product->id] ?? 0) > 0 ? '+ افزودن' : 'ناموجود' }}</span></button>
                @empty<x-workspace.empty title="کالایی پیدا نشد" description="نام یا کد دیگری جست‌وجو کنید؛ کالا باید فعال باشد." />@endforelse</div>
                @if($products->count() === 24)<p class="v-help">۲۴ نتیجهٔ اول نمایش داده می‌شود؛ برای پیدا کردن کالا، جست‌وجو را دقیق‌تر کنید.</p>@endif
            </section>
            <form wire:submit="completeSale" wire:confirm="مبلغ فاکتور را دریافت کرده‌اید؟ با تأیید، فروش قطعی و موجودی کسر می‌شود." class="s-panel v-cart">
                <h2><span class="v-step">۲</span> بررسی و ثبت فروش</h2>
                <div class="v-form-grid">
                    <x-workspace.field label="انبار برداشت *" name="warehouseId"><select wire:model.live="warehouseId" required><option value="">انتخاب انبار</option>@foreach($warehouses as $warehouse)<option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>@endforeach</select></x-workspace.field>
                    <x-workspace.field label="روش دریافت وجه" name="paymentMethod"><select wire:model="paymentMethod"><option value="cash">نقدی</option><option value="card">کارت‌خوان — ثبت دستی</option></select></x-workspace.field>
                </div>
                <details class="v-customer-select" @if($customerSearch || $customerId) open @endif><summary>انتخاب مشتری <small class="s-muted">(اختیاری؛ پیش‌فرض مشتری گذری)</small></summary>
                    <x-workspace.field label="جست‌وجوی مشتری"><input wire:model.live.debounce.300ms="customerSearch" placeholder="نام یا موبایل…" maxlength="120"></x-workspace.field>
                    <x-workspace.field label="مشتری فاکتور" name="customerId"><select wire:model="customerId"><option value="">مشتری گذری</option>@foreach($customers as $customer)<option value="{{ $customer->id }}">{{ $customer->name }} · {{ $customer->mobile }}</option>@endforeach</select></x-workspace.field>
                </details>
                <div class="v-cart-lines">@forelse($items as $index => $item)
                    @php($cartProduct = $cartProducts->get($item['product_id'] ?? 0))
                    <div class="v-cart-line" wire:key="cart-{{ $item['product_id'] ?? $index }}"><div><strong>{{ $cartProduct?->name ?? 'کالا دیگر قابل فروش نیست' }}</strong><small class="v-subtext">{{ number_format($quotedPrices[$item['product_id'] ?? 0] ?? 0) }} تومان / عدد</small></div><label class="v-quantity"><span class="v-sr-only">تعداد {{ $cartProduct?->name }}</span><input wire:model.live.debounce.300ms="items.{{ $index }}.quantity" inputmode="numeric" maxlength="7" dir="ltr" required></label><button type="button" class="s-btn" wire:click="removeItem({{ $index }})" aria-label="حذف {{ $cartProduct?->name }} از فاکتور">حذف</button></div>
                @empty<p class="v-help">از فهرست کالاها، روی «افزودن» بزنید.</p>@endforelse</div>
                <div class="v-cart-total"><span>مبلغ قابل دریافت</span><strong>{{ number_format($total) }} <small>تومان</small></strong></div>
                <p class="v-help">فقط پس از دریافت وجه ثبت کنید. اتصال خودکار کارت‌خوان و پرداخت اینترنتی هنوز فعال نیست.</p>
                @error('items')<button type="button" class="s-btn v-full" wire:click="refreshPrices">به‌روزرسانی قیمت‌ها و بررسی دوباره</button>@enderror
                <button class="s-btn s-mainbtn v-full" wire:loading.attr="disabled" wire:target="completeSale" @disabled(count($items) === 0)>وجه دریافت شد؛ ثبت فروش</button>
            </form>
        </div>
    @endif
    @if($selectedSale)
        <section class="s-panel"><div class="s-panelhead"><h2>فاکتور شمارهٔ {{ $selectedSale->id }}</h2><div class="s-controls"><button type="button" class="s-btn" @click="window.print()">چاپ فاکتور</button><button type="button" class="s-btn" wire:click="closeReceipt">بستن</button></div></div>
            <div id="vira-receipt">
                <h2>{{ \Modules\Core\Models\BusinessSetting::find(1)?->name ?: 'مجموعهٔ من' }}</h2>
                <div class="v-receipt-meta"><span>فاکتور {{ $selectedSale->id }} · {{ $selectedSale->customer_name }}</span><span>{{ \Morilog\Jalali\Jalalian::fromCarbon($selectedSale->created_at->timezone('Asia/Tehran'))->format('Y/m/d H:i') }}</span><span>دریافت {{ $selectedSale->payment_method === 'cash' ? 'نقدی' : 'کارت‌خوان (ثبت دستی)' }}</span></div>
                <div class="v-table-wrap"><table class="v-table"><thead><tr><th>کالا</th><th>تعداد</th><th>فی — تومان</th><th>جمع — تومان</th></tr></thead><tbody>@foreach($selectedSale->items as $line)<tr><td>{{ $line->name }}</td><td>{{ $line->quantity }}</td><td>{{ number_format($line->unit_price_toman) }}</td><td>{{ number_format($line->unit_price_toman * $line->quantity) }}</td></tr>@endforeach</tbody></table></div>
                <div class="v-cart-total"><span>جمع فروش اصلی</span><strong>{{ number_format($selectedSale->total_toman) }} تومان</strong></div>
                @php($refundTotal = $selectedSale->items->flatMap->returns->sum('refund_toman'))
                @if($refundTotal > 0)<p class="v-help">بازپرداخت‌های ثبت‌شده: {{ number_format($refundTotal) }} تومان · مبلغ پس از مرجوعی: {{ number_format($selectedSale->total_toman - $refundTotal) }} تومان</p>@endif
            </div>
            <div class="v-cart-lines">
                @foreach($selectedSale->items as $line)
                    <div><div class="s-panelhead"><div><strong>{{ $line->name }}</strong><span class="v-subtext">قابل مرجوع: {{ $line->quantity - $line->returned_quantity }} عدد · مرجوع‌شده: {{ $line->returned_quantity }} عدد</span></div>
                        @can('sales.manage')@if($line->returned_quantity < $line->quantity)<button type="button" class="s-btn" wire:click="beginReturn({{ $line->id }})" aria-label="مرجوع کردن {{ $line->name }}">مرجوع کردن</button>@endif@endcan</div>
                        @foreach($line->returns as $return)<p class="v-help">مرجوعی #{{ $return->id }} · {{ $return->quantity }} عدد · {{ number_format($return->refund_toman) }} تومان · {{ $return->refund_method === 'cash' ? 'نقدی' : 'کارت — ثبت دستی' }} · {{ $return->reason }} · {{ $return->user->name }} · {{ \Morilog\Jalali\Jalalian::fromCarbon($return->created_at->timezone('Asia/Tehran'))->format('Y/m/d H:i') }}</p>@endforeach
                    </div>
                @endforeach
            </div>
            @if($returnItemId && !$returnCompleted && auth()->user()->can('sales.manage'))
                @php($returnLine = $selectedSale->items->firstWhere('id', $returnItemId))
                @if($returnLine)
                    <form wire:submit="saveReturn" class="v-form-panel">
                        <h3>مرجوعی {{ $returnLine->name }}</h3><p class="v-help">فقط کالای سالم و قابل فروش؛ بازگشت به انبار همین فاکتور. بهای بازپرداخت هر عدد {{ number_format($returnLine->unit_price_toman) }} تومان است. برای ثبت قلم بعدی، دوباره «مرجوع کردن» را بزنید.</p>
                        <div class="v-form-grid">
                            <x-workspace.field label="تعداد مرجوعی *" name="returnQuantity"><input wire:model.live.debounce.300ms="returnQuantity" inputmode="numeric" maxlength="7" dir="ltr" required></x-workspace.field>
                            <x-workspace.field label="روش بازپرداخت دستی" name="refundMethod"><select wire:model="refundMethod"><option value="cash">نقدی</option><option value="card">کارت — ثبت دستی</option></select></x-workspace.field>
                        </div>
                        <x-workspace.field label="دلیل مرجوعی *" name="returnReason"><input wire:model="returnReason" maxlength="255" required></x-workspace.field>
                        @php($normalizedReturn = \Modules\Core\Support\WorkspaceAccess::digits($returnQuantity))
                        <p class="v-help">مبلغ بازپرداخت: {{ number_format(ctype_digit($normalizedReturn) && strlen($normalizedReturn) <= 7 ? (int) $normalizedReturn * $returnLine->unit_price_toman : 0) }} تومان</p>
                        <div class="v-checks"><label><input type="checkbox" wire:model="refundConfirmed" required> کالای سالم را دریافت و وجه را به مشتری بازگردانده‌ام.</label></div>
                        <p class="v-help">این فرم انتقال بانکی انجام نمی‌دهد؛ سابقهٔ بازپرداخت انجام‌شده را ثبت می‌کند.</p>
                        <button class="s-btn s-mainbtn" wire:loading.attr="disabled" wire:target="saveReturn">ثبت قطعی مرجوعی و بازپرداخت</button>
                    </form>
                @endif
            @endif
        </section>
    @endif
    <section class="s-panel"><div class="s-panelhead"><h2>{{ $isPos ? 'فروش‌های ثبت‌شده' : 'فهرست فاکتورها' }}</h2><span class="s-muted">{{ number_format($sales->total()) }} فاکتور</span></div>
        @if($sales->count())<div class="v-table-wrap"><table class="v-table"><thead><tr><th>فاکتور / تاریخ</th><th>مشتری</th><th>کانال</th><th>مبلغ — تومان</th><th><span class="v-sr-only">جزئیات</span></th></tr></thead><tbody>@foreach($sales as $sale)<tr wire:key="sale-{{ $sale->id }}"><td><strong>#{{ $sale->id }}</strong><small class="v-subtext">{{ \Morilog\Jalali\Jalalian::fromCarbon($sale->created_at->timezone('Asia/Tehran'))->format('Y/m/d H:i') }}</small></td><td>{{ $sale->customer_name }}</td><td><span class="s-pill">{{ $sale->channel === 'pos' ? 'صندوق' : 'فروش مستقیم' }}</span></td><td class="v-number">{{ number_format($sale->total_toman) }}</td><td><button type="button" class="s-btn" wire:click="selectSale({{ $sale->id }})" aria-label="نمایش فاکتور {{ $sale->id }}">مشاهده</button></td></tr>@endforeach</tbody></table></div><div class="v-pagination">{{ $sales->links() }}</div>
        @else<x-workspace.empty title="اولین فروش را ثبت کنید" description="پس از تعریف کالا و ورود موجودی، فاکتور جدید بسازید." icon="receipt-text" />@endif
    </section>
</div>
