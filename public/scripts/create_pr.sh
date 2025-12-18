#!/usr/bin/env bash
# Helper script to create a branch, commit changes, push, and open a PR using GitHub CLI (gh).

set -euo pipefail
BRANCH_NAME=${1:-fix/ci-triage-and-tests}
PR_TITLE=${2:-"Fix: Add tests and hardening for CI triage"}

echo "Creating branch $BRANCH_NAME"

git checkout -b "$BRANCH_NAME"

git add -A

git commit -m "$PR_TITLE"

echo "Pushing branch to origin"

git push -u origin "$BRANCH_NAME"

if command -v gh >/dev/null 2>&1; then
  echo "Opening PR"
  gh pr create --fill --title "$PR_TITLE" --label "tests" --label "ci-failure"
  echo "PR created"
else
  echo "gh CLI not found. Please create a PR manually from branch: $BRANCH_NAME"
fi
