@props(['title' => 'هنوز چیزی ثبت نشده', 'description' => '', 'icon' => 'package'])
<div class="v-empty"><span class="v-empty-icon"><i data-lucide="{{ $icon }}"></i></span><h2>{{ $title }}</h2><p class="s-muted">{{ $description }}</p>{{ $slot }}</div>
