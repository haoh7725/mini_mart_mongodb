$ErrorActionPreference = 'Stop'
$projectRoot = Split-Path $PSScriptRoot -Parent
$phpBinary = $env:MINIMART_PHP_BINARY
if (-not $phpBinary -and (Test-Path "$projectRoot\.runtime\php-path.txt")) {
    $phpBinary = (Get-Content "$projectRoot\.runtime\php-path.txt" -Raw).Trim()
}
if (-not $phpBinary) {
    $phpCommand = Get-Command php -ErrorAction SilentlyContinue
    if ($phpCommand) { $phpBinary = $phpCommand.Source }
    else { $phpBinary = 'D:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe' }
}
if (Test-Path "$projectRoot\.runtime\php.d") {
    $env:PHP_INI_SCAN_DIR = "$projectRoot\.runtime\php.d"
}
$env:PATH = (Split-Path $phpBinary -Parent) + ';' + $env:PATH
& $phpBinary @args
exit $LASTEXITCODE
