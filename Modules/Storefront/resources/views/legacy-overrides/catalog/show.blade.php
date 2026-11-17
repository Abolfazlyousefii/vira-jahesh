@extends('front::layouts.master')

@section('content')
<main class="main-content dt-sl mt-4 mb-3">
    <div class="container main-container">
        <div class="dt-sn p-3 mb-3">
            <a href="{{ route('storefront.preview.products') }}">همه محصولات</a>
            <span aria-hidden="true"> / </span>
            <span>{{ $product->name }}</span>
        </div>
        <article class="dt-sn p-4 mb-3">
            <div class="row">
                <div class="col-12 col-lg-5 text-center mb-3">
                    <img class="img-fluid" src="{{ $product->imageUrl() ?: theme_asset('images/600-600.png') }}" alt="{{ $product->name }}">
                </div>
                <div class="col-12 col-lg-7">
                    <h1 class="h3">{{ $product->name }}</h1>
                    <p class="text-muted">{{ $product->category }}</p>
                    <p class="h4 mt-4">{{ number_format($product->price_toman) }} تومان</p>
                    <p class="mt-3" style="white-space: pre-wrap">{{ $product->description }}</p>
                    @if($siblings->isNotEmpty())
                        <div class="mt-3" aria-label="تنوع‌های کالا">
                            @foreach($siblings as $sibling)
                                <a class="btn btn-light mb-2" href="{{ route('storefront.preview.product', $sibling->id) }}">
                                    {{ implode(' / ', array_filter([$sibling->color, $sibling->size])) }}
                                </a>
                            @endforeach
                        </div>
                    @endif
                    <div class="alert alert-info mt-4">این پیش‌نمایش برای تطبیق قالب است؛ سبد خرید و پرداخت پس از اتصال سفارش‌ها فعال می‌شود.</div>
                </div>
            </div>
        </article>
    </div>
</main>
@endsection
