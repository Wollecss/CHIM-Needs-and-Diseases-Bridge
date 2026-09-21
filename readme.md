# CHIM - Needs and Diseases Bridge (SunHelm & Immersive Diseases 2.0)

A comprehensive survival and medical immersion bridge connecting **SunHelm**, **Immersive Diseases 2.0**, and the **CHIM AI Agent / HerikaServer** LLM framework.

NPCs throughout Skyrim now perceive, react to, and diagnose the player's real-time physical survival needs, stage-based disease progressions, and visible cosmetic symptoms.

> 📖 **Developer & Agent Specification:**  
> For in-depth technical details on the Bethesda Papyrus event loops, Spriggit ESP record schemas, Base64 communication protocol, and PostgreSQL Oghma RAG indexing, see **[ARCHITECTURE.md](ARCHITECTURE.md)**.

---

## Features

### 1. Real-Time Survival Needs Tracking
- Monitors the player's core SunHelm survival states:
  - **Hunger:** *Well Fed &rarr; Satisfied &rarr; Peckish &rarr; Hungry &rarr; Ravenous &rarr; Starving*
  - **Thirst:** *Quenched &rarr; Sated &rarr; Thirsty &rarr; Parched &rarr; Dehydrated &rarr; Severely Dehydrated*
  - **Fatigue:** *Well Rested &rarr; Rested &rarr; Slightly Tired &rarr; Tired &rarr; Weary &rarr; Exhausted*
  - **Cold:** *Warm &rarr; Comfortable &rarr; Chilly &rarr; Cold &rarr; Freezing &rarr; Frigid*
- Dynamically injects physical status cues into dialogue prompts when threshold stages are reached.

### 2. Multi-Stage Disease Tracking & Overlay Mirroring
- Full support for all 3 SunHelm disease progression stages, Food Poisoning, and **Water-Borne Diseases (`SunHelmDirtyWater.esp`)**:
  - **Stage 1 (Mild):** Subtle symptoms that draw minimal attention (including initial contraction from drinking untreated dirty water).
  - **Stage 2 (Acute):** Prominent physical impairments and visual signs triggering acute concern.
  - **Stage 3 (Severe):** Critical, incapacitating emergency states.
- Automatically maps Immersive Diseases 2.0 and RaceMenu visual overlays into physical prompt cues:
  - `locked_joints`, `limp_arm`, `violent_cough`, `purple_veins`, `boils`, `blotches`, `necrotic_skin`, `stomach_cramps`, `chills_shiver`, `tremors`.

### 3. Two-Tier Oghma Medical Lore System
NPCs react according to their social class and profession:
- **Commoners, Guards & Mercenaries:**
  - Recognize only the **vanilla folklore names** (*Rockjoint*, *Bone Break Fever*, *Brain Rot*, *Dampworm*).
  - React with superstitious caution, empathy, or advice to pray at a divine shrine or seek an apothecary.
- **Apothecaries, Healers, Scholars, Priests & Mages:**
  - Diagnose illnesses by their **clinical pathology adapted to Tamrielic lore** (*Tetanus*, *Breakbone Fever*, *Meningitis*, *Dracunculiasis*, *Ergotism*).
  - Explain specific vectors (Falmer water, skeever bites, bear saliva, sabre cat scratches, marsh miasmas).
  - Prescribe lore-accurate alchemical remedies (e.g. *Mudcrab Chitin* and *Hawk Feathers* for synovial calcification in Rockjoint).
- **PostgreSQL Oghma Infinium Integration:**
  - 15 comprehensive medical compendium entries seeded into `public.oghma` with full-text `tsvector` indexing for RAG querying.

### 4. Dual Interaction Pathways
- **Path A (In-Dialogue Context Injection):** Whenever speaking to an NPC, your physical condition and diagnosed disease state are injected into their active context with behavioral urgency directives.
- **Path B (Ambient Proximity Barks):** When walking past town NPCs while suffering from Stage 2 or Stage 3 afflictions, nearby eligible actors will dynamically bark short in-character reactions on a configurable cooldown.

