<div>
    <div class="s-heading"><div><h1>نبض کسب‌وکار شما</h1><p class="s-muted">خوش آمدی {{ auth()->user()->name }}؛ همه‌چیز از اینجا شروع می‌شود.</p></div><span class="s-pill green"><i data-lucide="shield-check"></i>فضای کار فعال</span></div>
    <div class="s-tabs"><a class="s-link" href="{{ route('panel.dashboard') }}" style="padding:10px 0;border-bottom:2px solid var(--s-primary)">نمای کلی</a>@can('modules.manage')<a class="s-muted" href="{{ route('panel.modules') }}">ماژول‌های مجموعه</a>@endcan</div>
    <div class="s-welcome"><div><h2>یک شروع منظم، برای رشد بیشتر</h2><p>مشتریان، کالاها و فروش‌های مجموعه‌ات را در یک فضای کار مدیریت کن.</p></div><div class="s-welmark" aria-hidden="true"><span style="height:22px"></span><span style="height:35px"></span><span style="height:47px"></span><span style="height:65px"></span></div></div>
    <section class="s-kpis" aria-label="خلاصهٔ فضای کار">
        @foreach([['ماژول‌های در دسترس', $activeModules, 'blocks', 'var(--s-primary)', 'قابل انتخاب برای مجموعه'], ['کاربران مجموعه', $users, 'users', 'var(--s-blue)', 'حساب‌های ثبت‌شده'], ['رویدادهای اخیر', $activities->count(), 'activity', 'var(--s-green)', 'آخرین فعالیت‌های ثبت‌شده'], ['کانال‌های فروش', 2, 'store', 'var(--s-orange)', 'مسیر حضوری و اینترنتی']] as [$label,$value,$icon,$color,$caption])
        <div class="s-kpi" style="--k-color:{{ $color }}"><div class="s-kpihead"><span>{{ $label }}</span><span class="s-kpiicon"><i data-lucide="{{ $icon }}"></i></span></div><div class="s-value">{{ \Morilog\Jalali\CalendarUtils::convertNumbers((string)$value) }}</div><div class="s-metricfoot">{{ $caption }}</div></div>
        @endforeach
    </section>
    <div class="s-grid"><section class="s-panel"><div class="s-panelhead"><h2>بخش‌های فضای کار</h2><span class="s-pill">یکپارچه</span></div><div class="s-statusgrid">
        @foreach($modules as $module)
            @if(!in_array($module['name'], ['Core','Storefront']))
            <div class="s-modulebox"><div class="s-avatar"><i data-lucide="{{ $module['icon'] }}"></i></div><div><h3>{{ $module['label'] }}</h3><span class="s-small s-muted">{{ $module['available'] ? 'پایهٔ ماژول آماده است' : 'غیرفعال' }}</span></div></div>
            @endif
        @endforeach
    </div></section><section class="s-panel"><div class="s-panelhead"><h2>آخرین فعالیت‌ها</h2><i data-lucide="activity" class="s-muted"></i></div>
        @forelse($activities as $activity)
        <div class="s-timeline"><span class="s-timedot"></span><div><p>{{ $activity->description }}</p><span class="s-muted s-small">{{ $activity->created_at->timezone('Asia/Tehran')->format('H:i') }}</span></div></div>
        @empty<p class="s-muted s-small">هنوز فعالیتی ثبت نشده است.</p>@endforelse
    </section></div>
    <section class="s-panel"><div class="s-panelhead"><h2>مسیر فروش یکپارچه</h2><span class="s-muted s-small">توسعهٔ مرحله‌ای امکانات</span></div><div class="s-flow">
    @foreach([['store','سایت و صندوق','دریافت سفارش'],['contact-round','مشتریان','ارتباط و پیگیری'],['receipt-text','فروش','فاکتور و پرداخت'],['boxes','انبار','تأمین و تحویل']] as [$icon,$label,$description])
        <div class="s-flowitem"><div class="s-avatar"><i data-lucide="{{ $icon }}"></i></div><h3>{{ $label }}</h3><span class="s-muted s-small">{{ $description }}</span></div>
    @endforeach
    </div></section>
</div>
