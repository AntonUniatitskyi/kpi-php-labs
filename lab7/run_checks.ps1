# Автоматична перевірка практичної №7 (Windows PowerShell): виконує всі команди послідовно,
# показує кожну команду та її вивід, пише лог і зведену таблицю «до / після».
#
#   powershell -ExecutionPolicy Bypass -File .\run_checks.ps1
#   powershell -ExecutionPolicy Bypass -File .\run_checks.ps1 -Rows 500 -AbN 100
#
# Потрібні в PATH: php, ab (XAMPP: C:\xampp\php та C:\xampp\apache\bin), curl.exe (є у Windows 10+).
# Примітка: вбудований сервер PHP на Windows однопотоковий (PHP_CLI_SERVER_WORKERS не діє).
param(
    [int]$Rows = 1000,
    [int]$AbN  = 200,
    [int]$AbC  = 20,
    [int]$Port = 8089
)

$ErrorActionPreference = 'Continue'
Set-Location -Path $PSScriptRoot
[Console]::OutputEncoding = [System.Text.UTF8Encoding]::new($false)
$OutputEncoding = [System.Text.UTF8Encoding]::new($false)

$Base = "http://127.0.0.1:$Port"
$Api  = "$Base/api.php?resource=questions"

New-Item -ItemType Directory -Force -Path results | Out-Null
$Stamp   = Get-Date -Format 'yyyyMMdd_HHmmss'
$Log     = "results\run_$Stamp.log"
$Summary = "results\summary_$Stamp.md"

foreach ($tool in @('php', 'ab', 'curl.exe')) {
    if (-not (Get-Command $tool -ErrorAction SilentlyContinue)) {
        Write-Host "Не знайдено програму: $tool (додайте її в PATH)"
        exit 1
    }
}

Start-Transcript -Path $Log | Out-Null


function Step([string]$Title) {
    Write-Host ""
    Write-Host ("=" * 64)
    Write-Host $Title
    Write-Host ("=" * 64)
}

function Run([string]$Display, [scriptblock]$Block) {   # показати команду і виконати її
    Write-Host ""
    Write-Host "`$ $Display"
    & $Block
}

$script:T = ''; $script:M = ''; $script:Q = ''
$script:RPS = ''; $script:TPR = ''; $script:FAIL = ''
$script:Rows_ = New-Object System.Collections.Generic.List[string]

function Measure-One([string]$Mode) {      # один запит: час, пам'ять, кількість SQL (заголовки X-*)
    $url = "$Api&mode=$Mode"
    Write-Host ""
    Write-Host "`$ curl.exe -s -o NUL -D - `"$url`"  (лише заголовки X-*)"
    $h = (curl.exe -s -o NUL -D - $url) -join "`n"
    ($h -split "`n") | Where-Object { $_ -match '^X-' } | ForEach-Object { Write-Host ($_.Trim()) }
    $script:T = [regex]::Match($h, 'X-Time-Ms:\s*([\d.]+)').Groups[1].Value
    $script:M = [regex]::Match($h, 'X-Peak-Memory-MB:\s*([\d.]+)').Groups[1].Value
    $script:Q = [regex]::Match($h, 'X-SQL-Queries:\s*(\d+)').Groups[1].Value
}

function Measure-Ab([string]$Mode) {       # навантажувальний тест Apache Bench
    $url = "$Api&mode=$Mode"
    Write-Host ""
    Write-Host "`$ ab -n $AbN -c $AbC -q `"$url`""
    $out = (& ab -n $AbN -c $AbC -q $url 2>&1 | ForEach-Object { "$_" }) -join "`n"
    ($out -split "`n") |
        Where-Object { $_ -match 'Complete requests|Failed requests|Requests per second|Time per request' } |
        ForEach-Object { Write-Host ($_.Trim()) }
    $ml = [System.Text.RegularExpressions.RegexOptions]::Multiline
    $script:RPS  = [regex]::Match($out, 'Requests per second:\s+([\d.]+)').Groups[1].Value
    $script:TPR  = [regex]::Match($out, 'Time per request:\s+([\d.]+) \[ms\] \(mean\)\r?$', $ml).Groups[1].Value
    $script:FAIL = [regex]::Match($out, 'Failed requests:\s+(\d+)').Groups[1].Value
}

function Scenario([string]$Label, [string]$Mode) {
    Step "$Label  (mode=$Mode)"
    Measure-One $Mode
    Measure-Ab $Mode
    $script:Rows_.Add("| $Label | $($script:T) | $($script:M) | $($script:Q) | $($script:RPS) | $($script:TPR) | $($script:FAIL) |")
}

