<div>
    <div class="s-heading">
        <div><h1>مدیریت صفحهٔ اصلی سایت</h1><p class="s-muted">محتوا، ترتیب و وضعیت نمایش سکشن‌های عمومی را تنظیم کنید.</p></div>
        <a class="s-btn s-mainbtn" href="{{ route('storefront.home') }}" target="_blank" rel="noopener">مشاهدهٔ سایت <i data-lucide="arrow-left"></i></a>
    </div>
    @if($notice)
        <p class="s-pill green" role="status" style="margin:12px 0">{{ $notice }}</p>
    @endif
    <div class="vira-editor-layout">
        <section class="s-panel" aria-label="سکشن‌های صفحهٔ اصلی">
            <div class="s-panelhead"><h2>ساختار صفحه</h2><span class="s-pill">۳ سکشن</span></div>
            @foreach($sections as $section)
                <div class="vira-editor-section {{ $selected === $section['key'] ? 'is-selected' : '' }}" wire:key="section-{{ $section['key'] }}">
                    <button type="button" class="vira-editor-select" wire:click="selectSection('{{ $section['key'] }}')" aria-pressed="{{ $selected === $section['key'] ? 'true' : 'false' }}">
                        <strong>{{ ['hero' => 'Hero', 'features' => 'Features', 'cta' => 'CTA'][$section['key']] }}</strong>
                        <small>{{ ['hero' => 'بنر اصلی', 'features' => 'ویژگی‌های معرفی', 'cta' => 'دعوت به اقدام'][$section['key']] }}</small>
                    </button>
                    <div class="vira-editor-section-actions">
                        <button type="button" wire:click="moveSection('{{ $section['key'] }}', 'up')" @disabled($loop->first) aria-label="بالا بردن سکشن {{ $section['key'] }}">↑</button>
                        <button type="button" wire:click="moveSection('{{ $section['key'] }}', 'down')" @disabled($loop->last) aria-label="پایین بردن سکشن {{ $section['key'] }}">↓</button>
                        <button type="button" wire:click="toggleSection('{{ $section['key'] }}')">{{ $section['is_visible'] ? 'پنهان' : 'نمایش' }}</button>
                    </div>
                </div>
            @endforeach
        </section>
        <section class="s-panel">
            <div class="s-panelhead"><h2>ویرایش {{ ['hero' => 'Hero', 'features' => 'Features', 'cta' => 'CTA'][$selected] }}</h2></div>
            <form wire:submit="save">
                @if($selected === 'hero')
                    <label class="vira-editor-field">عنوان اصلی<input class="s-input" wire:model.live.debounce.300ms="form.title" maxlength="120" required></label>
                    @error('form.title')<p class="s-error">{{ $message }}</p>@enderror
                    <label class="vira-editor-field">توضیح<textarea class="s-input" wire:model.live.debounce.300ms="form.description" maxlength="320" required></textarea></label>
                    @error('form.description')<p class="s-error">{{ $message }}</p>@enderror
                    <label class="vira-editor-field">متن دکمه<input class="s-input" wire:model="form.button" maxlength="60" required></label>
                    @error('form.button')<p class="s-error">{{ $message }}</p>@enderror
                    <label class="vira-editor-field">مقصد دکمه
                        <select class="s-input" wire:model="form.href">
                            <option value="#site-features">ویژگی‌ها</option>
                            <option value="#site-cta">دعوت به اقدام</option>
                            <option value="#site-top">ابتدای صفحه</option>
                        </select>
                    </label>
                    @error('form.href')<p class="s-error">{{ $message }}</p>@enderror
                    <label class="vira-editor-field">تصویر Hero (اختیاری، JPEG/PNG/WebP، حداکثر ۲ مگابایت)
                        <input type="file" wire:model="heroImage" accept="image/jpeg,image/png,image/webp">
                    </label>
                    @error('heroImage')<p class="s-error">{{ $message }}</p>@enderror
                    <div wire:loading wire:target="heroImage" class="s-muted">در حال آماده‌سازی تصویر…</div>
                @elseif($selected === 'features')
                    @foreach(['first' => 'ویژگی اول', 'second' => 'ویژگی دوم', 'third' => 'ویژگی سوم'] as $key => $label)
                        <label class="vira-editor-field">{{ $label }}<input class="s-input" wire:model.live.debounce.300ms="form.{{ $key }}" maxlength="90" required></label>
                        @error('form.'.$key)<p class="s-error">{{ $message }}</p>@enderror
                    @endforeach
                @else
                    <label class="vira-editor-field">عنوان دعوت به اقدام<input class="s-input" wire:model.live.debounce.300ms="form.title" maxlength="120" required></label>
                    @error('form.title')<p class="s-error">{{ $message }}</p>@enderror
                    <label class="vira-editor-field">توضیح<textarea class="s-input" wire:model.live.debounce.300ms="form.description" maxlength="320" required></textarea></label>
                    @error('form.description')<p class="s-error">{{ $message }}</p>@enderror
                @endif
                <button class="s-btn s-mainbtn" type="submit" style="margin:12px 0">ذخیره و انتشار تغییرات</button>
            </form>
            <div class="vira-editor-preview" aria-label="پیش‌نمایش محتوای ویرایش‌شده">
                <strong>پیش‌نمایش محتوای سکشن انتخاب‌شده</strong>
                @if($selected === 'features')
                    <div class="vira-editor-preview-features">
                        <span>{{ $form['first'] ?? '' }}</span><span>{{ $form['second'] ?? '' }}</span><span>{{ $form['third'] ?? '' }}</span>
                    </div>
                @else
                    <h3>{{ $form['title'] ?? '' }}</h3><p>{{ $form['description'] ?? '' }}</p>
                    @if($selected === 'hero')<span class="s-pill">{{ $form['button'] ?? '' }}</span>@endif
                @endif
                <small>پیش‌نمایش در پنل منتشر نمی‌شود؛ فقط با ذخیره، سایت عمومی تغییر می‌کند.</small>
            </div>
        </section>
    </div>
</div>
