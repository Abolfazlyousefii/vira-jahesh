<div class="vc2" x-on:catalog-scroll-top.window="window.scrollTo({top: 0, behavior: 'instant'})">
    @if($showForm)
        <div class="vc2-page-title">
            <div>
                <span class="vc2-eyebrow"><i data-lucide="package-plus"></i> کاتالوگ / ثبت محصول</span>
                <h1>{{ $editingId ? 'ویرایش کالا' : 'تعریف کالای جدید' }}</h1>
                <p>مشخصات اصلی را ثبت کنید؛ جزئیات بیشتر اختیاری است.</p>
            </div>
            <button type="button" class="vc2-btn" wire:click="cancel"><i data-lucide="arrow-right"></i> بازگشت به کالاها</button>
        </div>
        <x-workspace.feedback :notice="$notice" />
        <div class="vc2-banner"><i data-lucide="check"></i> ابتدا مشخصات اصلی کالا را وارد کنید؛ تنوع و انتشار اختیاری هستند.</div>
        <form wire:submit="save" class="vc2-form">
            <div class="vc2-form-columns">
                <div class="vc2-form-flow">
                    <section class="vc2-card vc2-form-card">
                        <div class="vc2-section-head">
                            <span class="vc2-step">۱</span>
                            <div><h2>اطلاعات پایه کالا</h2><p>نام، کد عددی و مشخصات قابل جست‌وجو</p></div>
                        </div>
                        <div class="vc2-fields">
                            <div class="vc2-wide">
                                <x-workspace.field label="نام کالا *" name="form.name">
                                    <input wire:model="form.name" maxlength="160" placeholder="مثلاً کلید مینیاتوری تک‌پل ۱۶ آمپر" required>
                                </x-workspace.field>
                            </div>
                            <div>
                                <x-workspace.field label="کد کالا (خودکار)" name="form.sku" hint="۳ رقم دسته‌بندی + ۶ رقم ترتیبی؛ کد پس از ذخیره نهایی می‌شود و تغییر نام دسته آن را عوض نمی‌کند.">
                                    @if($editingId)
                                        <input type="text" value="{{ $form['sku'] }}" dir="ltr" class="vc2-immutable-code" readonly aria-label="کد ثابت کالا">
                                    @else
                                        <div class="vc2-sku-preview" role="status">
                                            <i data-lucide="fingerprint" aria-hidden="true"></i>
                                            <span>کد عددی به‌صورت خودکار هنگام ذخیره ساخته می‌شود</span>
                                        </div>
                                    @endif
                                </x-workspace.field>
                            </div>
                            <div>
                                <x-workspace.field label="دسته‌بندی" name="form.category">
                                    <input wire:model="form.category" list="vc2-categories" maxlength="100" placeholder="انتخاب یا تایپ دسته‌بندی">
                                </x-workspace.field>
                                <datalist id="vc2-categories">
                                    @foreach($categories as $category)
                                        <option value="{{ $category }}"></option>
                                    @endforeach
                                </datalist>
                            </div>
                            <div>
                                <x-workspace.field label="واحد شمارش" name="form.unit">
                                    <select wire:model="form.unit">
                                        @foreach(['عدد', 'بسته', 'متر', 'کیلوگرم', 'لیتر', 'کارتن', 'جفت'] as $unit)
                                            <option value="{{ $unit }}">{{ $unit }}</option>
                                        @endforeach
                                    </select>
                                </x-workspace.field>
                            </div>
                            <div>
                                <x-workspace.field label="برند (اختیاری)" name="form.brand">
                                    <input wire:model="form.brand" maxlength="80" placeholder="مثلاً اشنایدر">
                                </x-workspace.field>
                            </div>
                            <div>
                                <x-workspace.field label="قیمت فروش مبنا (تومان) *" name="form.price_toman">
                                    <input wire:model="form.price_toman" inputmode="numeric" dir="ltr" maxlength="13" placeholder="۲۴۵۰۰۰" required>
                                </x-workspace.field>
                            </div>
                            @if($kind === 'simple')
                                <div>
                                    <x-workspace.field label="بارکد (اختیاری)" name="form.barcode">
                                        <input wire:model="form.barcode" type="text" inputmode="numeric" dir="ltr" maxlength="64" placeholder="بارکد عددی">
                                    </x-workspace.field>
                                </div>
                            @endif
                            <div class="vc2-wide">
                                <x-workspace.field label="توضیحات کالا" name="form.description">
                                    <textarea wire:model="form.description" rows="2" maxlength="5000" placeholder="توضیح کوتاه و کاربردی درباره کالا"></textarea>
                                </x-workspace.field>
                            </div>
                        </div>
                    </section>

                    <section class="vc2-card vc2-form-card">
                        <div class="vc2-section-head">
                            <span class="vc2-step vc2-step-purple">۲</span>
                            <div><h2>ساختار و تنوع محصول</h2><p>رنگ، سایز، مدل یا ویژگی دلخواه</p></div>
                        </div>
                        <div class="vc2-kind-row">
                            <button type="button" class="vc2-kind {{ $kind === 'simple' ? 'is-active' : '' }}" wire:click="chooseKind('simple')" @disabled($editingId) aria-pressed="{{ $kind === 'simple' ? 'true' : 'false' }}">
                                <i data-lucide="package"></i><span><strong>کالای ساده</strong><small>یک کد و یک قیمت</small></span>
                            </button>
                            <button type="button" class="vc2-kind {{ $kind === 'variants' ? 'is-active' : '' }}" wire:click="chooseKind('variants')" @disabled($editingId) aria-pressed="{{ $kind === 'variants' ? 'true' : 'false' }}">
                                <i data-lucide="shapes"></i><span><strong>کالای دارای تنوع</strong><small>کد و قیمت مستقل برای هر ترکیب</small></span>
                            </button>
                        </div>

                        @if($kind === 'variants')
                            <div class="vc2-attributes">
                                @if($editingId)
                                    <p class="vc2-tip">ویژگی‌های تنوع‌های ثبت‌شده ثابت می‌مانند تا سوابق خرید و انبار تغییر نکنند. قیمت و وضعیت فروش قابل ویرایش است.</p>
                                    @foreach($variantFeatures as $attribute)
                                        <span class="vc2-attribute-chip">{{ $attribute['name'] }}: {{ $attribute['values'] }}</span>
                                    @endforeach
                                @else
                                    <p class="vc2-tip">نام ویژگی و مقادیر آن را تعریف کنید؛ مثال: «آمپر» و «۱۶، ۲۵، ۳۲».</p>
                                    @foreach($variantFeatures as $index => $attribute)
                                        <div class="vc2-attribute-row" wire:key="attribute-{{ $index }}">
                                            <x-workspace.field :label="'ویژگی '.($index + 1)" :name="'variantFeatures.'.$index.'.name'">
                                                <input wire:model="variantFeatures.{{ $index }}.name" maxlength="40" placeholder="مثلاً رنگ یا آمپر">
                                            </x-workspace.field>
                                            <x-workspace.field label="مقادیر (با ویرگول جدا کنید)" :name="'variantFeatures.'.$index.'.values'">
                                                <input wire:model="variantFeatures.{{ $index }}.values" maxlength="500" placeholder="مثلاً آبی، سفید، مشکی">
                                            </x-workspace.field>
                                            <button class="vc2-icon-danger" type="button" wire:click="removeAttribute({{ $index }})" aria-label="حذف ویژگی"><i data-lucide="trash-2"></i></button>
                                        </div>
                                    @endforeach
                                    <div class="vc2-inline-actions">
                                        <button type="button" class="vc2-btn" wire:click="addAttribute" @disabled(count($variantFeatures) >= 3)><i data-lucide="plus"></i> افزودن ویژگی</button>
                                        <button type="button" class="vc2-btn vc2-btn-purple" wire:click="generateVariants"><i data-lucide="shapes"></i> ساخت ترکیب‌ها</button>
                                    </div>
                                @endif
                                @error('variantFeatures')<div class="vc2-inline-error" role="alert">{{ $message }}</div>@enderror
                                @error('variants')<div class="vc2-inline-error" role="alert">{{ $message }}</div>@enderror

                                @if(count($variants))
                                    <div class="vc2-variants-title"><strong>تنوع‌های کالا</strong><span>{{ count($variants) }} ردیف؛ موجودی از ماژول انبار مدیریت می‌شود</span></div>
                                    <div class="vc2-variants-grid">
                                        @foreach($variants as $index => $variant)
                                            <div class="vc2-variant" wire:key="catalog-variant-{{ $variant['id'] ?? 'new-'.$index }}">
                                                <div class="vc2-variant-head">
                                                    <strong>تنوع {{ $index + 1 }} ـ {{ implode(' / ', array_values($variant['options'] ?? array_filter([$variant['color'] ?? '', $variant['size'] ?? '']))) }}</strong>
                                                    @unless($variant['id'] ?? null)
                                                        <button type="button" class="vc2-icon-danger" wire:click="removeVariant({{ $index }})" aria-label="حذف تنوع {{ $index + 1 }}"><i data-lucide="trash-2"></i></button>
                                                    @endunless
                                                </div>
                                                <div class="vc2-variant-fields">
                                                    <x-workspace.field :label="'کد تنوع '.($index + 1).' (خودکار)'" :name="'variants.'.$index.'.sku'">
                                                        @if($variant['id'] ?? null)
                                                            <input type="text" value="{{ $variant['sku'] }}" dir="ltr" readonly aria-label="کد ثابت تنوع {{ $index + 1 }}">
                                                        @else
                                                            <div class="vc2-sku-preview"><i data-lucide="fingerprint" aria-hidden="true"></i> پس از ذخیره تعیین می‌شود</div>
                                                        @endif
                                                    </x-workspace.field>
                                                    <x-workspace.field label="قیمت فروش (تومان) *" :name="'variants.'.$index.'.price_toman'">
                                                        <input wire:model="variants.{{ $index }}.price_toman" inputmode="numeric" dir="ltr" maxlength="13" required>
                                                    </x-workspace.field>
                                                    <x-workspace.field label="بارکد اختیاری" :name="'variants.'.$index.'.barcode'">
                                                        <input wire:model="variants.{{ $index }}.barcode" inputmode="numeric" dir="ltr" maxlength="64">
                                                    </x-workspace.field>
                                                </div>
                                                <div class="vc2-checks">
                                                    <label><input type="checkbox" wire:model="variants.{{ $index }}.is_active"> قابل فروش</label>
                                                    <label><input type="checkbox" wire:model="variants.{{ $index }}.is_published"> انتشار در سایت</label>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="vc2-soft-empty">ویژگی‌ها را تعریف کنید و روی «ساخت ترکیب‌ها» بزنید.</div>
                                @endif
                            </div>
                        @endif
                    </section>

                    <section class="vc2-card vc2-form-card">
                        <div class="vc2-section-head"><span class="vc2-step vc2-step-blue">۳</span><div><h2>وضعیت کالا</h2><p>فروش و انتشار</p></div></div>
                        @if($kind === 'simple')
                            <div class="vc2-toggles">
                                <label><span><strong>فعال و قابل فروش</strong><small>امکان ثبت فروش از این کالا</small></span><input type="checkbox" wire:model="form.is_active"></label>
                                <label><span><strong>انتشار در فروشگاه اینترنتی</strong><small>فقط در صورت فعال بودن ماژول Storefront</small></span><input type="checkbox" wire:model="form.is_published"></label>
                            </div>
                        @else
                            <p class="vc2-tip">وضعیت فروش و انتشار برای هر تنوع به‌صورت مستقل در بخش بالا تعیین می‌شود.</p>
                        @endif
                    </section>
                </div>

                <aside class="vc2-form-aside">
                    <section class="vc2-card vc2-aside-card">
                        <h3><i data-lucide="image-plus"></i> تصویر کالا <small>اختیاری</small></h3>
                        <div class="vc2-upload" wire:key="catalog-upload-{{ $groupId ?? $editingId ?? 'new' }}"
                            x-data="{
                                imagePreview: null,
                                choose(event) {
                                    if (this.imagePreview) URL.revokeObjectURL(this.imagePreview);
                                    this.imagePreview = null;
                                    const file = event.target.files?.[0];
                                    const types = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
                                    if (file && types.includes(file.type) && file.size <= 5 * 1024 * 1024) {
                                        this.imagePreview = URL.createObjectURL(file);
                                    }
                                },
                                destroy() {
                                    if (this.imagePreview) URL.revokeObjectURL(this.imagePreview);
                                }
                            }">
                            <input type="file" wire:model="productImage" x-on:change="choose($event)"
                                accept="image/jpeg,image/png,image/webp,image/gif,.jpg,.jpeg,.png,.webp,.gif"
                                aria-label="انتخاب تصویر کالا">
                            <template x-if="imagePreview">
                                <img x-bind:src="imagePreview" alt="پیش‌نمایش تصویر انتخاب‌شده">
                            </template>
                            @if($editedImageUrl)
                                <img x-show="!imagePreview" src="{{ $editedImageUrl }}" alt="تصویر فعلی کالا">
                            @else
                                <i x-show="!imagePreview" data-lucide="image-plus"></i>
                                <span x-show="!imagePreview">برای انتخاب تصویر کلیک کنید</span>
                            @endif
                        </div>
                        <p class="vc2-aside-help">JPG، PNG، WebP یا GIF · حداکثر ۵ مگابایت</p>
                        <p wire:loading wire:target="productImage" role="status">در حال بارگذاری تصویر...</p>
                        @error('productImage')<div class="vc2-inline-error" role="alert">{{ $message }}</div>@enderror
                        @if($editedImageUrl)<button type="button" class="vc2-btn vc2-danger-text" wire:click="removeImage" wire:confirm="تصویر فعلی حذف شود؟">حذف تصویر فعلی</button>@endif
                    </section>
                    <section class="vc2-card vc2-aside-card">
                        <h3><i data-lucide="clipboard-list"></i> راهنمای ثبت</h3>
                        <div class="vc2-info-list">
                            <p><b>کد:</b> خودکار و فقط عددی: سه رقم دسته، شش رقم ردیف و برای تنوع‌ها دو رقم افزوده می‌شود.</p>
                            <p><b>تنوع:</b> هر ترکیب یک محصول قابل فروش مستقل است.</p>
                            <p><b>انبار:</b> موجودی از این فرم تغییر نمی‌کند.</p>
                        </div>
                    </section>
                </aside>
            </div>
            <div class="vc2-form-footer">
                <small>ذخیره فقط پس از اعتبارسنجی کامل اطلاعات انجام می‌شود.</small>
                <div><button type="button" class="vc2-btn" wire:click="cancel">انصراف</button>
                    <button type="submit" class="vc2-btn vc2-primary" wire:loading.attr="disabled" wire:target="save,productImage"><i data-lucide="check"></i> ذخیره کالا</button>
                </div>
            </div>
        </form>
    @else
        <div class="vc2-page-title">
            <div>
                <span class="vc2-eyebrow"><i data-lucide="package"></i> مدیریت کاتالوگ</span>
                <h1>کالاهای مجموعه</h1>
                <p>جست‌وجو، مشاهده و مدیریت سریع کالاها</p>
            </div>
            <div class="vc2-heading-actions">
                @can('catalog.manage')
                    <button class="vc2-btn vc2-primary" type="button" wire:click="create"><i data-lucide="plus"></i> تعریف کالای جدید</button>
                @endcan
            </div>
        </div>
        <x-workspace.feedback :notice="$notice" />

        <section class="vc2-metrics" aria-label="آمار کالاها">
            <div class="vc2-metric vc2-metric-teal"><span class="vc2-metric-icon"><i data-lucide="package"></i></span><div><small>کل کالاها</small><strong>{{ number_format($statistics['total']) }}</strong></div></div>
            <div class="vc2-metric vc2-metric-blue"><span class="vc2-metric-icon"><i data-lucide="check"></i></span><div><small>کالاهای فعال</small><strong>{{ number_format($statistics['active']) }}</strong></div></div>
            <div class="vc2-metric vc2-metric-purple"><span class="vc2-metric-icon"><i data-lucide="shapes"></i></span><div><small>دارای تنوع</small><strong>{{ number_format($statistics['with_variants']) }}</strong></div></div>
        </section>

        <section class="vc2-card vc2-filters" aria-label="فیلتر کالاها">
            <div class="vc2-filter-row">
                <label class="vc2-search">
                    <i data-lucide="search"></i><span class="vc2-sr-only">جست‌وجوی زنده کالا</span>
                    <input type="search" wire:model.live.debounce.350ms="search" maxlength="160" placeholder="نام، کد عددی، بارکد، برند یا تنوع...">
                </label>
                <label class="vc2-select"><span class="vc2-sr-only">دسته‌بندی</span><select wire:model.live="categoryFilter"><option value="">همه دسته‌ها</option>@foreach($categories as $category)<option value="{{ $category }}">{{ $category }}</option>@endforeach</select></label>
                <label class="vc2-select"><span class="vc2-sr-only">وضعیت</span><select wire:model.live="statusFilter"><option value="">همه وضعیت‌ها</option><option value="active">فعال</option><option value="inactive">غیرفعال</option><option value="without_price">بدون قیمت</option></select></label>
            </div>
            <div class="vc2-filter-bottom">
                <div class="vc2-chips">
                    <button type="button" wire:click="$set('kindFilter', '')" class="{{ $kindFilter === '' ? 'is-active' : '' }}">همه کالاها</button>
                    <button type="button" wire:click="$set('kindFilter', 'variants')" class="{{ $kindFilter === 'variants' ? 'is-active' : '' }}">دارای تنوع</button>
                    <button type="button" wire:click="$set('kindFilter', 'simple')" class="{{ $kindFilter === 'simple' ? 'is-active' : '' }}">کالای ساده</button>
                </div>
                <div class="vc2-filter-end">
                    <label class="vc2-sort"><span>ترتیب:</span><select wire:model.live="sortBy"><option value="newest">جدیدترین</option><option value="name">نام کالا</option><option value="price">کمترین قیمت</option></select></label>
                    <button type="button" class="vc2-clear" wire:click="clearFilters">پاک کردن فیلترها</button>
                </div>
            </div>
        </section>

        <section class="vc2-card vc2-list" aria-label="فهرست کالاها">
            <header class="vc2-list-head">
                <div><h2>فهرست کالاها</h2><p>نمایش یک ردیف برای هر کالا یا خانواده تنوع</p></div>
                <span class="vc2-list-badge">{{ number_format($products->count()) }} مورد بارگذاری‌شده</span>
            </header>
            @if($products->isNotEmpty())
                <div class="vc2-table-wrap">
                    <table class="vc2-table">
                        <thead><tr><th scope="col">ردیف</th><th scope="col">نام کالا</th><th scope="col">کد کالا</th><th scope="col">دسته‌بندی</th><th scope="col">تنوع</th><th scope="col">قیمت فروش</th><th scope="col">وضعیت</th><th scope="col">عملیات</th></tr></thead>
                        <tbody>
                            @foreach($products as $index => $product)
                                @php
                                    $isGroup = $product->group_id !== null;
                                    $group = $product->group;
                                    $isActive = $isGroup ? $group->variants->contains('is_active', true) : $product->is_active;
                                    $displayName = $isGroup ? $group->name : $product->name;
                                    $displayCode = $isGroup ? ($group->sku ?: $product->sku) : $product->sku;
                                    $variantCount = $isGroup ? $group->variants->count() : 0;
                                @endphp
                                <tr wire:key="catalog-list-{{ $product->id }}">
                                    <td data-label="ردیف" class="vc2-row-num">{{ $index + 1 }}</td>
                                    <td data-label="نام کالا"><div class="vc2-product-cell"><x-product-visual :product="$product" /><div><strong>{{ $displayName }}</strong><small>{{ $isGroup ? 'محصول دارای تنوع' : ($product->brand ?: 'کالای ساده') }}</small></div></div></td>
                                    <td data-label="کد کالا"><bdi class="vc2-code">{{ $displayCode }}</bdi>@if($product->barcode)<small class="vc2-barcode" title="بارکد داخلی کالا">بارکد: <bdi dir="ltr">{{ $product->barcode }}</bdi></small>@endif</td>
                                    <td data-label="دسته‌بندی">{{ $product->category ?: 'بدون دسته' }}</td>
                                    <td data-label="تنوع"><span class="vc2-pill {{ $isGroup ? 'violet' : 'blue' }}">{{ $isGroup ? $variantCount.' تنوع' : 'ساده' }}</span></td>
                                    <td data-label="قیمت"><strong class="vc2-price">{{ number_format($product->price_toman) }}</strong><small class="vc2-currency"> تومان</small></td>
                                    <td data-label="وضعیت"><span class="vc2-pill {{ $isActive ? 'green' : 'red' }}">{{ $isActive ? 'فعال' : 'غیرفعال' }}</span></td>
                                    <td data-label="عملیات"><div class="vc2-row-actions">
                                        <button type="button" class="vc2-btn vc2-small" wire:click="showDetails({{ $product->id }})"><i data-lucide="search"></i> جزئیات</button>
                                        @can('catalog.manage')
                                            <button type="button" class="vc2-btn vc2-small vc2-edit" wire:click="edit({{ $product->id }})" aria-label="ویرایش {{ $displayName }}"><i data-lucide="pencil"></i></button>
                                        @endcan
                                    </div></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <footer class="vc2-list-footer">
                    <span>شماره ردیف · جست‌وجوی زنده · بدون صفحه‌بندی</span>
                    <span>{{ number_format($products->count()) }} کالا نمایش داده شد</span>
                </footer>
                @if($hasMore)
                    <div class="vc2-more-wrap">
                        <button type="button" class="vc2-btn vc2-more" wire:click="loadMore" wire:loading.attr="disabled" wire:target="loadMore"
                            x-init="const obs = new IntersectionObserver(entries => { if (entries[0]?.isIntersecting && !$el.disabled) $el.click() }, { root: null, rootMargin: '320px' }); obs.observe($el)">
                            <i data-lucide="chevron-down"></i> نمایش کالاهای بیشتر
                        </button>
                        <span wire:loading wire:target="loadMore" role="status">در حال دریافت کالاها...</span>
                    </div>
                @endif
            @else
                <div class="vc2-empty">
                    <i data-lucide="search"></i>
                    <strong>{{ $search !== '' || $categoryFilter !== '' || $statusFilter !== '' ? 'کالایی با این مشخصات پیدا نشد' : 'هنوز کالایی ثبت نشده است' }}</strong>
                    <p>فیلترها را تغییر دهید یا اولین کالا را تعریف کنید.</p>
                    <button type="button" class="vc2-btn" wire:click="clearFilters">پاک کردن فیلترها</button>
                </div>
            @endif
        </section>
    @endif

    @if($details && ! $showForm)
        @php
            $detailsGroup = $details->group;
        @endphp
        <div class="vc2-drawer-shell" role="dialog" aria-modal="true" aria-labelledby="vc2-details-title" x-on:keydown.escape.window="$wire.closeDetails()">
            <button type="button" class="vc2-drawer-shade" wire:click="closeDetails" aria-label="بستن جزئیات"></button>
            <aside class="vc2-drawer">
                <header>
                    <button type="button" class="vc2-icon-danger" wire:click="closeDetails" aria-label="بستن"><i data-lucide="x"></i></button>
                    <span class="vc2-eyebrow">پرونده کالا</span>
                    <h2 id="vc2-details-title">{{ $detailsGroup?->name ?? $details->name }}</h2>
                    <p>کد: <bdi>{{ $detailsGroup?->sku ?: $details->sku }}</bdi></p>
                </header>
                <div class="vc2-drawer-body">
                    <div class="vc2-detail-grid">
                        <div><small>دسته‌بندی</small><strong>{{ $details->category ?: '—' }}</strong></div>
                        <div><small>برند</small><strong>{{ $details->brand ?: '—' }}</strong></div>
                        <div><small>واحد</small><strong>{{ $details->unit ?: 'عدد' }}</strong></div>
                        <div><small>قیمت پایه</small><strong>{{ number_format($details->price_toman) }} تومان</strong></div>
                        <div><small>بارکد داخلی</small><strong dir="ltr">{{ $details->barcode ?: 'تعریف نشده' }}</strong></div>
                    </div>
                    @if($detailsGroup)
                        <h3>تنوع‌های کالا</h3>
                        @foreach($detailsGroup->variants as $variant)
                            <div class="vc2-detail-variant">
                                <strong>{{ implode(' / ', array_values($variant->options ?: array_filter([$variant->color, $variant->size]))) }}</strong>
                                <bdi>{{ $variant->sku }}</bdi>
                                @if($variant->barcode)<small>بارکد: <bdi dir="ltr">{{ $variant->barcode }}</bdi></small>@endif
                                <small>{{ number_format($variant->price_toman) }} تومان</small>
                            </div>
                        @endforeach
                    @endif
                    @if($details->description)<h3>توضیحات</h3><p>{{ $details->description }}</p>@endif
                    <p class="vc2-tip">موجودی و گردش کالا در ماژول Inventory بررسی می‌شود؛ این صفحه موجودی را تغییر نمی‌دهد.</p>
                </div>
                <footer>
                    <button type="button" class="vc2-btn" wire:click="closeDetails">بستن</button>
                    @can('catalog.manage')
                        <button type="button" class="vc2-btn vc2-primary" wire:click="edit({{ $details->id }})"><i data-lucide="pencil"></i> ویرایش کالا</button>
                    @endcan
                </footer>
            </aside>
        </div>
    @endif
</div>
