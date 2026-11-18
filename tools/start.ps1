param([int]$Port = 8000, [switch]$SkipMongo)
$ErrorActionPreference = 'Stop'
Set-Location (Split-Path $PSScriptRoot -Parent)
if (-not $SkipMongo) { & "$PSScriptRoot\start-mongodb.ps1" }
& "$PSScriptRoot\php.ps1" artisan serve --host=127.0.0.1 --port=$Port
exit $LASTEXITCODE
