<div class="v-metric">{{ number_format($data['value']) }} @if($data['unit'] ?? null)<small>{{ $data['unit'] }}</small>@endif</div>
<p class="s-muted">{{ $data['description'] }}</p>
<a class="v-metric-link" href="{{ route($data['route']) }}">{{ $data['label'] }} ←</a>
