@extends('front::layouts.master')

@section('content')
<main class="main-content dt-sl mt-4 mb-3">
    <div class="container main-container">
        <div class="dt-sn p-4 mb-3">
            <h1 class="h4 mb-3">همه محصولات</h1>
            <form method="get" action="{{ route('storefront.preview.products') }}" class="form-inline">
                <label for="vira-preview-search" class="sr-only">جست‌وجوی کالا</label>
                <input id="vira-preview-search" class="form-control ml-2 mb-2" name="q" value="{{ $search }}" maxlength="160" placeholder="نام یا دسته‌بندی کالا">
                <button class="btn btn-primary mb-2" type="submit">جست‌وجو</button>
            </form>
        </div>
        @if($products->count())
            <div class="row">
                @foreach($products as $product)
                    <div class="col-6 col-md-4 col-lg-3 mb-3">
                        @include('front::catalog.partials.product-card')
                    </div>
                @endforeach
            </div>
            <div class="dt-sn p-3 mb-3">{{ $products->links() }}</div>
        @else
            <div class="dt-sn p-4 text-center">محصول منتشرشده‌ای پیدا نشد.</div>
        @endif
    </div>
</main>
@endsection
