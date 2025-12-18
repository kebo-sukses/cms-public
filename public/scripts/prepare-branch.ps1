# Prepare branch and commit for Windows PowerShell
# Run from repository root in PowerShell

param(
    [string]$Branch = 'ci/rename-public-to-cms-public'
)

Write-Host "Creating branch: $Branch"
git checkout -b $Branch
Write-Host 'Adding changed files and committing'
git add INSTALL-GUIDE.md
git commit -m "docs: rename public -> cms-public in INSTALL-GUIDE.md"
Write-Host 'Pushing branch to origin'
git push -u origin $Branch
Write-Host 'Done. You can now open a PR from branch: ' $Branch
