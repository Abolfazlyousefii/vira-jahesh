<section class="vira-store-features" id="site-features">
    <h2>همه‌چیز برای مدیریت ساده‌تر</h2>
    <div class="vira-store-feature-grid">
        @foreach(['first', 'second', 'third'] as $key)
            <article class="vira-store-feature">
                <span class="s-avatar"><i data-lucide="{{ ['first' => 'users', 'second' => 'boxes', 'third' => 'blocks'][$key] }}"></i></span>
                <h3>{{ $settings[$key] }}</h3>
                <p>این بخش با توسعهٔ ماژول مربوط به اطلاعات عملیاتی متصل خواهد شد.</p>
            </article>
        @endforeach
    </div>
</section>
