@php
    $status = $status ?? 500;
    $heading = $heading ?? 'درخواست انجام نشد';
    $description = $description ?? 'لطفاً کمی بعد دوباره تلاش کنید.';
    $tone = $tone ?? 'teal';
@endphp
<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $heading }} | ویرا</title>
    <style>
        :root { color-scheme: light; }
        * { box-sizing: border-box; }
        html,body { margin:0; min-height:100%; }
        body { min-height:100dvh; display:grid; place-items:center; background:linear-gradient(145deg,#f5faf8,#eff4f7); color:#254652; font-family:Vazir,Tahoma,sans-serif; line-height:2; padding:24px; }
        .error-panel { width:min(100%,480px); background:#fff; border:1px solid #e0ece9; border-radius:22px; box-shadow:0 18px 70px #1e435410; padding:32px 30px; text-align:center; }
        .brand { display:flex; align-items:center; justify-content:center; gap:8px; margin-bottom:30px; color:#23515d; font-size:18px; font-weight:800; }
        .brand-mark { width:33px; height:33px; border-radius:12px; background:#0b998d; display:grid; place-items:center; color:#fff; }
        .status { display:inline-flex; align-items:center; justify-content:center; height:38px; min-width:78px; padding:3px 14px; background:#e7f7f3; color:#0c8a7b; font-size:18px; border-radius:13px; font-weight:800; direction:ltr; }
        .error-panel.rose .status { background:#fff0f1; color:#bd4e58; }
        .error-panel.amber .status { background:#fff6e7; color:#aa742b; }
        h1 { margin:18px 0 6px; font-size:21px; line-height:1.8; color:#173b49; }
        p { margin:0 auto; max-width:360px; color:#6b8790; font-size:13px; }
        .actions { margin-top:28px; display:flex; gap:11px; justify-content:center; flex-wrap:wrap; }
        a { border-radius:11px; min-height:44px; display:inline-flex; align-items:center; justify-content:center; padding:8px 18px; text-decoration:none; font-size:12px; font-weight:700; }
        .primary { background:#0a9588; color:#fff; }
        .secondary { background:#f1f7f6; color:#2c6c72; border:1px solid #dcebe8; }
        a:focus-visible { outline:3px solid #75c4bc; outline-offset:3px; }
        small { display:block; margin-top:24px; font-size:10px; color:#9bb0b5; }
        @media (max-width:420px) { .error-panel { padding:26px 18px; } h1 { font-size:18px; } }
    </style>
</head>
<body>
<main class="error-panel {{ $tone }}" role="main">
    <div class="brand"><span class="brand-mark" aria-hidden="true">و</span><span>ویرا</span></div>
    <span class="status" aria-label="کد وضعیت {{ $status }}">{{ $status }}</span>
    <h1>{{ $heading }}</h1>
    <p>{{ $description }}</p>
    <nav class="actions" aria-label="راه‌های ادامه">
        <a class="primary" href="{{ url('/') }}">بازگشت به صفحه اصلی</a>
        @if($status === 419)
            <a class="secondary" href="{{ url('/login') }}">ورود دوباره</a>
        @else
            <a class="secondary" href="{{ url('/login') }}">ورود به حساب</a>
        @endif
    </nav>
    <small>پشتیبانی ویرا · اطلاعات فنی خطا برای بررسی در گزارش‌های سرور ثبت می‌شود.</small>
</main>
</body>
</html>
