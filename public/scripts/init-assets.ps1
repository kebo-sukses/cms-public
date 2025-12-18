
<#
 init-assets.ps1
 Create standard asset folders used by the CMS and ensure .gitkeep placeholders exist.

 Usage:
  .\scripts\init-assets.ps1
#>

$folders = @(
  'assets/images',
  'assets/images/uploads',
  'assets/media'
)

# Ensure artifacts folder exists (non-web storage for uploaded templates)
$artifacts = 'data/artifacts/templates'
if (-not (Test-Path $artifacts)) { New-Item -ItemType Directory -Path $artifacts -Force | Out-Null; Write-Host "Created: $artifacts" }
$gitkeep = Join-Path 'data/artifacts' '.gitkeep'
if (-not (Test-Path $gitkeep)) { New-Item -ItemType File -Path $gitkeep -Force | Out-Null; Write-Host "Created placeholder: $gitkeep" }

foreach ($f in $folders) {
  if (-not (Test-Path $f)) {
    New-Item -ItemType Directory -Path $f -Force | Out-Null
    Write-Host "Created: $f"
  } else {
    Write-Host "Exists:  $f"
  }
  $gitkeep = Join-Path $f '.gitkeep'
  if (-not (Test-Path $gitkeep)) {
    New-Item -ItemType File -Path $gitkeep -Force | Out-Null
    Write-Host "Created placeholder: $gitkeep"
  }
}

Write-Host "Asset folders initialized. Add your media to 'assets/media' and user uploads to 'assets/images/uploads'."
