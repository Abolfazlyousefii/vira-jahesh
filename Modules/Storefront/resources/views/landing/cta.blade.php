<section class="vl-cta" id="site-cta">
                    <div class="vl-container vl-cta-inner">
                        <span class="vl-cta-kicker"><span></span> همراه شما در مسیر رشد</span>
                        <h2>{{ $ctaSettings['title'] ?? 'مدیریت کسب‌وکارتان را یکپارچه‌تر کنید' }}</h2>
                        <p>{{ $ctaSettings['description'] ?? 'برای بررسی نیازهای مجموعه و آشنایی با مسیر راه‌اندازی ویرا، با تیم جهش در ارتباط باشید.' }}</p>
                        <div class="vl-cta-actions">
                            @if($salesPhone !== '')
                                <a class="vl-btn vl-btn-white" href="tel:{{ $salesPhone }}"><svg class="vl-icon"><use href="#vl-phone"/></svg> تماس با مجموعه جهش <bdi dir="ltr">{{ $salesPhoneDisplay }}</bdi></a>
                            @endif
                            <a class="vl-btn vl-btn-glass" href="{{ route('login') }}">ورود به پنل مدیریت <svg class="vl-icon"><use href="#vl-arrow-left"/></svg></a>
                        </div>
                    </div>
                </section>
