#!/usr/bin/env bash
# SunHelm Needs Bridge - Papyrus compile helper.
# Avoids WSL/Windows nested-quoting problems with paths that contain spaces.
#
# Usage (from WSL):  bash "/mnt/h/Nolvus Awakening/MODS/mods/SunHelm - CHIM AI Bridge/compile.sh"

set -euo pipefail

cd "/mnt/h/Nolvus Awakening/TOOLS"

./Caprica.exe   "H:/Nolvus Awakening/MODS/mods/SunHelm - CHIM AI Bridge/Source/Scripts/SunHelmCHIMBridge.psc"   --game skyrim   -f "H:/Nolvus Awakening/TOOLS/VanillaScripts/TESV_Papyrus_Flags.flg"   -o "H:/Nolvus Awakening/MODS/mods/SunHelm - CHIM AI Bridge/Scripts"   -i "H:/Nolvus Awakening/MODS/mods/SunHelm - CHIM AI Bridge/Source/Scripts;H:/Nolvus Awakening/MODS/mods/CHIM - AI Agent/Source/Scripts;H:/Nolvus Awakening/MODS/mods/Skyrim Script Extender/Scripts/Source;H:/Nolvus Awakening/TOOLS/VanillaScripts"

echo "== Compile finished =="
ls -la "/mnt/h/Nolvus Awakening/MODS/mods/SunHelm - CHIM AI Bridge/Scripts/"
