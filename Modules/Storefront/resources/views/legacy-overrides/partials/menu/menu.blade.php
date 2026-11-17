<nav class="main-menu dt-sl" aria-label="منوی فروشگاه">
    <ul class="list float-right hidden-md">
        <li><a href="{{ route('storefront.preview.home') }}">خانه</a></li>
        <li><a href="{{ route('storefront.preview.products') }}">محصولات</a></li>
    </ul>
    <ul class="nav float-left">
        <li class="nav-item"><a class="nav-link" href="{{ route('storefront.preview.products') }}"><i class="mdi mdi-shopping-search"></i> مشاهده کالاها</a></li>
    </ul>
    <a class="d-md-none" href="{{ route('storefront.preview.products') }}" aria-label="مشاهده همه محصولات"><i class="mdi mdi-magnify"></i> محصولات</a>
</nav>
