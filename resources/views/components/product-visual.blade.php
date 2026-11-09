@props(['product'])
<span {{ $attributes->class(['v-product-thumb']) }}>@if($product->imageUrl())<img src="{{ $product->imageUrl() }}" alt="{{ $product->name }}" width="80" height="80" loading="lazy">@else<i data-lucide="package" aria-hidden="true"></i>@endif</span>
