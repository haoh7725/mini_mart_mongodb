$ErrorActionPreference = 'Stop'
$projectRoot = Split-Path $PSScriptRoot -Parent
$runtime = Join-Path $projectRoot '.runtime\mongodb'
$mongodBinary = $env:MINIMART_MONGOD_BINARY
if (-not $mongodBinary) { $mongodBinary = (Get-Command mongod -ErrorAction Stop).Source }
$mongoshBinary = (Get-Command mongosh -ErrorAction Stop).Source
New-Item -ItemType Directory -Force -Path "$runtime\data" | Out-Null
$listening = Get-NetTCPConnection -LocalPort 27018 -State Listen -ErrorAction SilentlyContinue
if (-not $listening) {
    $arguments = @('--dbpath', ('"' + "$runtime\data" + '"'), '--port', '27018', '--bind_ip', '127.0.0.1', '--replSet', 'minimart', '--logpath', ('"' + "$runtime\mongod.log" + '"'), '--logappend')
    Start-Process -FilePath $mongodBinary -ArgumentList $arguments -WindowStyle Hidden
}
for ($attempt = 0; $attempt -lt 30; $attempt++) {
    $savedPreference = $ErrorActionPreference
    $ErrorActionPreference = 'Continue'
    & $mongoshBinary 'mongodb://127.0.0.1:27018/?directConnection=true&serverSelectionTimeoutMS=1000' --quiet --file "$PSScriptRoot\mongo-init.js" 2>$null
    $mongoExitCode = $LASTEXITCODE
    $ErrorActionPreference = $savedPreference
    if ($mongoExitCode -eq 0) { break }
    Start-Sleep -Milliseconds 500
}
& $mongoshBinary 'mongodb://127.0.0.1:27018/?replicaSet=minimart&serverSelectionTimeoutMS=10000' --quiet --file "$PSScriptRoot\mongo-ready.js"
if ($LASTEXITCODE -ne 0) { throw 'MongoDB replica set is not ready.' }
