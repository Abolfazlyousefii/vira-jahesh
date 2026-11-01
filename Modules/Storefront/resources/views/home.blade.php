<x-layouts.base title="ویرا — یکپارچه با جهش">
    <div class="vira-store">
        <header class="vira-store-nav">
            <a class="s-brand" href="{{ route('storefront.home') }}">
                <span class="s-logo">و</span><span><strong>ویرا</strong><small class="s-muted" style="display:block">یکپارچه با جهش</small></span>
            </a>
            <nav aria-label="دسترسی‌های سایت">
                <a href="#site-features">ویژگی‌ها</a>
                <a href="#site-cta">دربارهٔ ویرا</a>
                <a class="s-btn" href="{{ route('login') }}">ورود به مجموعه <i data-lucide="arrow-left"></i></a>
            </nav>
        </header>

        <main>
            @foreach($sections as $section)
                @if($section['is_visible'])
                    @include('storefront::sections.'.$section['type'], ['settings' => $section['settings'], 'imageUrl' => $section['image_url']])
                @endif
            @endforeach
        </main>
        <footer class="vira-store-footer">
            <span>ویرا — یکپارچه با جهش</span>
            <span>نسخهٔ آغازین؛ فروش اینترنتی هنوز فعال نیست</span>
        </footer>
    </div>
</x-layouts.base>
