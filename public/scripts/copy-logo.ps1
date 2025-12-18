<#
  copy-logo.ps1
  Helper script to copy a local logo into the project assets so it can be used as site logo and favicon.

  Usage:
    - Update $sourcePath if your logo is in a different location
    - Run in PowerShell: .\scripts\copy-logo.ps1
#>

param(
  [string]$sourcePath = 'D:\calius digital marketing\logo\logo1.png',
  [switch]$GenerateIco
)

$destDir = Join-Path -Path (Get-Location) -ChildPath 'assets\images'
$destFile = Join-Path -Path $destDir -ChildPath 'logo1.png'

Write-Host "Source: $sourcePath"
Write-Host "Destination: $destFile"

if (-Not (Test-Path $sourcePath)) {
  Write-Error "Source file not found. Please place your logo at: $sourcePath or update the script to point to your file." -ErrorAction Stop
}

if (-Not (Test-Path $destDir)) {
  New-Item -ItemType Directory -Path $destDir -Force | Out-Null
}

Copy-Item -Path $sourcePath -Destination $destFile -Force
Write-Host "Copied logo to $destFile"

# Also create a favicon PNG fallback
$faviconDest = Join-Path -Path $destDir -ChildPath 'favicon.png'
Copy-Item -Path $sourcePath -Destination $faviconDest -Force
Write-Host "Copied favicon fallback to $faviconDest"

if ($GenerateIco) {
  Write-Host "Generating .ico using scripts/generate-favicon.ps1..."
  if ($PSBoundParameters.ContainsKey('MagickPath') -and $MagickPath) {
    & .\scripts\generate-favicon.ps1 -SourcePath $destFile -OutputDir $destDir -MagickPath $MagickPath
  } else {
    & .\scripts\generate-favicon.ps1 -SourcePath $destFile -OutputDir $destDir
  }
}