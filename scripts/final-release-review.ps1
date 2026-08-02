param(
    [switch] $SkipSmoke,
    [switch] $SkipAudits,
    [switch] $AllowDirty
)

$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $PSScriptRoot
Set-Location $root

function Invoke-Step {
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

function Assert-CleanWorkingTree {
    $changes = @(& git status --porcelain)

    if ($LASTEXITCODE -ne 0) {
        throw 'Tidak dapat membaca status Git.'
    }

    if (-not $AllowDirty -and $changes.Count -gt 0) {
        throw "Working tree belum bersih:`n$($changes -join "`n")"
    }
}

try {
    Invoke-Step 'Git repository check' {
        & git rev-parse --is-inside-work-tree | Out-Null
    }

    $branch = (& git branch --show-current).Trim()

    if ([string]::IsNullOrWhiteSpace($branch)) {
        throw 'Branch Git aktif tidak dapat ditentukan.'
    }

    Write-Host "Branch aktif: $branch" -ForegroundColor DarkGray

    if ($branch -ne 'feature/mvp-hardening' -and $branch -ne 'main') {
        Write-Host (
            "PERINGATAN: review dijalankan pada branch '$branch'. " +
            "Branch rilis yang diharapkan adalah feature/mvp-hardening atau main."
        ) -ForegroundColor Yellow
    }

    Write-Host ""
    Write-Host '==> Clean working tree' -ForegroundColor Cyan
    Assert-CleanWorkingTree
    Write-Host 'LULUS: Clean working tree' -ForegroundColor Green

    if (Test-Path 'public\hot') {
        throw 'public/hot masih tersedia. Hentikan Vite dan hapus file tersebut.'
    }

    Write-Host 'LULUS: Vite hot file tidak tersedia' -ForegroundColor Green

    $qualityArgs = @(
        '-NoProfile',
        '-ExecutionPolicy',
        'Bypass',
        '-File',
        'scripts\quality-gate.ps1',
        '-Mode',
        'Full'
    )

    if ($SkipAudits) {
        $qualityArgs += '-SkipAudits'
    }

    Invoke-Step 'Full quality gate' {
        & powershell @qualityArgs
    }

    if (-not $SkipSmoke) {
        Invoke-Step 'Production smoke test' {
            & powershell `
                -NoProfile `
                -ExecutionPolicy Bypass `
                -File scripts\production-smoke.ps1
        }
    } else {
        Write-Host ""
        Write-Host 'DILEWATI: Production smoke test' -ForegroundColor Yellow
    }

    Invoke-Step 'Migration status' {
        & php artisan migrate:status
    }

    Invoke-Step 'Scheduler list' {
        & php artisan schedule:list
    }

    $commit = (& git rev-parse --short HEAD).Trim()

    Write-Host ""
    Write-Host 'FINAL RELEASE REVIEW LULUS.' -ForegroundColor Green
    Write-Host "Branch : $branch" -ForegroundColor Green
    Write-Host "Commit : $commit" -ForegroundColor Green
    Write-Host (
        'Lanjutkan UAT manual dan merge hanya setelah seluruh ' +
        'test case CRITICAL/HIGH berstatus PASS.'
    ) -ForegroundColor Green

    exit 0
} catch {
    Write-Host ""
    Write-Host "FINAL RELEASE REVIEW GAGAL: $($_.Exception.Message)" -ForegroundColor Red
    exit 1
}
