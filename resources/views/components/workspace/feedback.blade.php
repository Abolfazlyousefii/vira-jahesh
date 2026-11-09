@props(['notice' => ''])
@if($notice)<div class="v-notice" role="status"><i data-lucide="check-check"></i><span>{{ $notice }}</span></div>@endif
@if($errors->any())<div class="v-error-summary" role="alert"><strong>ثبت انجام نشد.</strong> ورودی‌های مشخص‌شده را بررسی کنید.
    <ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
</div>@endif
