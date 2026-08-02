param(
    [int] $Port = 8013,
    [switch] $SkipDatabase
)

$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $PSScriptRoot
Set-Location $root

$server = $null
$stdout = Join-Path $root 'storage\logs\production-smoke.stdout.log'
$stderr = Join-Path $root 'storage\logs\production-smoke.stderr.log'

function Invoke-NativeStep {
    param(
        [string] $Name,
        [scriptblock] $Action
    )

    Write-Host ""
    Write-Host "==> $Name" -ForegroundColor Cyan
    & $Action

    if ($LASTEXITCODE -ne 0) {
        throw "$Name gagal dengan exit code $LASTEXITCODE."
    }

    Write-Host "LULUS: $Name" -ForegroundColor Green
}

function Invoke-SmokeRequest {
    param(
        [string] $Uri,
        [int] $ExpectedStatus
    )

    try {
        $response = Invoke-WebRequest -Uri $Uri -UseBasicParsing
        $status = [int] $response.StatusCode
        $body = [string] $response.Content
        $headers = $response.Headers
    } catch {
        if ($null -eq $_.Exception.Response) {
            throw
        }

        $rawResponse = $_.Exception.Response
        $status = [int] $rawResponse.StatusCode
        $headers = $rawResponse.Headers
        $reader = New-Object System.IO.StreamReader(
            $rawResponse.GetResponseStream()
        )
        $body = $reader.ReadToEnd()
        $reader.Dispose()
    }

    if ($status -ne $ExpectedStatus) {
        throw "$Uri menghasilkan status $status, target $ExpectedStatus."
    }

    return [pscustomobject]@{
        Status = $status
        Body = $body
        Headers = $headers
    }
}

try {
    Invoke-NativeStep 'Clear local optimization cache' {
        & php artisan optimize:clear --ansi
    }

    Invoke-NativeStep 'Build frontend assets' {
        & npm run build
    }

    $env:APP_ENV = 'production'
    $env:APP_DEBUG = 'false'
    $env:APP_URL = 'https://laras.example.test'
    $env:APP_KEY = (& php -r "echo 'base64:'.base64_encode(random_bytes(32));")
    $env:LOG_LEVEL = 'warning'
    $env:SESSION_DRIVER = 'database'
    $env:SESSION_ENCRYPT = 'true'
    $env:SESSION_SECURE_COOKIE = 'true'
    $env:SESSION_HTTP_ONLY = 'true'
    $env:SESSION_SAME_SITE = 'lax'
    $env:CACHE_STORE = 'database'
    $env:QUEUE_CONNECTION = 'database'
    $env:LARAS_ELOQUENT_STRICT = 'false'
    $env:LARAS_QUERY_MONITORING = 'true'
    $env:LARAS_QUERY_RESPONSE_HEADERS = 'false'

    Invoke-NativeStep 'Create production optimization cache' {
        & php artisan optimize --ansi
    }

    $releaseArgs = @(
        'artisan',
        'laras:release-check',
        '--production',
        '--require-cache'
    )

    if ($SkipDatabase) {
        $releaseArgs += '--skip-database'
    }

    Invoke-NativeStep 'Release readiness command' {
        & php @releaseArgs
    }

    Remove-Item $stdout, $stderr -Force -ErrorAction SilentlyContinue

    $server = Start-Process `
        -FilePath 'php' `
        -ArgumentList @(
            'artisan',
            'serve',
            '--host=127.0.0.1',
            "--port=$Port"
        ) `
        -RedirectStandardOutput $stdout `
        -RedirectStandardError $stderr `
        -PassThru `
        -WindowStyle Hidden

    $baseUrl = "http://127.0.0.1:$Port"
    $ready = $false

    for ($attempt = 1; $attempt -le 30; $attempt++) {
        Start-Sleep -Milliseconds 500

        try {
            $health = Invoke-SmokeRequest `
                -Uri "$baseUrl/up" `
                -ExpectedStatus 200
            $ready = $true
            break
        } catch {
            if ($server.HasExited) {
                throw 'Server production smoke berhenti sebelum siap.'
            }
        }
    }

    if (-not $ready) {
        throw 'Server production smoke tidak siap dalam 15 detik.'
    }

    Write-Host 'LULUS: /up menghasilkan 200' -ForegroundColor Green

    $login = Invoke-SmokeRequest `
        -Uri "$baseUrl/login" `
        -ExpectedStatus 200

    if ([string]::IsNullOrWhiteSpace($login.Headers['X-Request-ID'])) {
        throw 'Header X-Request-ID tidak tersedia.'
    }

    if ([string]::IsNullOrWhiteSpace($login.Headers['Content-Security-Policy'])) {
        throw 'Header Content-Security-Policy tidak tersedia.'
    }

    $csp = [string] $login.Headers['Content-Security-Policy']

    if ($csp -match 'localhost:\*|127\.0\.0\.1:\*|\[::1\]:\*') {
        throw 'CSP production masih memuat sumber development.'
    }

    if ($null -ne $login.Headers['X-DB-Query-Count']) {
        throw 'Header metrik database masih terekspos pada production.'
    }

    Write-Host 'LULUS: /login dan security headers production' -ForegroundColor Green

    $missing = Invoke-SmokeRequest `
        -Uri "$baseUrl/halaman-tidak-ada" `
        -ExpectedStatus 404

    if ($missing.Body -notmatch 'Kode referensi') {
        throw 'Halaman 404 tidak menampilkan kode referensi.'
    }

    Write-Host 'LULUS: custom 404 page' -ForegroundColor Green
    Write-Host ""
    Write-Host 'PRODUCTION SMOKE TEST LULUS.' -ForegroundColor Green
} catch {
    Write-Host ""
    Write-Host "PRODUCTION SMOKE TEST GAGAL: $($_.Exception.Message)" -ForegroundColor Red

    if (Test-Path $stderr) {
        Write-Host "Log error: $stderr" -ForegroundColor Yellow
    }

    exit 1
} finally {
    if ($null -ne $server -and -not $server.HasExited) {
        Stop-Process -Id $server.Id -Force -ErrorAction SilentlyContinue
    }

    & php artisan optimize:clear --ansi | Out-Null
}

exit 0
