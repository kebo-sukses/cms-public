#!/usr/bin/env bash
BRANCH=${1:-feature/prune-artifacts}

echo "Creating branch: $BRANCH"
git checkout -b "$BRANCH"
echo "Staging changes and committing"
git add scripts/prune-artifacts.php .github/workflows/prune-artifacts.yml admin/api/delete-artifact.php admin/templates-manager.html tests/phpunit/TestPruneArtifactsPhpUnit.php data/settings.json

git commit -m "feat: add prune artifacts script, delete API, UI column and scheduled workflow"
echo "Pushing branch to origin"
git push -u origin "$BRANCH"
echo "Done. Open a PR from branch: $BRANCH"
