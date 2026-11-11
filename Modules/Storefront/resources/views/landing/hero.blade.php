<section class="vl-hero" id="site-top">
                    <div class="vl-container vl-hero-grid">
                        <div class="vl-hero-copy">
                            <span class="vl-eyebrow"><span class="vl-eyebrow-dot"></span> سامانه یکپارچه مدیریت کسب‌وکار</span>
                            <h1>{!! nl2br(e($heroSettings['title'] ?? 'تمام کسب‌وکارتان، در یک پنل مدیریتی یکپارچه')) !!}</h1>
                            <p>{{ $heroSettings['description'] ?? 'با ویرا، کالاها، انبار، فروش و ارتباط با مشتریان را در یک محیط منسجم مدیریت کنید.' }}</p>
                            <div class="vl-hero-actions">
                                <a class="vl-btn vl-btn-primary vl-btn-large" href="{{ route('login') }}">
                                    ورود به پنل مدیریت
                                    <svg class="vl-icon"><use href="#vl-arrow-left"/></svg>
                                </a>
                                <a class="vl-btn vl-btn-secondary vl-btn-large" href="{{ $heroHref }}">
                                    {{ $heroSettings['button'] ?? 'آشنایی با امکانات' }}
                                    <svg class="vl-icon"><use href="#vl-arrow-left"/></svg>
                                </a>
                            </div>
                            <div class="vl-hero-note"><svg class="vl-icon"><use href="#vl-shield"/></svg> ساختار ماژولار، دسترسی کنترل‌شده و قابلیت توسعه متناسب با کسب‌وکار</div>
                        </div>

                        <div class="vl-hero-visual" aria-label="پیش‌نمایش محیط مدیریت ویرا">
                            <div class="vl-visual-glow"></div>
                            @if(!empty($hero['image_url']))
                                <div class="vl-hero-image"><img src="{{ $hero['image_url'] }}" alt="تصویر معرفی ویرا" loading="eager"></div>
                            @else
                                <div class="vl-dashboard-card">
                                    <div class="vl-dashboard-top">
                                        <div class="vl-dashboard-brand"><span class="vl-dashboard-logo">و</span><strong>فضای کاری ویرا</strong></div>
                                        <span class="vl-dashboard-user"><span class="vl-dashboard-avatar">م</span> مدیر مجموعه</span>
                                    </div>
                                    <div class="vl-dashboard-heading"><strong>نمای کلی کسب‌وکار</strong><span>محیط مدیریت یکپارچه</span></div>
                                    <div class="vl-dashboard-modules">
                                        <div class="vl-dashboard-module"><span class="vl-module-icon purple"><svg class="vl-icon"><use href="#vl-box"/></svg></span><span><strong>کالاها</strong><small>تعریف و مدیریت کالا</small></span><b>›</b></div>
                                        <div class="vl-dashboard-module"><span class="vl-module-icon green"><svg class="vl-icon"><use href="#vl-warehouse"/></svg></span><span><strong>انبار و موجودی</strong><small>گردش و مانده کالا</small></span><b>›</b></div>
                                        <div class="vl-dashboard-module"><span class="vl-module-icon blue"><svg class="vl-icon"><use href="#vl-users"/></svg></span><span><strong>مشتریان</strong><small>اطلاعات و پرونده‌ها</small></span><b>›</b></div>
                                    </div>
                                    <div class="vl-dashboard-bottom">
                                        <span><i></i> ماژول‌های مرتبط در یک محیط</span>
                                        <span>داده‌های مجموعه شما</span>
                                    </div>
                                </div>
                            @endif
                            <div class="vl-float-card vl-float-one"><span class="vl-float-icon green"><svg class="vl-icon"><use href="#vl-check"/></svg></span><span><strong>مدیریت یکپارچه</strong><small>اطلاعات در یک محیط</small></span></div>
                            <div class="vl-float-card vl-float-two"><span class="vl-float-icon purple"><svg class="vl-icon"><use href="#vl-layers"/></svg></span><span><strong>قابل توسعه</strong><small>متناسب با نیاز مجموعه</small></span></div>
                        </div>
                    </div>
                </section>
