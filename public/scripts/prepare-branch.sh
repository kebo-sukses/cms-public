#!/usr/bin/env bash
# Prepare branch and commit (Unix/macOS)
BRANCH=${1:-ci/rename-public-to-cms-public}

echo "Creating branch: $BRANCH"
git checkout -b "$BRANCH"
echo "Adding changed files and committing"
git add INSTALL-GUIDE.md
git commit -m "docs: rename public -> cms-public in INSTALL-GUIDE.md"
echo "Pushing branch to origin"
git push -u origin "$BRANCH"
echo "Done. Open a PR from branch: $BRANCH"
