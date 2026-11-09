@props(['label', 'name' => null, 'hint' => null])
<label class="v-field">
    <span>{{ $label }}</span>
    {{ $slot }}
    @if($hint)<small class="s-muted">{{ $hint }}</small>@endif
    @if($name)@error($name)<small class="s-error" role="alert">{{ $message }}</small>@enderror @endif
</label>
