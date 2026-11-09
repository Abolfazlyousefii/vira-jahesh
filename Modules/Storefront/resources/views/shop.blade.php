<x-layouts.base :title="$product ? $product->name : 'فهرست کالاها'">
    <div class="vira-store">
        <header class="vira-store-nav"><a class="s-brand" href="{{ route('storefront.home') }}"><span class="s-logo">و</span><strong>{{ \Modules\Core\Models\BusinessSetting::find(1)?->name ?: 'ویرا' }}</strong></a><nav aria-label="دسترسی‌های سایت"><a href="{{ route('storefront.home') }}">خانه</a><a href="{{ route('storefront.shop') }}">کالاها</a></nav></header>
        <main class="s-home">
            @if($product)
                <div class="v-shop-intro"><a class="s-btn" href="{{ route('storefront.shop') }}">بازگشت به کالاها</a></div>
                <article class="v-sale-layout"><div class="v-product-art v-product-detail-art">@if($product->imageUrl())<img src="{{ $product->imageUrl() }}" alt="{{ $product->name }}" width="640" height="640">@else<i data-lucide="package" aria-hidden="true"></i>@endif</div><div class="s-panel"><span class="s-pill">{{ $product->category ?: 'کالا' }}</span><h1>{{ $product->name }}</h1><div class="v-metric">{{ number_format($product->price_toman) }} <small>تومان</small></div>
                    @if($siblings->isNotEmpty())<section class="v-variant-selection" aria-label="انتخاب رنگ و سایز"><h2>رنگ و سایز</h2><div class="s-controls">@foreach($siblings as $sibling)<a class="s-btn {{ $sibling->id === $product->id ? 's-mainbtn' : '' }}" href="{{ route('storefront.product', $sibling->id) }}" @if($sibling->id === $product->id) aria-current="page" @endif>{{ implode(' / ', array_filter([$sibling->color, $sibling->size], fn ($value) => $value !== '')) }}</a>@endforeach</div></section>@endif
                    <p style="white-space:pre-wrap">{{ $product->description }}</p><p class="v-help">این صفحه برای مشاهدهٔ کالا است. خرید اینترنتی هنوز فعال نیست.</p></div></article>
            @else
                <div class="v-shop-intro"><span class="v-eyebrow">ویترین فروشگاه</span><h1>کالاهای ما</h1><p class="s-muted">کالاها و قیمت‌های منتشرشدهٔ فروشگاه را ببینید.</p></div>
                <form method="get" action="{{ route('storefront.shop') }}" class="v-toolbar"><label class="v-search"><span class="v-sr-only">جست‌وجوی کالا</span><input type="search" name="q" value="{{ $search }}" placeholder="نام یا دسته‌بندی کالا…" maxlength="160"></label><button class="s-btn s-mainbtn">جست‌وجو</button></form>
                <div class="v-shop-grid">@forelse($products as $item)<a class="v-shop-card" href="{{ route('storefront.product', $item->id) }}"><span class="v-product-art">@if($item->imageUrl())<img src="{{ $item->imageUrl() }}" alt="{{ $item->name }}" width="400" height="300" loading="lazy">@else<i data-lucide="package" aria-hidden="true"></i>@endif</span><small class="s-muted">{{ $item->category ?: 'کالا' }}</small><h2>{{ $item->name }}</h2><div class="v-number">{{ number_format($item->price_toman) }} <small>تومان</small></div><span class="v-metric-link">مشاهدهٔ کالا ←</span></a>@empty<x-workspace.empty title="کالایی برای نمایش پیدا نشد" description="کالاهای منتشرشده در این بخش قرار می‌گیرند." />@endforelse</div>
                <div class="v-pagination">{{ $products->links() }}</div>
            @endif
        </main>
        <footer class="vira-store-footer"><span>ویرا · یکپارچه با جهش</span><span>ویترین کالا؛ خرید اینترنتی هنوز فعال نیست.</span></footer>
    </div>
</x-layouts.base>