$server = $null
try {
    Step "0. Підготовка: чиста БД, $Rows питань, без індексу, запуск сервера"
    curl.exe -s -o NUL --max-time 1 "$Base/api.php?resource=foo"
    if ($LASTEXITCODE -eq 0) {
        Write-Host "Порт $Port уже зайнятий. Зупиніть сервер або задайте інший: -Port 8090"
        return
    }
    Run 'php tools.php reset'          { php tools.php reset }
    Run "php tools.php seed $Rows"     { php tools.php seed $Rows }
    Run 'php tools.php index drop'     { php tools.php index drop }

    Write-Host ""
    Write-Host "`$ php -S 127.0.0.1:$Port   (у фоні)"
    $server = Start-Process -FilePath php -ArgumentList @('-S', "127.0.0.1:$Port") -PassThru `
        -WindowStyle Hidden -RedirectStandardOutput 'results\server.out.log' -RedirectStandardError 'results\server.log'
    for ($i = 0; $i -lt 50; $i++) {
        curl.exe -s -o NUL --max-time 1 "$Base/api.php?resource=foo"
        if ($LASTEXITCODE -eq 0) { break }
        Start-Sleep -Milliseconds 200
    }

    Scenario 'ДО: N+1, без індексу' 'n1'

    Step '4. Індекс: EXPLAIN до і після'
    Run 'php tools.php explain'        { php tools.php explain }
    Run 'php tools.php index create'   { php tools.php index create }
    Run 'php tools.php explain'        { php tools.php explain }
    Scenario 'N+1, з індексом' 'n1'

    Scenario 'GROUP BY (без N+1), з індексом' 'group'

    Step '6. Кеш: холодний і теплий запит'
    Run 'php tools.php cache clear'    { php tools.php cache clear }
    Write-Host '(холодний запит: кеш порожній -> список + GROUP BY = 2 запити)'
    Measure-One 'cached'
    Write-Host '(теплий запит: результат countByType() з кешу -> лише список = 1 запит)'
    Measure-One 'cached'

    Scenario 'ПІСЛЯ: GROUP BY + кеш, з індексом' 'cached'

    Step '6.1 Інвалідація кешу після додавання питання'
    Write-Host '(статистика до додавання)'
    Run "curl.exe -s -X POST `"$Api&action=stats`"" { curl.exe -s -X POST "$Api&action=stats"; Write-Host '' }
    Write-Host '(додаємо питання типу text)'
    $tmp = Join-Path $env:TEMP 'survey_new_question.json'
    [System.IO.File]::WriteAllText($tmp, '{"text":"Тест інвалідації кешу","type":"text"}', [System.Text.UTF8Encoding]::new($false))
    Run "curl.exe -s -X POST `"$Api`" -H `"Content-Type: application/json`" --data-binary @$tmp" {
        curl.exe -s -X POST $Api -H 'Content-Type: application/json' --data-binary "@$tmp"; Write-Host ''
    }
    Write-Host '(статистика після: count для text має зрости на 1 одразу, а не через 60 с)'
    Run "curl.exe -s -X POST `"$Api&action=stats`"" { curl.exe -s -X POST "$Api&action=stats"; Write-Host '' }
    Step '7. Перевірка обробки некоректного вводу (без Warning, лише JSON)'
    Run "curl.exe -s -i -X POST `"$Api`" -d `"text[]=x&type=text`"" {
        curl.exe -s -i -X POST $Api -d 'text[]=x&type=text'; Write-Host ''
    }
    Run "curl.exe -s -i -g -X POST `"$Api&action[]=x`"" {
        curl.exe -s -i -g -X POST "$Api&action[]=x"; Write-Host ''
    }

    $phpVer = (php -r 'echo PHP_VERSION;')
    $lines = @(
        '# Зведені результати (практична №7)',
        '',
        "- Дата: $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')",
        "- PHP: $phpVer",
        "- Питань у таблиці: $Rows (+8 початкових)",
        "- ab: -n $AbN -c $AbC",
        '',
        '| Сценарій | Час, мс | Пам''ять, МБ | SQL-запитів | Req/s | Time per request, мс | Failed |',
        '|----------|--------:|------------:|------------:|------:|---------------------:|-------:|'
    ) + $script:Rows_
    [System.IO.File]::WriteAllLines((Join-Path $PSScriptRoot $Summary), $lines, [System.Text.UTF8Encoding]::new($false))

    Step 'Готово'
    Write-Host "Лог:             $Log"
    Write-Host "Лог сервера:     results\server.log"
    Write-Host "Зведена таблиця: $Summary"
    Write-Host ""
    $lines | ForEach-Object { Write-Host $_ }
}
finally {
    if ($server -and -not $server.HasExited) {
        Stop-Process -Id $server.Id -Force
    }
    Stop-Transcript | Out-Null
}