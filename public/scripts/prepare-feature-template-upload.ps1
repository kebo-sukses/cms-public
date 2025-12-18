# Create feature branch for template upload UI & server support
param(
    [string]$Branch = 'feature/store-template-artifact'
)

Write-Host "Creating branch: $Branch"
git checkout -b $Branch
Write-Host 'Staging changes and committing'
git add admin/upload-handler.php admin/api/upload_helpers.php admin/api/template-artifacts.php admin/api/template-artifact-download.php admin/templates-manager.html admin/index.html tests/phpunit/TestTemplateArtifactPhpUnit.php .gitignore scripts/init-assets.ps1 INSTALL-GUIDE.md
git commit -m "feat: store template artifacts (data/artifacts) + upload UI and API"
Write-Host 'Pushing branch to origin'
git push -u origin $Branch
Write-Host 'Done. Open a PR from branch: ' $Branch