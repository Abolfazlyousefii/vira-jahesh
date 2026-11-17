<div class="item">
    <div class="product-card">
        <a class="product-thumb" href="{{ route('storefront.preview.product', $product->id) }}">
            <img src="{{ $product->imageUrl() ?: theme_asset('images/600-600.png') }}" alt="{{ $product->name }}" loading="lazy">
        </a>
        <div class="product-card-body">
            <h5 class="product-title">
                <a href="{{ route('storefront.preview.product', $product->id) }}">{{ $product->name }}</a>
            </h5>
            <div class="rating-stars"><span class="product-meta">{{ $product->category ?: 'بدون دسته‌بندی' }}</span></div>
            <div class="price-index-h float-left">
                <div class="product-prices-div">
                    <span class="product-price">{{ number_format($product->price_toman) }} تومان</span>
                </div>
            </div>
            <div class="cart"><a href="{{ route('storefront.preview.product', $product->id) }}">مشاهده محصول</a></div>
        </div>
    </div>
</div>
