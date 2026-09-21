# Agent Directives: CHIM - Needs and Diseases Bridge

> **Scope:** Autonomous AI coding agents (Claude, Cursor, Copilot, Antigravity, Devin) modifying, debugging, or maintaining this repository.

---

## 1. Project Mission & Architecture Summary

This repository is an open-source survival and medical immersion bridge connecting:
- **Skyrim SE / AE Game Engine** (via Bethesda Papyrus & SKSE)
- **SunHelm Survival** (`SunHelmSurvival.esp` - Required Core Master)
- **SunHelm Diseases** (`SunHelmDiseases.esp` - Optional Multi-Stage Add-on)
- **Water-Borne Diseases for SunHelm** (`SunHelmDirtyWater.esp` - Optional Add-on)
- **Immersive Diseases 2.0** (`Immersive Diseases.esp` - Optional Overlays)
- **CHIM AI Agent & HerikaServer** (Apache / PHP 8.x / PostgreSQL LLM framework)

### Primary Invariants:
1. **100% Modular & Zero-Dependency:** The plugin `SunHelm_CHIM_Bridge.esp` has **only one** mod master: `SunHelmSurvival.esp`. All other mods are dynamically resolved at runtime via `Game.GetFormFromFile()`.
2. **WebUI Independence:** Every extra mod integration and survival need can be toggled on/off in the WebUI (`config.php`). The prompt injection engine (`context_pre.php`) must honor these toggles with robust fallbacks.
3. **Save-Game Safety:** `state.json` is a live runtime cache written by Skyrim game sessions. Never commit, overwrite, or clobber `state.json` during builds.

---

## 2. Key Directories & Path Conventions

| Component | Repository Path | Live Target (WSL / Server) | Purpose |
|---|---|---|---|
| **Papyrus Source** | `Source/Scripts/SunHelmCHIMBridge.psc` | — | Primary Skyrim event loop and disease monitor |
| **Papyrus Binary** | `Scripts/SunHelmCHIMBridge.pex` | — | Compiled Papyrus binary loaded by Skyrim engine |
| **Plugin ESP** | `SunHelm_CHIM_Bridge.esp` | — | Bethesda plugin containing auto-start quest `_SH_CHIM_BridgeQuest` |
| **Extension WebUI** | `plugin/ext/sunhelm_needs/config.php` | `/var/www/html/HerikaServer/ext/sunhelm_needs/config.php` | Tabbed browser configuration interface |
| **Prompt Injector** | `plugin/ext/sunhelm_needs/context_pre.php` | `/var/www/html/HerikaServer/ext/sunhelm_needs/context_pre.php` | Injects physical needs & medical lore into `$GLOBALS["COMMAND_PROMPT"]` |
| **Payload Parser** | `plugin/ext/sunhelm_needs/preprocessing.php`| `/var/www/html/HerikaServer/ext/sunhelm_needs/preprocessing.php` | Parses `sunhelm_needs@` and `sunhelm_disease@` into `state.json` |
| **Settings Schema** | `plugin/ext/sunhelm_needs/settings.json` | `/var/www/html/HerikaServer/ext/sunhelm_needs/settings.json` | User toggles, thresholds, cooldowns, templates |
| **Medical Compendium** | `plugin/ext/sunhelm_needs/oghma_diseases.sql`| `/var/www/html/HerikaServer/ext/sunhelm_needs/oghma_diseases.sql`| 15-entry PostgreSQL Oghma knowledge pack |
| **Root Comm Hook** | — | `/var/www/html/HerikaServer/comm.php` | Base64 receiver bridging Skyrim SKSE to server |

*Windows UNC Path to WSL Server:* `\\wsl.localhost\DwemerAI4Skyrim3\var\www\html\HerikaServer\`

---

## 3. Build, Deploy & Verification Commands

### 1. Compile Papyrus Script
Always compile using Caprica for Skyrim SE:
```bash
# From WSL:
bash compile.sh

# Or from Windows PowerShell:
& "H:\Nolvus Awakening\TOOLS\Caprica.exe" "SunHelmCHIMBridge.psc" -g skyrim --ignorecwd -f "H:\Nolvus Awakening\TOOLS\VanillaScripts\TESV_Papyrus_Flags.flg" --output="H:\Nolvus Awakening\MODS\mods\SunHelm - CHIM AI Bridge\Scripts" --import="H:\Nolvus Awakening\MODS\mods\SunHelm - CHIM AI Bridge\Source\Scripts;H:\Nolvus Awakening\MODS\mods\CHIM - AI Agent\Source\Scripts;H:\Nolvus Awakening\MODS\mods\Skyrim Script Extender\Scripts\Source;H:\Nolvus Awakening\TOOLS\VanillaScripts"
```

### 2. Deploy Extension to Server
```bash
# Run from WSL:
bash install.sh
```
This syncs extension files to `/var/www/html/HerikaServer/ext/sunhelm_needs/`, auto-patches root `comm.php`, and seeds `oghma_diseases.sql` into the `dwemer` PostgreSQL database.

### 3. Verify System Health
```bash
# Run from WSL:
bash verify.sh
```
Performs syntax linting across all PHP files, validates `state.json`, checks root `comm.php` hooks, and queries `public.oghma` for the 15 disease entries.

### 4. Test Prompt Injection
```bash
# Test prompt output across NPC archetypes (Arcadia, Stenvar, Guards, Healers):
wsl -d DwemerAI4Skyrim3 php test_disease_injection.php
```

---

## 4. Crucial Engine Rules & Anti-Patterns

1. **Bethesda Event Dispatch Separation:**
   - `RegisterForSingleUpdate(float seconds)` dispatches **only** to `Event OnUpdate()`.
   - `RegisterForSingleUpdateGameTime(float hours)` dispatches **only** to `Event OnUpdateGameTime()`.
   - Combining or swapping these causes silent event drops in the Bethesda Papyrus VM. Always maintain the dual-loop architecture.
2. **Never Add Hard Masters to ESP:**
   - `SunHelm_CHIM_Bridge.esp` must only have `Skyrim.esm`, `Update.esm`, `SunHelmSurvival.esp`, and `AIAgent.esp` as masters.
   - Do NOT add `SunHelmDiseases.esp`, `SunHelmDirtyWater.esp`, or `Immersive Diseases.esp` as masters. Always resolve forms via `Game.GetFormFromFile()`.
3. **ESP Auto-Start Bitmask:**
   - The quest `_SH_CHIM_BridgeQuest` (`0x00000800`) relies on `DNAM` bit 0 (`Start Game Enabled`).
   - If rebuilding the ESP via Spriggit, ensure the `DNAM` flag structure matches `0100 0000 0000 0000 0000 0000`.
4. **Visual Cue Safety:**
   - If `mod_immersive_diseases` is toggled off in `settings.json`, `context_pre.php` must NEVER print visual texture descriptors (purple veins, blotches, rigid elbows) into prompts. Use generic physical malaise descriptors instead.
5. **CRLF Line Endings:**
   - Files edited on Windows may contain CRLF. Ensure bash scripts use `set -euo pipefail` and handle line endings gracefully.

