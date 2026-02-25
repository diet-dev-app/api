#!/usr/bin/env bash
# Bundle the split OpenAPI source files into a single public/openapi.yaml
# Usage: ./scripts/bundle-openapi.sh

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT_DIR="$(cd "$SCRIPT_DIR/.." && pwd)"
INPUT="$ROOT_DIR/openapi/openapi.yaml"
OUTPUT="$ROOT_DIR/public/openapi.yaml"

echo "Bundling OpenAPI spec..."
echo "  Source : $INPUT"
echo "  Output : $OUTPUT"

if command -v npx &>/dev/null; then
  npx --yes @redocly/cli bundle "$INPUT" -o "$OUTPUT"
  echo "Done. Bundled spec written to public/openapi.yaml"
else
  echo "Error: npx not found. Install Node.js to use this script."
  exit 1
fi
