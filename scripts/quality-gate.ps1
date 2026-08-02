param(
    [ValidateSet('Dirty', 'Full')]
    [string] $Mode = 'Dirty',

    [switch] $SkipAudits
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

function Assert-NoForbiddenTrackedFiles {
    $tracked = @(& git ls-files)

    if ($LASTEXITCODE -ne 0) {
        throw 'Tidak dapat membaca daftar file Git.'
    }

    $forbidden = @(
        $tracked | Where-Object {
            $path = $_ -replace '\\', '/'

            $path -eq '.env' -or
            $path -eq 'public/hot' -or
            $path -eq 'database/database.sqlite' -or
            (
                $path -like 'storage/logs/*' -and
                $path -ne 'storage/logs/.gitignore'
            )
        }
    )

    if ($forbidden.Count -gt 0) {
        throw "File terlarang terlacak Git:`n$(
            $forbidden -join "`n"
        )"
    }
}

try {
    Invoke-Step 'Git whitespace check' {
        & git diff --check
    }

    Write-Host ""
    Write-Host '==> Forbidden tracked files' -ForegroundColor Cyan
    Assert-NoForbiddenTrackedFiles
    Write-Host 'LULUS: Forbidden tracked files' -ForegroundColor Green

    Invoke-Step 'Composer validate' {
        & composer validate --strict --no-interaction
    }

    Invoke-Step 'PHP platform requirements' {
        & cmd.exe /d /c 'composer check-platform-reqs --no-ansi 2>&1'
    }

    if ($Mode -eq 'Dirty') {
        Invoke-Step 'Laravel Pint (dirty files)' {
            & php vendor\bin\pint --dirty --test
        }
    } else {
        Invoke-Step 'Laravel Pint (full project)' {
            & php vendor\bin\pint --test
        }
    }

    Invoke-Step 'Clear Laravel caches' {
        & php artisan optimize:clear --ansi
    }

    Invoke-Step 'Laravel test suite' {
        & php artisan test
    }

    Invoke-Step 'Scheduler compilation' {
        & php artisan schedule:list
    }

    Invoke-Step 'Frontend production build' {
        & npm run build
    }

    if (-not $SkipAudits) {
        Invoke-Step 'Composer security audit' {
            & composer audit --locked --abandoned=report --no-interaction
        }

        Invoke-Step 'npm full security audit' {
            & npm audit --audit-level=high
        }

        Invoke-Step 'npm production security audit' {
            & npm audit --omit=dev --audit-level=high
        }
    }

    Write-Host ""
    Write-Host 'QUALITY GATE LULUS.' -ForegroundColor Green
    exit 0
} catch {
    Write-Host ""
    Write-Host "QUALITY GATE GAGAL: $($_.Exception.Message)" -ForegroundColor Red
    exit 1
}