### 5. Robust Engine Architecture
- **Auto-Starting Quest:** `_SH_CHIM_BridgeQuest` starts automatically on new games and save loads (`Start Game Enabled` flag enabled).
- **Dual Update Loop:**
  - **Real-Time Polling:** Every 15 seconds during active gameplay.
  - **Game-Time Polling:** Every 0.25 in-game hours during sleep, waiting, or fast travel.
  - **Immediate Cure Detection:** Detects shrine prayers or cure disease potions on the next tick and instantly resets NPC dialogue prompts back to healthy.

---

## Directory Structure

```
├── .gitignore
├── compile.sh                      # Helper to compile Papyrus via Caprica
├── install.sh                      # Deploys PHP extension to HerikaServer & seeds Oghma SQL
├── readme.md                       # Documentation
├── verify.sh                       # Runs PHP linter & PostgreSQL database checks
├── Scripts/
│   └── SunHelmCHIMBridge.pex       # Compiled Papyrus binary
├── Source/
│   └── Scripts/
│       └── SunHelmCHIMBridge.psc   # Papyrus source script
├── SunHelm_CHIM_Bridge.esp         # Bethesda SSE plugin (Start-Game-Enabled quest)
└── plugin/
    └── ext/
        └── sunhelm_needs/          # HerikaServer extension
            ├── comm.php            # Endpoint hook
            ├── config.php          # Tabbed WebUI settings & Oghma migration
            ├── context.php         # Context builder
            ├── context_pre.php     # Two-tier prompt injection & symptom mapping
            ├── manifest.json       # CHIM extension metadata
            ├── oghma_diseases.sql  # 15 medical compendium entries
            ├── preprocessing.php   # Status message payload parser
            ├── settings.json       # User settings
            └── update.php          # Settings saver
```

---

## Requirements & Compatibility
 
- **Skyrim Special Edition / Anniversary Edition** (1.5.97 / 1.6.x)
- [SKSE64](https://skse.silverlock.org/)
- [CHIM - AI Agent](https://github.com/) & HerikaServer
- [SunHelm Survival and needs](https://www.nexusmods.com/skyrimspecialedition/mods/39414)
- [SunHelm Diseases](https://www.nexusmods.com/skyrimspecialedition/mods/39414)
- *(Optional & Supported)* **Water-Borne Diseases for SunHelm (`SunHelmDirtyWater.esp`)** - Dynamic dirty water contraction spells detected natively without master dependencies.
- *(Optional & Supported)* [Immersive Diseases 2.0](https://www.nexusmods.com/skyrimspecialedition/mods/79111) for visual skin & vein overlays.

---

## Installation

### 1. Deploy the HerikaServer Extension
Run the installer script from WSL (or copy the contents of `plugin/ext/sunhelm_needs` to your `/var/www/html/HerikaServer/ext/sunhelm_needs` directory):

```bash
bash install.sh
```

This will:
1. Copy all extension files to `/var/www/html/HerikaServer/ext/sunhelm_needs/`.
2. Automatically patch `comm.php` to handle `sunhelm_disease@` payloads.
3. Seed the 15-entry medical compendium into your PostgreSQL `dwemer` database.

### 2. Install the Mod in Mod Organizer 2
1. Install this folder as a mod in your mod manager.
2. Ensure `SunHelm_CHIM_Bridge.esp` is enabled in your load order (load after `SunHelmSurvival.esp` and `SunHelmDiseases.esp`).
3. If modifying the Papyrus script, recompile using:
   ```bash
   bash compile.sh
   ```

---

## Configuration

Access the extension configuration through your browser:

`http://localhost:8081/HerikaServer/ext/sunhelm_needs/config.php`  
*(or via CHIM Server WebUI &rarr; Extensions &rarr; SunHelm Needs & Diseases)*

### Tab 1: Survival Needs
- Toggle tracking for Hunger, Thirst, Fatigue, and Cold independently.
- Set minimum stage threshold (0–5).
- Customize the prompt template injected into companion contexts.

### Tab 2: Diseases & Medicine
- Toggle disease tracking and visual symptom analysis.
- Set minimum disease stage threshold (Stage 1 Mild vs. Stage 2 Acute).
- Toggle ambient proximity barks and configure the cooldown timer (default: 180s).
- Re-run Oghma Medical Lore database migrations with one click.

---

## Verification

To verify the installation:

```bash
bash verify.sh
```

---

## License

MIT License. Free to use, adapt, and build upon.
