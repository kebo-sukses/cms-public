<#
 generate-favicon.ps1
 Creates a favicon.ico from a source PNG using ImageMagick if available.
 Falls back to copying the PNG to favicon.png and instructing the user how to install ImageMagick.

 Usage:
 .\scripts\generate-favicon.ps1 -SourcePath assets/images/logo1.png -OutputDir assets/images

 Parameters:
 -SourcePath: path to the source PNG (default: assets/images/logo1.png)
 -OutputDir: destination directory for favicon.ico (default: assets/images)
#>

param(
    [string]$SourcePath = 'assets/images/logo1.png',
    [string]$OutputDir = 'assets/images',
    [string]$MagickPath = $env:IMAGEMAGICK_PATH
)

$fullSource = Resolve-Path -Path $SourcePath -ErrorAction SilentlyContinue
if (-not $fullSource) {
    Write-Error "Source PNG not found at path: $SourcePath`nPlease ensure the file exists or pass -SourcePath with a valid path." -ErrorAction Stop
}

$fullSource = $fullSource.Path
$destDir = Resolve-Path -Path $OutputDir -ErrorAction SilentlyContinue
if (-not $destDir) { New-Item -ItemType Directory -Path $OutputDir -Force | Out-Null; $destDir = Resolve-Path -Path $OutputDir }
$destDir = $destDir.Path

$faviconIco = Join-Path $destDir 'favicon.ico'
$faviconPng = Join-Path $destDir 'favicon.png'

# If user provided a MagickPath, prefer that
if ($MagickPath) {
    $magickExe = Join-Path -Path $MagickPath -ChildPath 'magick.exe'
    $convertExe = Join-Path -Path $MagickPath -ChildPath 'convert.exe'
} else {
    $magickExe = $null
    $convertExe = $null
}

# If not provided, fallback to PATH checks
if (-not $magickExe) { $magickExe = (Get-Command magick -ErrorAction SilentlyContinue)?.Source }
if (-not $convertExe) { $convertExe = (Get-Command convert -ErrorAction SilentlyContinue)?.Source }

$magickAvailable = (Test-Path $magickExe) -or (Test-Path $convertExe)
if ($magickAvailable) {
    Write-Host "ImageMagick detected. Generating multi-resolution favicon.ico..."
    if (Test-Path $magickExe) {
        try {
            & "${magickExe}" convert -- "${fullSource}" -define icon:auto-resize=64,48,32,16 "${faviconIco}"
            if (Test-Path $faviconIco) {
                $size = (Get-Item $faviconIco).Length
                if ($size -gt 0) { Write-Host "Favicon created at: $faviconIco ($size bytes)" } 
                else { Write-Warning "Favicon created but file is empty. Falling back to PNG."; Copy-Item -Path $fullSource -Destination $faviconPng -Force; Write-Host "Copied PNG fallback to: $faviconPng" }
            } else { Write-Warning "Favicon not found after ImageMagick execution. Falling back to PNG."; Copy-Item -Path $fullSource -Destination $faviconPng -Force; Write-Host "Copied PNG fallback to: $faviconPng" }
        } catch { Write-Warning "ImageMagick conversion with 'magick' failed ($_). Falling back to PNG copy."; Copy-Item -Path $fullSource -Destination $faviconPng -Force; Write-Host "Copied PNG fallback to: $faviconPng" }
    } elseif (Test-Path $convertExe) {
        try {
            & "${convertExe}" "${fullSource}" -define icon:auto-resize=64,48,32,16 "${faviconIco}"
            if (Test-Path $faviconIco) {
                $size = (Get-Item $faviconIco).Length
                if ($size -gt 0) { Write-Host "Favicon created at: $faviconIco ($size bytes)" } 
                else { Write-Warning "Favicon created but file is empty. Falling back to PNG."; Copy-Item -Path $fullSource -Destination $faviconPng -Force; Write-Host "Copied PNG fallback to: $faviconPng" }
            } else { Write-Warning "Favicon not found after ImageMagick execution. Falling back to PNG."; Copy-Item -Path $fullSource -Destination $faviconPng -Force; Write-Host "Copied PNG fallback to: $faviconPng" }
        } catch { Write-Warning "ImageMagick conversion with 'convert' failed ($_). Falling back to PNG copy."; Copy-Item -Path $fullSource -Destination $faviconPng -Force; Write-Host "Copied PNG fallback to: $faviconPng" }
    } else { Write-Warning "ImageMagick command not callable even though detected. Creating PNG fallback."; Copy-Item -Path $fullSource -Destination $faviconPng -Force; Write-Host "Copied PNG fallback to: $faviconPng" }
} else { Write-Warning "ImageMagick (magick or convert) not found in PATH or at provided MagickPath. Creating PNG fallback instead."; Copy-Item -Path $fullSource -Destination $faviconPng -Force; Write-Host "Copied PNG fallback to: $faviconPng"; Write-Host "To generate a .ico file automatically install ImageMagick and re-run this script."; Write-Host "ImageMagick install: https://imagemagick.org/script/download.php" }
