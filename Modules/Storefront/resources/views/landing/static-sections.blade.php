<div class="vl-trust-strip">
                <div class="vl-container vl-trust-inner">
                    <div><svg class="vl-icon"><use href="#vl-box"/></svg> مدیریت کالا و محصولات</div>
                    <div><svg class="vl-icon"><use href="#vl-warehouse"/></svg> انبار و موجودی</div>
                    <div><svg class="vl-icon"><use href="#vl-users"/></svg> مشتریان و ارتباطات</div>
                    <div><svg class="vl-icon"><use href="#vl-chart"/></svg> گزارش‌های مدیریتی</div>
                </div>
            </div>

            <section class="vl-section vl-section-soft" id="site-modules">
                <div class="vl-container">
                    <div class="vl-section-heading">
                        <span class="vl-section-kicker">بخش‌های نرم‌افزار</span>
                        <h2>ماژول‌های کاربردی برای مدیریت روزمره</h2>
                        <p>هر بخش مسئولیت مشخصی دارد و اطلاعات موردنیازش را از هسته مشترک نرم‌افزار دریافت می‌کند.</p>
                    </div>
                    <div class="vl-module-grid">
                        @foreach($modules as $module)
                            <article class="vl-module-card">
                                <span class="vl-module-icon {{ $module['tone'] }}"><svg class="vl-icon"><use href="#vl-{{ $module['icon'] }}"/></svg></span>
                                <div><h3>{{ $module['title'] }}</h3><p>{{ $module['description'] }}</p></div>
                                <span class="vl-module-arrow"><svg class="vl-icon"><use href="#vl-arrow-up-left"/></svg></span>
                            </article>
                        @endforeach
                    </div>
                    <p class="vl-module-note"><svg class="vl-icon"><use href="#vl-shield"/></svg> امکانات هر مجموعه بر اساس نیازهای کاری و ماژول‌های فعال‌شده در زمان راه‌اندازی مشخص می‌شود.</p>
                </div>
            </section>

            <section class="vl-section" id="site-workspace">
                <div class="vl-container vl-showcase">
                    <div class="vl-showcase-copy">
                        <span class="vl-section-kicker">یک محیط مدیریتی منسجم</span>
                        <h2>از اطلاعات کالا تا ارتباط با مشتری؛ همه‌چیز در دسترس شماست</h2>
                        <p>ویرا با هدف یکپارچه‌سازی فرایندهای کسب‌وکار طراحی شده است. مدیر مجموعه می‌تواند متناسب با ساختار کاری خود، بخش‌های موردنیاز را در یک محیط مدیریتی دنبال کند.</p>
                        <div class="vl-check-list">
                            <div><span><svg class="vl-icon"><use href="#vl-check"/></svg></span> دسترسی منظم به اطلاعات و عملیات مجموعه</div>
                            <div><span><svg class="vl-icon"><use href="#vl-check"/></svg></span> ساختار ماژولار و قابلیت توسعه در مراحل بعدی</div>
                            <div><span><svg class="vl-icon"><use href="#vl-check"/></svg></span> سطح دسترسی متناسب با نقش کاربران</div>
                            <div><span><svg class="vl-icon"><use href="#vl-check"/></svg></span> تجربه یکپارچه در بخش‌های مختلف نرم‌افزار</div>
                        </div>
                        <a class="vl-btn vl-btn-primary" href="{{ route('login') }}">ورود به فضای مدیریت <svg class="vl-icon"><use href="#vl-arrow-left"/></svg></a>
                    </div>

                    <div class="vl-screen-wrap">
                        <div class="vl-screen">
                            <div class="vl-screen-bar"><span class="vl-screen-dots"><i></i><i></i><i></i></span><strong>پیش‌نمایش فضای کاری ویرا</strong><span>مدیر مجموعه</span></div>
                            <div class="vl-screen-body">
                                <aside class="vl-screen-sidebar">
                                    <strong>ویرا</strong>
                                    <span class="active"></span><span></span><span></span><span></span><span></span><span></span>
                                </aside>
                                <div class="vl-screen-content">
                                    <div class="vl-screen-welcome"><strong>فضای کاری مجموعه</strong><span class="vl-screen-avatar">م</span></div>
                                    <div class="vl-screen-tiles">
                                        <div><span class="vl-screen-tile-icon purple"><svg class="vl-icon"><use href="#vl-box"/></svg></span><strong>کالاهای نرم‌افزار</strong><small>اطلاعات و تنوع‌ها</small></div>
                                        <div><span class="vl-screen-tile-icon green"><svg class="vl-icon"><use href="#vl-warehouse"/></svg></span><strong>انبار و موجودی</strong><small>اسناد و گردش کالا</small></div>
                                        <div><span class="vl-screen-tile-icon blue"><svg class="vl-icon"><use href="#vl-users"/></svg></span><strong>مشتریان و CRM</strong><small>پرونده و پیگیری</small></div>
                                    </div>
                                    <div class="vl-screen-table">
                                        <div class="vl-screen-table-head"><strong>دسترسی سریع به بخش‌ها</strong><span>نمای کلی</span></div>
                                        <div class="vl-screen-row"><span>فروش و فاکتورها</span><b>بخش عملیاتی</b></div>
                                        <div class="vl-screen-row"><span>گزارش‌های مدیریتی</span><b>گزارش و تحلیل</b></div>
                                        <div class="vl-screen-row"><span>مدیریت کارکنان</span><b>سطح دسترسی</b></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="vl-section vl-section-soft" id="site-about">
                <div class="vl-container">
                    <div class="vl-section-heading">
                        <span class="vl-section-kicker">چرا ویرا؟</span>
                        <h2>مدیریت ساده‌تر، دید شفاف‌تر، توسعه‌پذیری بیشتر</h2>
                        <p>ویرا بستری برای نظم‌دادن به اطلاعات و فرایندهای کسب‌وکار است؛ با همراهی مجموعه جهش در مسیر راه‌اندازی و توسعه.</p>
                    </div>
                    <div class="vl-benefit-grid">
                        <article><span>۰۱ / یکپارچگی</span><h3>اطلاعات متمرکز</h3><p>بخش‌های مختلف را در یک محیط مدیریتی دنبال کنید و پراکندگی ابزارها را کاهش دهید.</p></article>
                        <article><span>۰۲ / انعطاف</span><h3>متناسب با کسب‌وکار</h3><p>امکانات و ماژول‌های موردنیاز بر اساس نیاز و اولویت‌های مجموعه بررسی می‌شوند.</p></article>
                        <article><span>۰۳ / توسعه</span><h3>آماده رشد</h3><p>ساختار ماژولار، مسیر توسعه تدریجی قابلیت‌ها و افزودن بخش‌های جدید را فراهم می‌کند.</p></article>
                        <article><span>۰۴ / همراهی</span><h3>همراهی مجموعه جهش</h3><p>در مسیر بررسی نیاز، راه‌اندازی و توسعه نرم‌افزار از همراهی تیم جهش استفاده می‌کنید.</p></article>
                    </div>
                </div>
            </section>

            <section class="vl-section" id="site-process">
                <div class="vl-container">
                    <div class="vl-section-heading">
                        <span class="vl-section-kicker">شروع همکاری</span>
                        <h2>از شناخت نیاز تا راه‌اندازی ویرا</h2>
                        <p>فرایند تهیه و راه‌اندازی نرم‌افزار با شناخت دقیق‌تر نیازهای مجموعه شما آغاز می‌شود.</p>
                    </div>
                    <div class="vl-steps">
                        <article><span>۱</span><h3>درخواست مشاوره</h3><p>نیاز اولیه و ساختار کاری مجموعه خود را مطرح می‌کنید.</p></article>
                        <article><span>۲</span><h3>بررسی نیازها</h3><p>فرایندهای کاری و ماژول‌های موردنیاز کسب‌وکار بررسی می‌شوند.</p></article>
                        <article><span>۳</span><h3>راه‌اندازی و تحویل</h3><p>نرم‌افزار بر اساس محدوده توافق‌شده آماده و راه‌اندازی می‌شود.</p></article>
                        <article><span>۴</span><h3>مدیریت کسب‌وکار</h3><p>مدیر مجموعه از طریق پنل مدیریت به امکانات فعال‌شده دسترسی خواهد داشت.</p></article>
                    </div>
                </div>
            </section>

            <section class="vl-section vl-section-soft" id="site-faq">
                <div class="vl-container">
                    <div class="vl-section-heading">
                        <span class="vl-section-kicker">پرسش‌های متداول</span>
                        <h2>پاسخ پرسش‌های شما درباره ویرا</h2>
                        <p>برای آشنایی بهتر با ساختار نرم‌افزار، پاسخ پرسش‌های پرتکرار را بخوانید.</p>
                    </div>
                    <div class="vl-faq-list">
                        <details class="vl-faq" open>
                            <summary>ویرا برای چه کسب‌وکارهایی مناسب است؟ <svg class="vl-icon"><use href="#vl-chevron"/></svg></summary>
                            <p>برای کسب‌وکارهایی که می‌خواهند بخش‌هایی مانند کالا، انبار، فروش، مشتریان و ارتباطات را در یک محیط مدیریتی منسجم‌تر مدیریت کنند. محدوده امکانات مناسب هر مجموعه در جلسه مشاوره بررسی می‌شود.</p>
                        </details>
                        <details class="vl-faq">
                            <summary>آیا همه ماژول‌ها برای تمام مشتریان فعال می‌شوند؟ <svg class="vl-icon"><use href="#vl-chevron"/></svg></summary>
                            <p>خیر. ماژول‌ها و امکانات موردنیاز هر کسب‌وکار بر اساس نیاز و محدوده توافق‌شده بررسی و فعال می‌شوند.</p>
                        </details>
                        <details class="vl-faq">
                            <summary>چطور می‌توانم برای تهیه ویرا مشاوره بگیرم؟ <svg class="vl-icon"><use href="#vl-chevron"/></svg></summary>
                            <p>برای هماهنگی جلسه معرفی و بررسی نیازهای کسب‌وکار، با مجموعه جهش تماس بگیرید. در صورت ثبت شماره تماس مستقیم، همین صفحه راه تماس را نیز نمایش می‌دهد.</p>
                        </details>
                        <details class="vl-faq">
                            <summary>مشتریان فعلی ویرا از کجا وارد نرم‌افزار می‌شوند؟ <svg class="vl-icon"><use href="#vl-chevron"/></svg></summary>
                            <p>مشتریان فعلی می‌توانند از دکمه «ورود به پنل مدیریت» در بالای صفحه استفاده کنند. این مسیر به صفحه ورود اصلی نرم‌افزار متصل است.</p>
                        </details>
                    </div>
                </div>
            </section>
