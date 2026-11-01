@forelse($data['activities'] as $activity)
    <div class="s-timeline">
        <span class="s-timedot"></span>
        <div>
            <p>{{ $activity->description }}</p>
            <span class="s-muted s-small">{{ $activity->created_at->timezone('Asia/Tehran')->format('H:i') }}</span>
        </div>
    </div>
@empty
    <p class="s-muted">هنوز فعالیتی برای نمایش وجود ندارد.</p>
@endforelse
