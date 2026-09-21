#!/usr/bin/env bash
# SunHelm Needs & Diseases Bridge - installer / updater for the CHIM (HerikaServer) extension.
# Syncs this mod's plugin files into the live Apache/PHP server, seeds Oghma medical lore,
# and patches the root comm.php parser.
#
# Usage (from WSL):  bash "/mnt/h/Nolvus Awakening/MODS/mods/SunHelm - CHIM AI Bridge/install.sh"

set -euo pipefail

SRC_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)/plugin/ext/sunhelm_needs"
DEST_DIR="/var/www/html/HerikaServer/ext/sunhelm_needs"
ROOT_COMM="/var/www/html/HerikaServer/comm.php"

echo "== SunHelm Needs & Diseases Bridge installer =="
echo "Source: $SRC_DIR"
echo "Dest:   $DEST_DIR"

if [ ! -d "$SRC_DIR" ]; then
    echo "ERROR: plugin source not found at $SRC_DIR" >&2
    exit 1
fi

mkdir -p "$DEST_DIR"

# Copy plugin files, preserving live state.json if it already exists.
for f in context_pre.php context.php comm.php config.php update.php manifest.json settings.json preprocessing.php oghma_diseases.sql; do
    if [ -f "$SRC_DIR/$f" ]; then
        cp -f "$SRC_DIR/$f" "$DEST_DIR/$f"
        echo "  installed $f"
    fi
done

# Seed state.json if missing (never clobber live game data).
if [ ! -f "$DEST_DIR/state.json" ]; then
    cp -f "$SRC_DIR/state.json" "$DEST_DIR/state.json"
    echo "  seeded state.json"
else
    echo "  kept existing state.json"
fi

chmod 664 "$DEST_DIR"/*.json 2>/dev/null || true
chmod 664 "$DEST_DIR"/*.php 2>/dev/null || true
chmod 664 "$DEST_DIR"/*.sql 2>/dev/null || true

# Seed Oghma medical lore into PostgreSQL
if [ -f "$DEST_DIR/oghma_diseases.sql" ]; then
    echo "Seeding Oghma Medical Compendium into PostgreSQL (dwemer)..."
    PGPASSWORD=dwemer psql -h localhost -U dwemer -d dwemer -f "$DEST_DIR/oghma_diseases.sql" > /dev/null
    echo "  Oghma medical compendium seeded successfully."
fi

# Patch root comm.php to handle both sunhelm_needs@ and sunhelm_disease@
if [ -f "$ROOT_COMM" ]; then
    echo "Patching root comm.php..."
    cp -f "$ROOT_COMM" "$ROOT_COMM.bak_$(date +%Y%m%d_%H%M%S)"
    python3 - "$ROOT_COMM" << 'PYEOF'
import sys, re
path = sys.argv[1]
src = open(path, 'r', encoding='utf-8').read()
changed = False

# Add disease handler if not present
disease_block = """    if (strpos($rawDecoded, 'sunhelm_disease@') !== false) {
        $parts = explode('@', $rawDecoded);
        if (count($parts) >= 4) {
            $stateFile = __DIR__ . '/ext/sunhelm_needs/state.json';
            $state = file_exists($stateFile) ? json_decode(file_get_contents($stateFile), true) : [];
            if (!is_array($state)) $state = [];
            $diseaseName = trim($parts[1]);
            $stage       = (int)$parts[2];
            $visualCues  = trim($parts[3]);
            $stageLabels = [0 => 'Healthy', 1 => 'Mild', 2 => 'Acute', 3 => 'Severe'];
            $stageLabel  = $stageLabels[$stage] ?? 'Afflicted';
            $formattedStage = ($stage > 0 && strcasecmp($diseaseName, 'None') !== 0 && strcasecmp($diseaseName, 'Food Poisoning') !== 0)
                ? ($stageLabel . ' ' . $diseaseName)
                : $diseaseName;

            $state['player_disease'] = [
                'has_disease'    => ($stage > 0 && strcasecmp($diseaseName, 'None') !== 0),
                'disease_name'   => $diseaseName,
                'stage'          => $stage,
                'stage_label'    => $formattedStage,
                'visual_cues'    => $visualCues,
                'updated_at'     => date('Y-m-d H:i:s')
            ];
            $state['updated_at'] = date('Y-m-d H:i:s');
            file_put_contents($stateFile, json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        }
    }"""

if "sunhelm_disease@" not in src:
    anchor = "if (strpos($rawDecoded, 'sunhelm_needs@') !== false) {"
    if anchor in src:
        src = src.replace(anchor, disease_block + "\n\n    " + anchor)
        changed = True

open(path, 'w', encoding='utf-8').write(src)
print("  patched root comm.php with disease handler" if changed else "  root comm.php already has disease handler.")
PYEOF
else
    echo "  WARN: root comm.php not found; skipping patch."
fi

echo "== Install complete =="
echo "Config UI: http://localhost:8081/HerikaServer/ext/sunhelm_needs/config.php"
