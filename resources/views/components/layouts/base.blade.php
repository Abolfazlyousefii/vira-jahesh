@props(['title' => 'ویرا — یکپارچه با جهش'])
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="color-scheme" content="light dark">
    <title>{{ $title }}</title>
    <link rel="icon" href="{{ asset('vira.svg') }}" type="image/svg+xml">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body>
    <div id="jstudio" x-data="workspace" data-mode="light">
        {{ $slot }}
    </div>
    @livewireScriptConfig
</body>
</html>
