<section class="vl-section" id="site-features">
                    <div class="vl-container">
                        <div class="vl-section-heading">
                            <span class="vl-section-kicker">امکانات ویرا</span>
                            <h2>همه‌چیز برای مدیریت ساده‌تر</h2>
                            <p>به‌جای استفاده از چند ابزار جداگانه، بخش‌های مختلف کسب‌وکار را در یک فضای مدیریتی منظم دنبال کنید.</p>
                        </div>
                        <div class="vl-feature-grid">
                            @foreach($featureItems as $item)
                                <article class="vl-feature-card">
                                    <span class="vl-feature-icon {{ $item['tone'] }}"><svg class="vl-icon"><use href="#vl-{{ $item['icon'] }}"/></svg></span>
                                    <h3>{{ $featureSettings[$item['key']] ?? $item['fallback'] }}</h3>
                                    <p>{{ $item['description'] }}</p>
                                    <a href="#site-modules" class="vl-text-link">آشنایی با ماژول‌ها <svg class="vl-icon"><use href="#vl-arrow-left"/></svg></a>
                                </article>
                            @endforeach
                        </div>
                    </div>
                </section>
