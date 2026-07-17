#!/usr/bin/env bash
# =============================================================================
# GenScript OpenCart — One-Click Deployment Packager
# =============================================================================
# Usage:
#   bash deploy-pack.sh
#
# Generates release.zip at the project root with EVERYTHING needed to run.
# Upload release.zip to your server, unzip, and you're live.
#
# What is INCLUDED:
#   - All OpenCart core (admin/ catalog/ system/ image/ vendor/ etc.)
#   - .htaccess, index.php, php.ini, nginx.conf
#   - All customised theme files, stylesheets, and scripts
#   - vqmod/ (if present)
#
# What is EXCLUDED (development-only / sensitive):
#   - .git/                  Git history
#   - .gitignore             Not needed on server
#   - .env                   Local credentials (use .env.example as template)
#   - .env.example           Template only — not a real config
#   - docker-compose.yml     Local Docker orchestration
#   - dev_runtime_assets/    Screenshots, analysis, test backups
#   - mysql/                 Docker DB volume (huge)
#   - front-end/             Stale legacy static assets
#   - system/storage/cache/  Cached pages & Twig compiles
#   - system/storage/logs/   Local error logs
#   - system/storage/session/ Local sessions
#   - .playwright-mcp/       Browser test snapshots
#   - .claude/               Claude Code sessions
#   - .DS_Store, Thumbs.db   OS junk files
#   - release.zip itself     Don't nest
# =============================================================================

set -euo pipefail
cd "$(dirname "$0")"

OUTFILE="release.zip"
NOW=$(date '+%Y-%m-%d %H:%M:%S')

echo ""
echo "  ╔══════════════════════════════════════════╗"
echo "  ║   GenScript OpenCart  —  deploy-pack    ║"
echo "  ╚══════════════════════════════════════════╝"
echo "  Started: $NOW"
echo ""

# Remove previous build
[ -f "$OUTFILE" ] && rm "$OUTFILE"

echo "  [1/3] Scanning tracked files..."

# ---------------------------------------------------------------------------
# Build the exclusion list
# ---------------------------------------------------------------------------
EXCLUDE=(
  # VCS
  ".git"
  ".gitignore"
  ".gitattributes"

  # Secrets & local config
  ".env"
  ".env.example"
  "config.php"
  "admin/config.php"

  # Docker (not needed on production servers)
  "docker-compose.yml"

  # Dev runtime garbage
  "dev_runtime_assets"

  # Legacy stale directory
  "front-end"

  # Docker DB volume (massive)
  "mysql"

  # OpenCart runtime caches / logs / sessions
  "system/storage/cache*"
  "system/storage/logs*"
  "system/storage/session*"
  "system/storage/upload*"
  "system/storage/modification*"
  "system/storage/backup*"
  "system/storage/download*"
  "system/storage/marketplace*"

  # MCP / AI tool artifacts
  ".playwright-mcp"
  ".claude"

  # Self (don't nest the zip)
  "$OUTFILE"
  "deploy-pack.sh"

  # OS junk
  ".DS_Store"
  "Thumbs.db"
)

# Build the -x flags for zip
ZIP_EXCLUDE=""
for pattern in "${EXCLUDE[@]}"; do
  ZIP_EXCLUDE="$ZIP_EXCLUDE -x '$pattern' -x '*/$pattern' -x '*/$pattern/*'"
done

echo "  [2/3] Packaging release.zip (excluding dev assets)..."

# Run zip (use eval because the exclude patterns contain special chars)
eval zip -r "$OUTFILE" . $ZIP_EXCLUDE 2>&1 | tail -5

SIZE=$(du -h "$OUTFILE" | cut -f1)
echo ""
echo "  [3/3] Done!  release.zip  ($SIZE)"
echo ""
echo "  ╔═══════════════════════════════════════════════════════════╗"
echo "  ║  UPLOAD TO YOUR SERVER                                   ║"
echo "  ╠═══════════════════════════════════════════════════════════╣"
echo "  ║                                                         ║"
echo "  ║  scp release.zip user@your-server:/var/www/html/         ║"
echo "  ║  ssh user@your-server                                   ║"
echo "  ║    cd /var/www/html                                      ║"
echo "  ║    unzip -o release.zip                                  ║"
echo "  ║    cp .env.example .env   # then edit .env with your DB  ║"
echo "  ║    rm release.zip                                        ║"
echo "  ║                                                         ║"
echo "  ║  All paths are DYNAMIC — no manual config edits needed!  ║"
echo "  ╚═══════════════════════════════════════════════════════════╝"
echo ""
