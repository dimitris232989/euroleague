param(
    [string]$MariaDbHome = 'C:\Program Files\MariaDB 12.2',
    [string]$DbHost = '127.0.0.1',
    [int]$DbPort = 3306,
    [string]$DbName = 'euroleague',
    [string]$DbUser = 'root',
    [string]$DbPassword = '',
    [string]$PhpHost = '127.0.0.1',
    [int]$PhpPort = 8000
)

$ErrorActionPreference = 'Stop'

$repoRoot = Split-Path -Parent $PSScriptRoot
$mariaDbExe = Join-Path $MariaDbHome 'bin\mariadbd.exe'
$myIniPath = Join-Path $MariaDbHome 'data\my.ini'

function Test-TcpPort {
    param(
        [string]$TargetHost,
        [int]$Port
    )

    try {
        $client = [System.Net.Sockets.TcpClient]::new()
        $asyncResult = $client.BeginConnect($TargetHost, $Port, $null, $null)
        if (-not $asyncResult.AsyncWaitHandle.WaitOne(1000, $false)) {
            $client.Close()
            return $false
        }

        $null = $client.EndConnect($asyncResult)
        $client.Close()
        return $true
    } catch {
        return $false
    }
}

if (-not (Get-Command php -ErrorAction SilentlyContinue)) {
    throw 'The php command is not available on PATH.'
}

if (-not (Test-Path $mariaDbExe)) {
    throw "MariaDB server executable not found at $mariaDbExe. Use -MariaDbHome to point to the correct install directory."
}

if (-not (Test-Path $myIniPath)) {
    throw "MariaDB config file not found at $myIniPath."
}

if (-not (Test-TcpPort -TargetHost $DbHost -Port $DbPort)) {
    Write-Host "Starting MariaDB from $MariaDbHome ..."
    Start-Process -FilePath $mariaDbExe -ArgumentList @("--defaults-file=`"$myIniPath`"", '--console') -WorkingDirectory (Join-Path $MariaDbHome 'bin') -WindowStyle Hidden | Out-Null

    $started = $false
    for ($attempt = 0; $attempt -lt 30; $attempt++) {
        Start-Sleep -Milliseconds 500
        if (Test-TcpPort -TargetHost $DbHost -Port $DbPort) {
            $started = $true
            break
        }
    }

    if (-not $started) {
        throw "MariaDB did not start listening on ${DbHost}:${DbPort}."
    }
} else {
    Write-Host "MariaDB is already listening on ${DbHost}:${DbPort}."
}

$env:EUROLEAGUE_DB_DRIVER = 'mysql'
$env:EUROLEAGUE_DB_HOST = $DbHost
$env:EUROLEAGUE_DB_PORT = $DbPort.ToString()
$env:EUROLEAGUE_DB_NAME = $DbName
$env:EUROLEAGUE_DB_USER = $DbUser
$env:EUROLEAGUE_DB_PASS = $DbPassword

Set-Location $repoRoot

Write-Host "Starting PHP server at http://${PhpHost}:${PhpPort} ..."
php -S "$PhpHost`:$PhpPort" -t public