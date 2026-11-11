@props(['notice' => ''])
@if($notice)
    <div class="v-notice" role="status" aria-live="polite">
        <i data-lucide="check-check" aria-hidden="true"></i>
        <span>{{ $notice }}</span>
    </div>
@endif
@if($errors->any())
    <div class="v-error-summary" role="alert" aria-live="assertive">
        <span class="v-feedback-symbol" aria-hidden="true">!</span>
        <div class="v-feedback-content">
            <strong>چند مورد نیاز به اصلاح دارد.</strong>
            <p>فیلدهای مشخص‌شده را بررسی کنید و دوباره تلاش کنید.</p>
            <ul>
                @foreach(array_unique($errors->all()) as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
@endif
