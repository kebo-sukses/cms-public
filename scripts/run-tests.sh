#!/usr/bin/env bash
set -euo pipefail

IMAGE_NAME="calius-tests:latest"

# Build the image
docker build -t "${IMAGE_NAME}" .

# Run tests in a temporary container (mount project so results and cache are visible)
docker run --rm -v "$(pwd)":/app -w /app "${IMAGE_NAME}"
