#!/usr/bin/env bash
# SunHelm Needs Bridge - Papyrus compile helper.
# Avoids WSL/Windows nested-quoting problems with paths that contain spaces.
#
# Usage (from WSL):  bash "/mnt/h/Nolvus Awakening/MODS/mods/SunHelm - CHIM AI Bridge/compile.sh"
#
# IMPORT PATHS AND THE FOLLOWER BRIDGE
#
# SunHelmFollowerNeeds.psc has to be importable or this will not compile, because the bridge calls
# its natives. That is a *compile-time* requirement only, and it does not make the follower mod a
# dependency of the finished bridge: at runtime the script checks whether that plugin's ESP exists
# before it calls anything, so a player without it gets silence rather than errors, and the shipped
# .pex needs nothing extra.
#
# The script lives in that mod's Source/Scripts, and also in its repository under scripts/. Point
# FOLLOWER_SCRIPTS at whichever you have; leave it and the bridge still builds as long as the path
# exists, since Caprica ignores import directories it cannot find.

set -euo pipefail

FOLLOWER_SCRIPTS="H:/Nolvus Awakening/MODS/mods/SunHelm - Individual Follower Needs/Source/Scripts"

cd "/mnt/h/Nolvus Awakening/TOOLS"

./Caprica.exe   "H:/Nolvus Awakening/MODS/mods/SunHelm - CHIM AI Bridge/Source/Scripts/SunHelmCHIMBridge.psc"   --game skyrim   -f "H:/Nolvus Awakening/TOOLS/VanillaScripts/TESV_Papyrus_Flags.flg"   -o "H:/Nolvus Awakening/MODS/mods/SunHelm - CHIM AI Bridge/Scripts"   -i "H:/Nolvus Awakening/MODS/mods/SunHelm - CHIM AI Bridge/Source/Scripts;${FOLLOWER_SCRIPTS};H:/Nolvus Awakening/MODS/mods/CHIM - AI Agent/Source/Scripts;H:/Nolvus Awakening/MODS/mods/Skyrim Script Extender/Scripts/Source;H:/Nolvus Awakening/TOOLS/VanillaScripts"

echo "== Compile finished =="
ls -la "/mnt/h/Nolvus Awakening/MODS/mods/SunHelm - CHIM AI Bridge/Scripts/"
