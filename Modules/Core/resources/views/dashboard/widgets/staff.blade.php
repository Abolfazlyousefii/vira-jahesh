<div class="s-kpi" style="min-height:110px">
    <span class="s-muted">تعداد حساب‌های ثبت‌شدهٔ کارکنان</span>
    <div class="s-value">{{ \Morilog\Jalali\CalendarUtils::convertNumbers((string) $data['count']) }}</div>
    <span class="s-muted s-small">این عدد، تعداد مشتریان فروشگاه نیست.</span>
    @if(auth()->user()->hasRole('owner') && auth()->user()->can('users.manage'))<a class="v-metric-link" href="{{ route('panel.staff') }}">مدیریت همکاران و دسترسی‌ها ←</a>@endif
</div>
