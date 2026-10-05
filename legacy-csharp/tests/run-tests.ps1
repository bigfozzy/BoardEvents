<#
    Локальный запуск тестов. CI не используется.

    Сборка идёт напрямую через Roslyn csc, потому что MSBuild в этом проекте
    не работает: XHE.dll подключается по абсолютному HintPath, который
    существует только на машине автора.

    Использование:
        pwsh -File tests\run-tests.ps1
        pwsh -File tests\run-tests.ps1 -RepoRoot "C:\путь\к\репозиторию"

    Пути к компилятору и пакетам задаются параметрами или переменными
    окружения (CSC, NUGET).
#>
[CmdletBinding()]
param(
    [string] $RepoRoot,
    [string] $CscPath,
    [string] $NuGetRoot
)

$ErrorActionPreference = 'Stop'

# --- настройки по умолчанию -------------------------------------------
if (-not $CscPath) {
    $CscPath = 'C:\Program Files (x86)\Microsoft Visual Studio\2019\BuildTools\MSBuild\Current\Bin\Roslyn\csc.exe'
}
if (-not $NuGetRoot) { $NuGetRoot = 'E:\Nuget' }

if (-not $RepoRoot) {
    # tests\ -> legacy-csharp\
    $RepoRoot = Split-Path -Parent $PSScriptRoot
}
$RepoRoot = (Resolve-Path -LiteralPath $RepoRoot).Path

# Исходники C#-версии лежат прямо в legacy-csharp\ (после переноса из Board Events\)
$proj = $RepoRoot
if (-not (Test-Path -LiteralPath (Join-Path $proj 'Main.cs'))) {
    Write-Host "Не найден Main.cs в папке проекта: $proj" -ForegroundColor Red
    Write-Host "Ожидается legacy-csharp\Main.cs"
    exit 1
}

if (-not (Test-Path -LiteralPath $CscPath)) {
    Write-Host "Не найден компилятор: $CscPath" -ForegroundColor Red
    Write-Host "Передайте путь параметром -CscPath или переменной CSC."
    exit 1
}

# Папка сборки - в корне репозитория, чтобы не смешивать с robot\
$RootDir = Split-Path -Parent $RepoRoot

$out = Join-Path $RootDir 'tests-out'
if (-not (Test-Path -LiteralPath $out)) {
    New-Item -ItemType Directory -Path $out | Out-Null
}
Get-ChildItem -LiteralPath $out -Filter *.rsp -ErrorAction SilentlyContinue |
    Remove-Item -Force

# --- сборка приложения -------------------------------------------------
Write-Host '[1/2] Сборка приложения...' -ForegroundColor Cyan

$binDir = Join-Path $proj 'bin\x86\Debug'

$frameworkRefs = @(
    'System.dll', 'System.Core.dll', 'System.Drawing.dll', 'System.Windows.Forms.dll',
    'System.Web.dll', 'System.Data.dll', 'System.Xml.dll', 'System.Net.Http.dll',
    'System.Deployment.dll', 'Microsoft.CSharp.dll', 'System.Xml.Linq.dll',
    'System.Data.DataSetExtensions.dll'
)

$packageRefs = @(
    'newtonsoft.json\13.0.3\lib\net45\Newtonsoft.Json.dll',
    'cefsharp.common\120.2.70\lib\net462\CefSharp.dll',
    'cefsharp.common\120.2.70\lib\net462\CefSharp.Core.dll',
    'cefsharp.winforms\120.2.70\lib\net462\CefSharp.WinForms.dll',
    'quartz\3.8.0\lib\net462\Quartz.dll'
)

$appDll = Join-Path $out 'Board Events.dll'

$rsp = New-Object System.Collections.Generic.List[string]
$rsp.Add('/nologo')
$rsp.Add('/target:library')
$rsp.Add('/langversion:7.3')
$rsp.Add('/out:"' + $appDll + '"')

foreach ($r in $frameworkRefs) { $rsp.Add('/r:"' + $r + '"') }

# Quartz берем только из Nuget. В bin\x86\Debug лежит старая копия
# 2.4.1, и ссылка на нее вместе с 3.8.0 дает CS0433 - тип неоднозначен.
foreach ($n in @('Common.Logging.dll', 'Common.Logging.Core.dll', 'XHE.dll')) {
    $p = Join-Path $binDir $n
    if (Test-Path -LiteralPath $p) { $rsp.Add('/r:"' + $p + '"') }
    else { Write-Host "  пропущена ссылка (нет файла): $p" -ForegroundColor DarkYellow }
}

foreach ($n in $packageRefs) {
    $p = Join-Path $NuGetRoot $n
    if (Test-Path -LiteralPath $p) { $rsp.Add('/r:"' + $p + '"') }
    else { Write-Host "  пропущена ссылк�� (нет пакета): $p" -ForegroundColor DarkYellow }
}

# исходники приложения - все .cs, кроме каталога тестов
$appFiles = @(Get-ChildItem -LiteralPath $proj -Recurse -Filter *.cs |
    Where-Object { $_.FullName -notlike '*\Tests\*' -and $_.FullName -notlike '*\bin\*' } |
    ForEach-Object { '"' + $_.FullName + '"' })

foreach ($f in $appFiles) { $rsp.Add([string]$f) }

$appRsp = Join-Path $out 'app.rsp'
[System.IO.File]::WriteAllLines($appRsp, $rsp)

& $CscPath "@$appRsp"
if ($LASTEXITCODE -ne 0) {
    Write-Host 'Ошибка сборки приложения' -ForegroundColor Red
    exit 1
}

# --- сборка тестов -----------------------------------------------------
Write-Host '[2/2] Сборка тестов...' -ForegroundColor Cyan

$testExe = Join-Path $out 'BoardEvents.Tests.exe'
$testRsp = @(
    '/nologo'
    '/target:exe'
    '/langversion:7.3'
    ('/out:"' + $testExe + '"')
    '/r:System.dll'
    '/r:System.Core.dll'
    ('/r:"' + $appDll + '"')
    ('"' + (Join-Path $proj 'Tests\Tests.cs') + '"')
)

$testRspPath = Join-Path $out 'tests.rsp'
[System.IO.File]::WriteAllLines($testRspPath, $testRsp)

& $CscPath "@$testRspPath"
if ($LASTEXITCODE -ne 0) {
    Write-Host 'Ошибка сборки тестов' -ForegroundColor Red
    exit 1
}

# --- запуск ------------------------------------------------------------
Write-Host ''
Write-Host 'Запуск тестов...' -ForegroundColor Cyan
Write-Host '---------------------------------------------'

& $testExe $RepoRoot
$code = $LASTEXITCODE

Write-Host ''
if ($code -eq 0) {
    Write-Host 'Тесты пройдены.' -ForegroundColor Green
} else {
    Write-Host "Тесты провалены, код возврата $code" -ForegroundColor Red
}

exit $code