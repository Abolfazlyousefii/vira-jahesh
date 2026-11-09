<div class="v-workspace">
    <div class="s-heading"><div><span class="v-eyebrow">هر تغییر، با دلیل و سابقه</span><h1>انبار و موجودی</h1><p class="s-muted">موجودی سایت و صندوق از همین‌جا تأمین می‌شود.</p></div>@can('inventory.manage')<button class="s-btn s-mainbtn" wire:click="$toggle('showForm')"><i data-lucide="boxes"></i> ثبت ورود یا خروج</button>@endcan</div>
    <div class="s-controls"><a class="s-btn" href="{{ route('panel.inventory.documents') }}">خرید، تأمین‌کنندگان و انتقال</a></div>
    <x-workspace.feedback :notice="$notice" />
    @can('inventory.manage')
        @if($warehouses->isEmpty() || $showForm)
            <section class="s-panel v-form-panel">
                @if($warehouses->isEmpty())<h2>اولین انبار را بسازید</h2><p class="s-muted">مثلاً «انبار اصلی» یا «فروشگاه».</p>@endif
                <form wire:submit="createWarehouse" class="v-toolbar"><x-workspace.field label="نام انبار جدید" name="warehouseName"><input wire:model="warehouseName" maxlength="100" placeholder="انبار اصلی" required></x-workspace.field><button class="s-btn" wire:loading.attr="disabled" wire:target="createWarehouse">افزودن انبار</button></form>
            </section>
        @endif
        @if($showForm && $warehouses->isNotEmpty())
            <form wire:submit="saveMovement" class="s-panel v-form-panel">
                <div class="s-panelhead"><h2>ثبت گردش موجودی</h2><button type="button" class="s-btn" wire:click="$set('showForm', false)">بستن فرم</button></div>
                <x-workspace.field label="پیدا کردن کالا"><input wire:model.live.debounce.300ms="search" placeholder="نام کالا را جست‌وجو کنید…" maxlength="160"></x-workspace.field>
                <div class="v-form-grid">
                    <x-workspace.field label="انبار *" name="warehouseId"><select wire:model="warehouseId" required><option value="">انتخاب انبار</option>@foreach($warehouses as $warehouse)<option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>@endforeach</select></x-workspace.field>
                    <x-workspace.field label="کالا *" name="productId"><select wire:model="productId" required><option value="">انتخاب کالا — تا ۱۰۰ نتیجهٔ جست‌وجو</option>@foreach($products as $product)<option value="{{ $product->id }}">{{ $product->name }} · {{ $product->sku }}</option>@endforeach</select></x-workspace.field>
                    <x-workspace.field label="نوع گردش *" name="direction"><select wire:model="direction"><option value="in">ورود به انبار +</option><option value="out">خروج از انبار −</option></select></x-workspace.field>
                    <x-workspace.field label="تعداد *" name="quantity"><input wire:model="quantity" inputmode="numeric" dir="ltr" maxlength="7" required></x-workspace.field>
                </div>
                <x-workspace.field label="دلیل تغییر *" name="reason" hint="مثلاً موجودی اولیه، ورود خرید یا کالای آسیب‌دیده"><input wire:model="reason" maxlength="255" required></x-workspace.field>
                <div class="v-form-footer"><button class="s-btn s-mainbtn" wire:loading.attr="disabled" wire:target="saveMovement">ثبت گردش</button><span class="s-muted">این تغییر با نام شما در سابقه می‌ماند.</span></div>
            </form>
        @endif
    @endcan
    <section class="s-panel">
        <div class="v-toolbar"><h2>موجودی کالاها</h2><label class="v-search"><span class="v-sr-only">جست‌وجوی موجودی</span><input wire:model.live.debounce.300ms="search" placeholder="نام کالا…" type="search" maxlength="160"></label></div>
        @if($balances->count())
            <div class="v-table-wrap"><table class="v-table"><thead><tr><th>کالا</th><th>انبار</th><th>موجودی</th></tr></thead><tbody>@foreach($balances as $balance)<tr wire:key="balance-{{ $balance->id }}"><td><strong>{{ $balance->product->name }}</strong></td><td>{{ $balance->warehouse->name }}</td><td><span class="s-pill {{ $balance->quantity > 0 ? 'green' : 'orange' }}">{{ number_format($balance->quantity) }} عدد{{ $balance->quantity === 0 ? ' · ناموجود' : '' }}</span></td></tr>@endforeach</tbody></table></div>
            <div class="v-pagination">{{ $balances->links() }}</div>
        @else<x-workspace.empty title="موجودی ثبت‌شده‌ای پیدا نشد" description="پس از تعریف کالا، ورود موجودی را برای انبار ثبت کنید." icon="boxes" />@endif
    </section>
    @if($movements->isNotEmpty())
        <section class="s-panel"><div class="s-panelhead"><h2>آخرین گردش‌ها</h2><small class="s-muted">۱۰ تغییر اخیر</small></div><div class="v-table-wrap"><table class="v-table"><thead><tr><th>کالا / انبار</th><th>تغییر</th><th>دلیل</th><th>ثبت‌کننده / تاریخ</th></tr></thead><tbody>@foreach($movements as $movement)<tr wire:key="movement-{{ $movement->id }}"><td>{{ $movement->product->name }}<small class="v-subtext">{{ $movement->warehouse->name }}</small></td><td><bdi class="s-pill {{ $movement->delta > 0 ? 'green' : 'orange' }}">{{ $movement->delta > 0 ? '+' : '' }}{{ $movement->delta }}</bdi></td><td>{{ $movement->reason }}</td><td>{{ $movement->user->name }}<small class="v-subtext">{{ \Morilog\Jalali\Jalalian::fromCarbon($movement->created_at->timezone('Asia/Tehran'))->format('Y/m/d H:i') }}</small></td></tr>@endforeach</tbody></table></div></section>
    @endif
</div>
