<div class="s-statusgrid">
    @foreach($data['modules'] as $module)
        <div class="s-modulebox">
            <span class="s-avatar"><i data-lucide="{{ $module['icon'] }}"></i></span>
            <div>
                <strong>{{ $module['label'] }}</strong>
                <div class="s-small s-muted">{{ $module['available'] ? 'در دسترس' : 'غیرفعال یا وابستگی ناقص' }}</div>
            </div>
        </div>
    @endforeach
</div>
