<div class="v-workspace">
    <div class="s-heading"><div><span class="v-eyebrow">مرجع مشترک سایت و صندوق</span><h1>کالا و قیمت <small class="s-pill">{{ number_format($count) }} کالا</small></h1><p class="s-muted">کالا را تعریف کنید، قیمت بدهید و برای فروش آماده کنید.</p></div>@can('catalog.manage')<button class="s-btn s-mainbtn" wire:click="create"><i data-lucide="package"></i> کالای جدید</button>@endcan</div>
    <x-workspace.feedback :notice="$notice" />
    @if($showForm)
        <form wire:submit="save" class="s-panel v-form-panel" wire:key="product-form-{{ $editingId ?? 'new' }}-{{ $kind }}">
            <div class="s-panelhead"><h2>{{ $editingId ? 'ویرایش کالا' : 'افزودن کالا' }}</h2><button type="button" class="s-btn" wire:click="$set('showForm', false)">بستن فرم</button></div>
            @unless($editingId)<div class="v-kind-selector" role="group" aria-label="نوع کالا"><button type="button" class="s-btn {{ $kind === 'simple' ? 's-mainbtn' : '' }}" wire:click="chooseKind('simple')" aria-pressed="{{ $kind === 'simple' ? 'true' : 'false' }}"><i data-lucide="package"></i> کالای ساده</button><button type="button" class="s-btn {{ $kind === 'variants' ? 's-mainbtn' : '' }}" wire:click="chooseKind('variants')" aria-pressed="{{ $kind === 'variants' ? 'true' : 'false' }}"><i data-lucide="shapes"></i> دارای رنگ یا سایز</button></div>@endunless
            <div class="v-form-grid">
                <x-workspace.field label="نام کالا *" name="form.name"><input wire:model="form.name" x-init="$nextTick(() => $el.focus())" maxlength="160" required></x-workspace.field>
                <x-workspace.field label="دسته‌بندی" name="form.category"><input wire:model="form.category" maxlength="100" placeholder="مثلاً نوشت‌افزار"></x-workspace.field>
                @if($kind === 'simple')
                <x-workspace.field label="قیمت فروش — تومان *" name="form.price_toman" hint="بدون اعشار؛ مثال: ۱۲۵۰۰۰"><input wire:model="form.price_toman" inputmode="numeric" dir="ltr" maxlength="13" required></x-workspace.field>
                <x-workspace.field label="کد کالا *" name="form.sku" hint="حروف انگلیسی، عدد، خط تیره؛ مانند NOTE-BLUE"><input wire:model="form.sku" dir="ltr" maxlength="64" required></x-workspace.field>
                <x-workspace.field label="بارکد" name="form.barcode"><input wire:model="form.barcode" inputmode="numeric" dir="ltr" maxlength="64"></x-workspace.field>
                @endif
            </div>
            <x-workspace.field label="توضیحات کالا" name="form.description"><textarea wire:model="form.description" rows="3" maxlength="5000"></textarea></x-workspace.field>
            @if($kind === 'simple')<div class="v-checks"><label><input type="checkbox" wire:model="form.is_active"> فعال و قابل فروش</label><label><input type="checkbox" wire:model="form.is_published"> نمایش در فهرست عمومی سایت</label></div>@endif
            <section class="v-photo-section"><h2><i data-lucide="image-plus"></i> تصویر اصلی کالا</h2><x-workspace.field label="انتخاب تصویر" name="productImage" hint="JPG، PNG یا WebP؛ تا ۲ مگابایت و حداکثر ۴۰۹۶ پیکسل"><span class="v-upload-control"><input type="file" wire:model="productImage" accept="image/jpeg,image/png,image/webp" aria-label="انتخاب تصویر کالا"><i data-lucide="image-plus" aria-hidden="true"></i><span aria-hidden="true">برای انتخاب تصویر بزنید</span></span></x-workspace.field><p wire:loading wire:target="productImage" role="status">در حال بارگذاری تصویر…</p>
                @if($productImage && in_array($productImage->guessExtension(), ['jpg', 'jpeg', 'png', 'webp'], true))<img class="v-photo-preview" src="{{ $productImage->temporaryUrl() }}" alt="پیش‌نمایش تصویر انتخاب‌شده">@elseif($editedImageUrl)<img class="v-photo-preview" src="{{ $editedImageUrl }}" alt="تصویر فعلی کالا"><button type="button" class="s-btn" wire:click="removeImage" wire:confirm="تصویر فعلی کالا حذف شود؟"><i data-lucide="trash-2"></i> حذف تصویر فعلی</button>@endif
            </section>
            @if($kind === 'variants')
                <section class="v-variants"><div class="s-panelhead"><div><h2><i data-lucide="shapes"></i> رنگ‌ها و سایزها</h2><p class="s-muted">هر ترکیب یک کد، قیمت و موجودی مستقل دارد. حداقل رنگ یا سایز را وارد کنید.</p></div></div>
                    @foreach($variants as $index => $variant)<section class="v-variant-card" wire:key="variant-row-{{ $variant['id'] ?? 'new-'.$index }}"><div class="s-panelhead"><h3>تنوع {{ $index + 1 }} @if($variant['id'] ?? null)<span class="s-pill">ثبت‌شده</span>@endif</h3>@unless($variant['id'] ?? null)<button type="button" class="s-btn" wire:click="removeVariant({{ $index }})" aria-label="حذف ردیف تنوع {{ $index + 1 }}"><i data-lucide="trash-2"></i> حذف ردیف</button>@endunless</div><div class="v-variant-fields">
                        <x-workspace.field :label="'رنگ تنوع '.($index + 1)" :name="'variants.'.$index.'.color'"><input wire:model="variants.{{ $index }}.color" maxlength="20" placeholder="مثلاً آبی" @disabled($variant['id'] ?? false)></x-workspace.field>
                        <x-workspace.field :label="'سایز تنوع '.($index + 1)" :name="'variants.'.$index.'.size'"><input wire:model="variants.{{ $index }}.size" maxlength="12" placeholder="مثلاً M یا ۴۲" @disabled($variant['id'] ?? false)></x-workspace.field>
                        <x-workspace.field :label="'کد تنوع '.($index + 1).' *'" :name="'variants.'.$index.'.sku'"><input wire:model="variants.{{ $index }}.sku" maxlength="64" dir="ltr" placeholder="SHIRT-BLUE-M" required></x-workspace.field>
                        <x-workspace.field :label="'قیمت تنوع '.($index + 1).' — تومان *'" :name="'variants.'.$index.'.price_toman'"><input wire:model="variants.{{ $index }}.price_toman" inputmode="numeric" maxlength="13" dir="ltr" required></x-workspace.field>
                        <x-workspace.field :label="'بارکد تنوع '.($index + 1)" :name="'variants.'.$index.'.barcode'"><input wire:model="variants.{{ $index }}.barcode" inputmode="numeric" dir="ltr" maxlength="64"></x-workspace.field>
                    </div><div class="v-checks"><label><input type="checkbox" wire:model="variants.{{ $index }}.is_active"> این تنوع قابل فروش است</label><label><input type="checkbox" wire:model="variants.{{ $index }}.is_published"> نمایش این تنوع در سایت</label></div></section>@endforeach
                    <button type="button" class="s-btn" wire:click="addVariant" @disabled(count($variants) >= 50)><i data-lucide="plus"></i> افزودن رنگ یا سایز دیگر</button>
                    <p class="v-help">رنگ و سایز پس از ثبت ثابت می‌مانند. برای ترکیب تازه، تنوع جدید بسازید؛ برای توقف فروش، تنوع قبلی را غیرفعال کنید.</p>
                </section>
            @endif
            <p class="v-help">پس از ذخیرهٔ کالا، موجودی هر کد را از بخش انبار وارد کنید.</p>
            <div class="v-form-footer"><button class="s-btn s-mainbtn" wire:loading.attr="disabled" wire:target="save,productImage">ذخیرهٔ کالا</button><button type="button" class="s-btn" wire:click="$set('showForm', false)">انصراف</button></div>
        </form>
    @endif
    <section class="s-panel">
        <div class="v-toolbar"><label class="v-search"><i data-lucide="package"></i><span class="v-sr-only">جست‌وجوی کالا</span><input wire:model.live.debounce.300ms="search" placeholder="نام، کد کالا یا بارکد…" type="search" maxlength="160"></label><span class="s-muted">قیمت‌ها به تومان</span></div>
        @if($products->count())
            <div class="v-table-wrap"><table class="v-table v-responsive-table"><thead><tr><th>کالا</th><th>کد کالا</th><th>قیمت فروش</th><th>وضعیت</th><th><span class="v-sr-only">عملیات</span></th></tr></thead><tbody>
                @foreach($products as $product)<tr wire:key="product-{{ $product->id }}"><td><div class="s-flex"><x-product-visual :product="$product" /><div><strong>{{ $product->name }}</strong><small class="v-subtext">{{ $product->category ?: 'بدون دسته‌بندی' }}{{ $product->group_id ? ' · دارای تنوع' : '' }}</small></div></div></td><td data-label="کد کالا"><bdi>{{ $product->sku }}</bdi></td><td data-label="قیمت فروش" class="v-number">{{ number_format($product->price_toman) }} <small>تومان</small></td><td data-label="وضعیت"><span class="s-pill {{ $product->is_active ? 'green' : 'orange' }}">{{ $product->is_active ? 'فعال' : 'غیرفعال' }}</span>@if($product->is_published)<small class="v-subtext">منتشرشده در سایت</small>@endif</td><td>@can('catalog.manage')<button class="s-btn" wire:click="edit({{ $product->id }})" aria-label="ویرایش {{ $product->name }}">{{ $product->group_id ? 'ویرایش تنوع‌ها' : 'ویرایش' }}</button>@endcan</td></tr>@endforeach
            </tbody></table></div>
            <div class="v-pagination">{{ $products->links() }}</div>
        @else
            <x-workspace.empty :title="$search !== '' ? 'نتیجه‌ای پیدا نشد' : 'اولین کالا را تعریف کنید'" description="نام، کد و قیمت کالا برای شروع کافی است." />
        @endif
    </section>
</div>
