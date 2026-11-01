<section class="vira-store-hero" id="site-top">
    <div class="vira-store-hero-copy">
        <span class="s-pill">مدیریت یکپارچهٔ فروشگاه</span>
        <h1>{{ $settings['title'] }}</h1>
        <p>{{ $settings['description'] }}</p>
        <a class="s-btn s-mainbtn" href="{{ $settings['href'] }}">{{ $settings['button'] }} <i data-lucide="arrow-left"></i></a>
        <div class="vira-site-disclaimer">صفحهٔ معرفی نسخهٔ آغازین؛ خرید و پرداخت هنوز عملیاتی نیست.</div>
    </div>
    <div class="vira-store-art">
        @if($imageUrl)
            <img src="{{ $imageUrl }}" alt="تصویر معرفی ویرا">
        @else
            <div class="vira-store-art-mark" aria-hidden="true">و</div>
        @endif
    </div>
</section>
