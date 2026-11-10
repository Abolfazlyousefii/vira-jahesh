<section class="panel task-panel" aria-label="نیازمند رسیدگی">
    <div class="panel-head">
        <div><div class="panel-title"><i data-lucide="clock"></i> نیازمند رسیدگی</div><span class="panel-subtitle">پیگیری‌هایی که موعدشان رسیده است</span></div>
        <a class="text-link" href="{{ route('panel.crm') }}">مشاهده همه <i data-lucide="arrow-left"></i></a>
    </div>
    <div class="task-summary">
        <div class="task-summary-icon"><i data-lucide="clock-alert"></i></div>
        <div><strong>{{ number_format($data['count']) }} کار</strong><small>پیگیری سررسیدشده</small></div>
    </div>
    @if(count($data['items']))
        <div class="task-list v4-task-items">
            @foreach($data['items'] as $item)
                <div class="task-item">
                    <div class="task-type teal"><i data-lucide="contact-round"></i></div>
                    <div class="task-info"><strong>{{ $item['title'] }}</strong><small>پیگیری مشتری</small></div>
                    <span class="task-time">{{ $item['date'] }}</span>
                </div>
            @endforeach
        </div>
    @else
        <div class="v4-dashboard-empty">پیگیری عقب‌افتاده‌ای ندارید.</div>
    @endif
    <div class="task-footer"><a class="text-link" href="{{ route('panel.crm') }}">رفتن به پیگیری‌ها <i data-lucide="arrow-left"></i></a></div>
</section>
