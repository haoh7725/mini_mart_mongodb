param([string]$PhpBinary, [string]$ComposerPhar)
$ErrorActionPreference = 'Stop'
$projectRoot = Split-Path $PSScriptRoot -Parent
Set-Location $projectRoot
if (-not $PhpBinary) {
    $found = Get-Command php -ErrorAction SilentlyContinue
    if ($found) { $PhpBinary = $found.Source }
    else { $PhpBinary = 'D:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe' }
}
if (-not (Test-Path -LiteralPath $PhpBinary)) { throw 'Pass -PhpBinary with the path to php.exe (PHP 8.2+).' }
New-Item -ItemType Directory -Force -Path '.runtime' | Out-Null
[IO.File]::WriteAllText("$projectRoot\.runtime\php-path.txt", $PhpBinary)
& $PhpBinary -r "exit(extension_loaded('mongodb') ? 0 : 1);"
if ($LASTEXITCODE -ne 0) {
    $version = (& $PhpBinary -r "echo PHP_MAJOR_VERSION,'.',PHP_MINOR_VERSION;").Trim()
    $thread = (& $PhpBinary -r "echo PHP_ZTS ? 'ts' : 'nts';").Trim()
    $arch = (& $PhpBinary -r 'echo PHP_INT_SIZE;').Trim()
    if ($arch -ne '8' -or $version -notin @('8.2', '8.3', '8.4', '8.5')) { throw 'This helper supports PHP 8.2-8.5 x64.' }
    $compiler = if ($version -in @('8.4', '8.5')) { 'vs17' } else { 'vs16' }
    $asset = "php_mongodb-2.5.7-$version-$thread-$compiler-x86_64"
    $extensionDirectory = "$projectRoot\.runtime\extensions\$asset"
    New-Item -ItemType Directory -Force -Path $extensionDirectory, '.runtime\php.d' | Out-Null
    if (-not (Test-Path "$extensionDirectory\$asset.dll")) {
        Invoke-WebRequest -Uri "https://github.com/mongodb/mongo-php-driver/releases/download/2.5.7/$asset.zip" -OutFile ".runtime\$asset.zip"
        Expand-Archive -LiteralPath ".runtime\$asset.zip" -DestinationPath $extensionDirectory -Force
    }
    [IO.File]::WriteAllText("$projectRoot\.runtime\php.d\mongodb.ini", 'extension="' + "$extensionDirectory\$asset.dll" + '"' + "`n")
}
if (-not $ComposerPhar) { $ComposerPhar = 'D:\laragon\bin\composer\composer.phar' }
if (-not (Test-Path -LiteralPath $ComposerPhar)) { throw 'Pass -ComposerPhar with the path to composer.phar.' }
& "$PSScriptRoot\php.ps1" $ComposerPhar install --no-interaction
if ($LASTEXITCODE -ne 0) { throw 'Composer install failed.' }
if (-not (Test-Path -LiteralPath '.env')) {
    Copy-Item -LiteralPath '.env.example' -Destination '.env'
    & "$PSScriptRoot\php.ps1" artisan key:generate
}
& "$PSScriptRoot\start-mongodb.ps1"
& "$PSScriptRoot\php.ps1" artisan migrate --force
if ($LASTEXITCODE -ne 0) { throw 'Migration failed.' }
Write-Host 'Ready. For a new demo database: .\tools\php.ps1 artisan db:seed'
Write-Host 'Start the app: .\tools\start.ps1'
