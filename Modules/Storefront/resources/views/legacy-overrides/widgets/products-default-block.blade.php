<section class="slider-section dt-sl mb-3" aria-label="محصولات فروشگاه">
    <div class="row mb-3">
        <div class="col-12">
            <div class="section-title text-sm-title title-wide-custom title-wide no-after-title-wide">
                <h2>محصولات فروشگاه</h2>
                <a href="{{ route('storefront.preview.products') }}">مشاهده همه محصولات</a>
            </div>
        </div>
        <div class="col-12 px-res-0">
            @if($previewProducts->isNotEmpty())
                <div class="product-carousel carousel-md owl-carousel owl-theme">
                    @foreach($previewProducts as $product)
                        @include('front::catalog.partials.product-card')
                    @endforeach
                </div>
            @else
                <div class="dt-sn p-4 text-center">
                    <h3>فروشگاه آماده نمایش محصولات است</h3>
                    <p>کالاهای فعال و منتشرشده در Catalog ویرا در این بخش نمایش داده می‌شوند.</p>
                    <a class="btn btn-primary" href="{{ route('storefront.preview.products') }}">مشاهده فروشگاه</a>
                </div>
            @endif
        </div>
    </div>
</section>
