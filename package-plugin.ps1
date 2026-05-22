# package-plugin.ps1
# Script to package WordPress plugin zalo-miniapp-core into a zip file

$ErrorActionPreference = "Stop"

# 1. Paths
$pluginName = "zalo-miniapp-core"
$sourcePath = Join-Path $PSScriptRoot "System-wordpress\wp-content\plugins\$pluginName"
$outerTemp = Join-Path $PSScriptRoot "temp_archive"
$tempPath = Join-Path $outerTemp $pluginName
$outputPath = Join-Path $PSScriptRoot "${pluginName}.zip"

Write-Host "Starting packaging of plugin $pluginName..." -ForegroundColor Cyan

# 2. Cleanup old output
if (Test-Path $outputPath) {
    Remove-Item $outputPath -Force
    Write-Host "Removed old zip file at: $outputPath" -ForegroundColor Yellow
}
if (Test-Path $outerTemp) {
    Remove-Item $outerTemp -Recurse -Force
}

# 3. Create temp dir
New-Item -ItemType Directory -Path $tempPath | Out-Null

# 4. Copy allowed directories and files
$foldersToCopy = @("includes", "templates", "vendor")
foreach ($folder in $foldersToCopy) {
    $srcFolder = Join-Path $sourcePath $folder
    $destFolder = Join-Path $tempPath $folder
    if (Test-Path $srcFolder) {
        Copy-Item -Path $srcFolder -Destination $destFolder -Recurse -Force
    }
}

$files = Get-ChildItem -Path $sourcePath -File
foreach ($file in $files) {
    if ($file.Name -ne "composer.phar" -and $file.Name -ne ".gitignore" -and $file.Name -notlike ".*") {
        Copy-Item -Path $file.FullName -Destination (Join-Path $tempPath $file.Name) -Force
    }
}

# 5. Compress
Write-Host "Compressing folder..." -ForegroundColor Cyan
Compress-Archive -Path $tempPath -DestinationPath $outputPath -Force

# 6. Cleanup
Remove-Item $outerTemp -Recurse -Force

Write-Host "PACKAGING SUCCESSFUL!" -ForegroundColor Green
Write-Host "Output zip file: $outputPath" -ForegroundColor Green
if (Test-Path $outputPath) {
    Write-Host "Size: [ $(( (Get-Item $outputPath).Length / 1MB ).ToString("0.00")) MB ]" -ForegroundColor Green
}
