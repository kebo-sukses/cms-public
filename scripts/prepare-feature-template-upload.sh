#!/usr/bin/env bash
BRANCH=${1:-feature/store-template-artifact}

echo "Creating branch: $BRANCH"
git checkout -b "$BRANCH"
echo "Staging changes and committing"
git add admin/upload-handler.php admin/api/upload_helpers.php admin/api/template-artifacts.php admin/api/template-artifact-download.php admin/templates-manager.html admin/index.html tests/phpunit/TestTemplateArtifactPhpUnit.php .gitignore scripts/init-assets.ps1 INSTALL-GUIDE.md

git commit -m "feat: store template artifacts (data/artifacts) + upload UI and API"
echo "Pushing branch to origin"
git push -u origin "$BRANCH"
echo "Done. Open a PR from branch: $BRANCH"