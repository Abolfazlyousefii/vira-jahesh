param(
    [ValidateSet('seed', 'owner', 'serve')]
    [string]$Action = 'seed',
    [string]$OwnerEmail = ''
)
$ErrorActionPreference = 'Stop'
$root = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
$db = Join-Path $root 'database\vira-demo.sqlite'
$previous = @{}
foreach ($key in @('APP_ENV','DB_CONNECTION','DB_DATABASE')) {
    $previous[$key] = [Environment]::GetEnvironmentVariable($key, 'Process')
}
try {
    Set-Location $root
    $env:APP_ENV = 'local'
    $env:DB_CONNECTION = 'sqlite'
    $env:DB_DATABASE = $db
    if ($Action -eq 'seed') {
        if (-not (Test-Path $db)) { New-Item -Path $db -ItemType File | Out-Null }
        php artisan migrate --force
        if ($LASTEXITCODE -ne 0) { throw 'Migration failed. Demo seed cancelled.' }
        php artisan db:seed --class=DatabaseSeeder --force
        if ($LASTEXITCODE -ne 0) { throw 'Permission seeding failed.' }
        php artisan db:seed --class=DemoRetailSeeder --force
        if ($LASTEXITCODE -ne 0) { throw 'Demo seeding failed.' }
        Write-Host 'Demo SQLite is ready. To create a demo login run: .\scripts\vira-demo.ps1 -Action owner -OwnerEmail you@example.test'
    } elseif ($Action -eq 'owner') {
        if (-not (Test-Path $db)) { throw 'First run -Action seed.' }
        if (-not $OwnerEmail) { throw 'Supply -OwnerEmail your chosen email.' }
        php artisan vira:owner $OwnerEmail
    } else {
        if (-not (Test-Path $db)) { throw 'First run -Action seed.' }
        Write-Host 'Starting isolated demo on http://127.0.0.1:8018/panel'
        php artisan serve --host=127.0.0.1 --port=8018
    }
} finally {
    foreach ($key in $previous.Keys) {
        [Environment]::SetEnvironmentVariable($key, $previous[$key], 'Process')
    }
}
