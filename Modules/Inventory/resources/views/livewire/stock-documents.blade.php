<div class="v-workspace">
    <div class="s-heading"><div><span class="v-eyebrow">از تأمین کالا تا رسیدن به شعبه</span><h1>خرید و انتقال کالا</h1><p class="s-muted">هر رسید برای یک کالا ثبت می‌شود؛ موجودی و سابقه با هم تغییر می‌کنند.</p></div><a class="s-btn" href="{{ route('panel.inventory') }}">موجودی انبارها</a></div>
    <x-workspace.feedback :notice="$notice" />
    @if($canWrite)
        <div class="s-controls"><button class="s-btn s-mainbtn" wire:click="newDocument('purchase')">+ رسید خرید</button><button class="s-btn" wire:click="newDocument('transfer')">انتقال بین انبارها</button></div>
        @if($showForm)
            <form wire:submit="saveDocument" class="s-panel v-form-panel">
                <div class="s-panelhead"><h2>{{ $kind === 'purchase' ? 'رسید خرید کالا' : 'انتقال فوری بین دو انبار' }}</h2><button type="button" class="s-btn" wire:click="$set('showForm', false)">بستن فرم</button></div>
                <p class="v-help">{{ $kind === 'purchase' ? 'برای کالای تحویل‌گرفته‌شده رسید بزنید. بهای خرید، قیمت فروش را تغییر نمی‌دهد؛ این رسید ثبت پرداخت به تأمین‌کننده نیست.' : 'فقط پس از تحویل کالا در مقصد ثبت کنید. خروج مبدأ و ورود مقصد هم‌زمان ثبت می‌شوند؛ انتقال در راه در این نسخه وجود ندارد.' }}</p>
                <x-workspace.field label="جست‌وجوی کالا"><input wire:model.live.debounce.300ms="productSearch" maxlength="160" placeholder="نام یا کد کالا…"></x-workspace.field>
                <div class="v-form-grid">
                    <x-workspace.field label="کالای رسید *" name="productId"><select wire:model="productId" required><option value="">انتخاب کالا — تا ۱۰۰ نتیجه</option>@foreach($products as $product)<option value="{{ $product->id }}">{{ $product->name }} · {{ $product->sku }}</option>@endforeach</select></x-workspace.field>
                    <x-workspace.field :label="$kind === 'purchase' ? 'انبار دریافت *' : 'انبار مبدأ *'" name="warehouseId"><select wire:model="warehouseId" required><option value="">انتخاب انبار</option>@foreach($warehouses as $warehouse)<option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>@endforeach</select></x-workspace.field>
                    @if($kind === 'transfer')
                        <x-workspace.field label="انبار مقصد *" name="targetWarehouseId"><select wire:model="targetWarehouseId" required><option value="">انتخاب مقصد متفاوت</option>@foreach($warehouses as $warehouse)<option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>@endforeach</select></x-workspace.field>
                    @else
                        <x-workspace.field label="تأمین‌کننده *" name="supplierId"><select wire:model="supplierId" required><option value="">انتخاب تأمین‌کننده</option>@foreach($suppliers as $supplier)<option value="{{ $supplier->id }}">{{ $supplier->name }}{{ $supplier->phone ? ' · '.$supplier->phone : '' }}</option>@endforeach</select></x-workspace.field>
                        <x-workspace.field label="بهای خرید هر عدد — تومان *" name="unitCost"><input wire:model.live.debounce.300ms="unitCost" dir="ltr" inputmode="numeric" maxlength="13" required></x-workspace.field>
                    @endif
                    <x-workspace.field label="تعداد رسید *" name="quantity"><input wire:model.live.debounce.300ms="quantity" dir="ltr" inputmode="numeric" maxlength="7" required></x-workspace.field>
                </div>
                <x-workspace.field :label="$kind === 'purchase' ? 'شرح / شمارهٔ سند تأمین‌کننده *' : 'شرح انتقال *'" name="reason"><input wire:model="reason" maxlength="255" required placeholder="مثلاً فاکتور ۱۲۳ یا انتقال به شعبه"></x-workspace.field>
                @if($kind === 'purchase')<div class="v-cart-total"><span>جمع بهای خرید</span><strong>{{ $purchaseTotal === null ? '—' : number_format($purchaseTotal) }} <small>تومان</small></strong></div>@endif
                <div class="v-checks"><label><input type="checkbox" wire:model="deliveryConfirmed" required> تعداد و تحویل فیزیکی کالا را بررسی و تأیید می‌کنم.</label></div>
                <button class="s-btn s-mainbtn" wire:loading.attr="disabled" wire:target="saveDocument">تحویل انجام شد؛ ثبت قطعی</button>
            </form>
        @endif
        <details class="s-panel"><summary>افزودن تأمین‌کننده</summary><form wire:submit="saveSupplier" class="v-form-grid">
            <x-workspace.field label="نام تأمین‌کننده *" name="supplierName"><input wire:model="supplierName" maxlength="120" required></x-workspace.field>
            <x-workspace.field label="تلفن تأمین‌کننده" name="supplierPhone"><input wire:model="supplierPhone" maxlength="20" inputmode="tel" dir="ltr"></x-workspace.field>
            <div><button class="s-btn" wire:loading.attr="disabled" wire:target="saveSupplier">افزودن و انتخاب</button></div>
        </form></details>
    @endif
    <section class="s-panel"><div class="v-toolbar"><h2>سابقهٔ خرید و انتقال</h2><label class="v-search"><span class="v-sr-only">جست‌وجوی اسناد</span><input wire:model.live.debounce.300ms="search" placeholder="کالا یا تأمین‌کننده…" maxlength="160" type="search"></label></div>
        @if($documents->count())<div class="v-table-wrap"><table class="v-table v-responsive-table"><thead><tr><th>سند / کالا</th><th>مسیر / تأمین‌کننده</th><th>تعداد / بهای خرید</th><th>شرح / ثبت‌کننده</th></tr></thead><tbody>@foreach($documents as $document)<tr wire:key="document-{{ $document->id }}">
            <td data-label="سند / کالا"><strong>#{{ $document->id }} · {{ $document->kind === 'purchase' ? 'خرید' : 'انتقال' }}</strong><span class="v-subtext">{{ $document->product_name }} · <bdi>{{ $document->sku }}</bdi></span></td>
            <td data-label="مسیر / تأمین‌کننده">{{ $document->kind === 'purchase' ? $document->supplier_name : $document->warehouse->name }}<span class="v-subtext">{{ $document->kind === 'purchase' ? 'دریافت: '.$document->warehouse->name : 'مقصد: '.$document->targetWarehouse->name }}</span></td>
            <td data-label="تعداد / بهای خرید">{{ number_format($document->quantity) }} عدد @if($document->kind === 'purchase')<span class="v-subtext">فی {{ number_format($document->unit_cost_toman) }} · جمع {{ number_format($document->total_toman) }} تومان</span>@endif</td>
            <td data-label="شرح / ثبت‌کننده">{{ $document->reason }}<span class="v-subtext">{{ $document->user->name }} · {{ \Morilog\Jalali\Jalalian::fromCarbon($document->created_at->timezone('Asia/Tehran'))->format('Y/m/d H:i') }}</span></td>
        </tr>@endforeach</tbody></table></div><div class="v-pagination">{{ $documents->links() }}</div>
        @else<x-workspace.empty title="هنوز رسیدی ثبت نشده" description="برای کالای تحویل‌شده، رسید خرید یا انتقال ثبت کنید." icon="boxes" />@endif
    </section>
</div>
