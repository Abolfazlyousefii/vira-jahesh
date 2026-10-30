<x-layouts.panel title="ماژول‌های مجموعه">
<div class="s-heading"><div><h1>ماژول‌های مجموعه</h1><p class="s-muted">وضعیت دسترسی و وابستگی بخش‌های ویرا</p></div><span class="s-pill">زیرساخت نسخهٔ آغازین</span></div>
<section class="s-panel"><div class="s-tablewrap"><table><thead><tr><th>ماژول</th><th>وابستگی‌ها</th><th>دسترسی</th><th>مرحله</th></tr></thead><tbody>
@foreach($modules as $module)<tr><td><div class="s-flex"><span class="s-avatar"><i data-lucide="{{ $module['icon'] }}"></i></span><strong>{{ $module['label'] }}</strong></div></td><td>{{ collect($module['requires'])->map(fn($name) => config('vira.modules.'.$name.'.label'))->join('، ') ?: 'مستقل' }}</td><td><span class="s-pill {{ $module['available'] ? 'green' : 'orange' }}">{{ $module['available'] ? 'فعال' : 'غیرفعال یا وابستگی ناقص' }}</span></td><td>{{ $module['name']==='Core' ? 'هستهٔ قابل استفاده' : 'آمادهٔ توسعهٔ امکانات' }}</td></tr>@endforeach
</tbody></table></div></section>
</x-layouts.panel>
