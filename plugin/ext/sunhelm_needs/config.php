<?php
$settingsFile = __DIR__ . '/settings.json';
$stateFile    = __DIR__ . '/state.json';
$sqlFile      = __DIR__ . '/oghma_diseases.sql';

$saved = false;
$dbMessage = '';

// Handle Oghma DB Seeding POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'seed_oghma') {
    if (file_exists($sqlFile)) {
        $host = 'localhost';
        $port = '5432';
        $dbname = 'dwemer';
        $username = 'dwemer';
        $password = 'dwemer';
        $conn = @pg_connect("host=$host port=$port dbname=$dbname user=$username password=$password");
        if ($conn) {
            $sql = file_get_contents($sqlFile);
            $res = @pg_query($conn, $sql);
            if ($res) {
                $dbMessage = 'Oghma Medical Compendium seeded successfully into database!';
            } else {
                $dbMessage = 'Error executing SQL: ' . pg_last_error($conn);
            }
            pg_close($conn);
        } else {
            $dbMessage = 'Failed to connect to dwemer PostgreSQL database.';
        }
    } else {
        $dbMessage = 'oghma_diseases.sql file not found.';
    }
}

// Handle Settings Save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (!isset($_POST['action']) || $_POST['action'] === 'save_settings')) {
    $existing = file_exists($settingsFile) ? json_decode(file_get_contents($settingsFile), true) : [];
    $newSettings = array_merge($existing, [
        // Tab 1: Survival Needs
        'track_hunger'                  => isset($_POST['track_hunger']),
        'track_thirst'                  => isset($_POST['track_thirst']),
        'track_fatigue'                 => isset($_POST['track_fatigue']),
        'track_cold'                    => isset($_POST['track_cold']),
        'min_stage'                     => (int)($_POST['min_stage'] ?? 1),
        'prompt_template'               => trim($_POST['prompt_template'] ?? 'Player physical condition: The player is {conditions}. Notice their physical state when appropriate and react naturally.'),
        // Tab 2: Diseases & Medicine
        'track_diseases'                => isset($_POST['track_diseases']),
        'min_disease_stage'             => (int)($_POST['min_disease_stage'] ?? 2),
        'enable_ambient_barks'          => isset($_POST['enable_ambient_barks']),
        'ambient_bark_cooldown_seconds' => (int)($_POST['ambient_bark_cooldown_seconds'] ?? 300),
        'disease_prompt_template'       => trim($_POST['disease_prompt_template'] ?? 'Health Observation: You visibly notice the player exhibiting {visual_cues}. {knowledge_context}'),
        // Tab 3: Mod Integrations & Compatibility
        'mod_sunhelm_diseases'          => isset($_POST['mod_sunhelm_diseases']),
        'mod_waterborne_diseases'       => isset($_POST['mod_waterborne_diseases']),
        'mod_immersive_diseases'        => isset($_POST['mod_immersive_diseases']),
        'mod_oghma_clinical_lore'       => isset($_POST['mod_oghma_clinical_lore']),
        // Follower condition (SunHelm - Individual Follower Needs)
        'mod_sunhelm_followers'         => isset($_POST['mod_sunhelm_followers']),
        'followers_profile_line'        => isset($_POST['followers_profile_line']),
        'followers_turn_instruction'    => isset($_POST['followers_turn_instruction']),
        'followers_report_needs'        => isset($_POST['followers_report_needs']),
        'followers_report_drunk'        => isset($_POST['followers_report_drunk']),
        'followers_report_disease'      => isset($_POST['followers_report_disease']),
        'followers_observable_to_others' => isset($_POST['followers_observable_to_others']),
        'followers_mention_chance'      => max(0, min(100, (int)($_POST['followers_mention_chance'] ?? 35))),
        'followers_min_stage'           => max(1, min(5, (int)($_POST['followers_min_stage'] ?? 3))),
    ]);
    file_put_contents($settingsFile, json_encode($newSettings, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    $saved = true;
}

$settings = file_exists($settingsFile) ? json_decode(file_get_contents($settingsFile), true) : [
    'track_hunger'                  => true,
    'track_thirst'                  => true,
    'track_fatigue'                 => true,
    'track_cold'                    => true,
    'min_stage'                     => 1,
    'prompt_template'               => 'Player physical condition: The player is {conditions}. Notice their physical state when appropriate and react naturally.',
    'track_diseases'                => true,
    'min_disease_stage'             => 2,
    'enable_ambient_barks'          => true,
    'ambient_bark_cooldown_seconds' => 300,
    'disease_prompt_template'       => 'Health Observation: You visibly notice the player exhibiting {visual_cues}. {knowledge_context}',
    'mod_sunhelm_diseases'          => true,
    'mod_waterborne_diseases'       => true,
    'mod_immersive_diseases'        => true,
    'mod_oghma_clinical_lore'       => true,
    'mod_sunhelm_followers'         => true,
    'followers_profile_line'        => true,
    'followers_turn_instruction'    => true,
    'followers_report_needs'        => true,
    'followers_report_drunk'        => true,
    'followers_report_disease'      => true,
    'followers_observable_to_others' => true,
    'followers_mention_chance'      => 35,
    'followers_min_stage'           => 3,
];

$state    = file_exists($stateFile) ? json_decode(file_get_contents($stateFile), true) : [];
$needs    = $state['player_needs'] ?? [];
$disease  = $state['player_disease'] ?? [];

$needMeta = [
    ['key' => 'hunger',  'label' => 'Hunger',  'stageKey' => 'hunger_stage',  'labelKey' => 'hunger_label'],
    ['key' => 'thirst',  'label' => 'Thirst',  'stageKey' => 'thirst_stage',  'labelKey' => 'thirst_label'],
    ['key' => 'fatigue', 'label' => 'Fatigue', 'stageKey' => 'fatigue_stage', 'labelKey' => 'fatigue_label'],
    ['key' => 'cold',    'label' => 'Cold',    'stageKey' => 'cold_stage',    'labelKey' => 'cold_label'],
];

function getStageColor($stage) {
    if ($stage <= 1) return '#2cb67d';
    if ($stage == 2) return '#ffbc6b';
    if ($stage == 3) return '#f5a623';
    return '#ef4444';
}

function getDiseaseColor($stage) {
    if ($stage == 0) return '#2cb67d';
    if ($stage == 1) return '#ffbc6b';
    if ($stage == 2) return '#f5a623';
    return '#ef4444';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>SunHelm CHIM Bridge - Configuration</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #16161a; color: #fffffe; padding: 24px; margin: 0; }
        .container { max-width: 880px; margin: 0 auto; background: #242629; border-radius: 8px; padding: 32px; box-shadow: 0 4px 16px rgba(0,0,0,0.5); }
        h1 { color: #7f5af0; margin-top: 0; margin-bottom: 6px; }
        .subtitle { color: #94a1b2; font-size: 14px; margin-bottom: 24px; }
        
        /* Tab Styling */
        .tab-nav { display: flex; gap: 8px; border-bottom: 2px solid #32353b; margin-bottom: 24px; }
        .tab-btn { background: transparent; border: none; border-bottom: 3px solid transparent; color: #94a1b2; padding: 12px 20px; font-size: 15px; font-weight: 600; cursor: pointer; transition: all 0.2s ease; }
        .tab-btn:hover { color: #fffffe; }
        .tab-btn.active { color: #7f5af0; border-bottom-color: #7f5af0; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }

        /* Status Grid */
        .status-box { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 24px; background: #16161a; padding: 16px; border-radius: 6px; }
        .status-item { text-align: center; }
        .status-label { font-size: 11px; color: #94a1b2; text-transform: uppercase; letter-spacing: 0.5px; }
        .status-val { font-size: 17px; font-weight: bold; margin-top: 4px; }
        .status-stage { font-size: 11px; color: #72757e; margin-top: 2px; }

        /* Disease Status Card */
        .disease-card { background: #16161a; border-radius: 6px; padding: 20px; margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center; border-left: 5px solid #2cb67d; }
        .disease-info h3 { margin: 0 0 6px 0; font-size: 18px; }
        .disease-info p { margin: 0; color: #94a1b2; font-size: 13px; }
        .disease-badge { padding: 6px 14px; border-radius: 20px; font-size: 13px; font-weight: bold; }

        /* Mod Compatibility Cards */
        .mod-card { background: #16161a; border: 1px solid #32353b; border-radius: 6px; padding: 18px; margin-bottom: 16px; transition: border-color 0.2s; }
        .mod-card:hover { border-color: #7f5af0; }
        .mod-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; }
        .mod-title { font-size: 15px; font-weight: bold; color: #fffffe; }
        .badge-active { background: #2cb67d; color: #16161a; padding: 3px 10px; border-radius: 12px; font-size: 11px; font-weight: bold; }
        .badge-bypassed { background: #72757e; color: #fffffe; padding: 3px 10px; border-radius: 12px; font-size: 11px; font-weight: bold; }

        /* Forms */
        .form-group { margin-bottom: 20px; }
        label { display: block; font-weight: 600; margin-bottom: 8px; color: #fffffe; font-size: 14px; }
        .checkbox-label { display: inline-flex; align-items: center; gap: 8px; margin-right: 20px; font-weight: normal; cursor: pointer; font-size: 14px; }
        input[type="text"], input[type="number"], textarea, select { width: 100%; padding: 10px; border-radius: 4px; border: 1px solid #72757e; background: #16161a; color: #fffffe; box-sizing: border-box; font-size: 14px; }
        textarea { resize: vertical; min-height: 80px; font-family: inherit; }
        .hint { font-size: 12px; color: #94a1b2; margin-top: 6px; line-height: 1.4; }
        
        .btn-primary { background: #7f5af0; color: #fff; border: none; padding: 12px 24px; font-size: 15px; font-weight: bold; border-radius: 4px; cursor: pointer; transition: background 0.2s; }
        .btn-primary:hover { background: #6b46c1; }
        .btn-secondary { background: #32353b; color: #fffffe; border: none; padding: 10px 18px; font-size: 13px; font-weight: bold; border-radius: 4px; cursor: pointer; }
        .btn-secondary:hover { background: #42464e; }
        
        .alert { background: #2cb67d; color: #16161a; padding: 12px; border-radius: 4px; margin-bottom: 20px; font-weight: bold; }
        .alert-info { background: #3da9fc; color: #16161a; }
        .card-panel { background: #16161a; border: 1px solid #32353b; border-radius: 6px; padding: 18px; margin-bottom: 20px; }
        .callout-box { background: rgba(127, 90, 240, 0.1); border-left: 4px solid #7f5af0; padding: 14px; border-radius: 4px; margin-bottom: 20px; font-size: 13px; color: #fffffe; line-height: 1.5; }
    </style>
</head>
<body>
<div class="container">
    <h1>SunHelm AI Bridge</h1>
    <div class="subtitle">Unified Survival Needs & Medical Pathology Integration for CHIM</div>

    <?php if ($saved): ?>
        <div class="alert">&#x2714; Settings successfully saved across all tabs!</div>
    <?php endif; ?>
    <?php if ($dbMessage): ?>
        <div class="alert alert-info">&#x1F4D9; <?= htmlspecialchars($dbMessage) ?></div>
    <?php endif; ?>

    <!-- Tab Navigation -->
    <div class="tab-nav">
        <button type="button" class="tab-btn active" onclick="switchTab('tab-needs')">&#x1F37D; Tab 1: Survival Needs</button>
        <button type="button" class="tab-btn" onclick="switchTab('tab-diseases')">&#x2695;&#xFE0F; Tab 2: Diseases & Medicine</button>
        <button type="button" class="tab-btn" onclick="switchTab('tab-mods')">&#x1F9E9; Tab 3: Mod Compatibility</button>
    </div>

    <!-- Unified Form Enclosing All Tabs -->
    <form method="POST">
        <input type="hidden" name="action" value="save_settings">

        <!-- ================================================================ -->
        <!-- TAB 1: SURVIVAL NEEDS (SunHelm Core)                            -->
        <!-- ================================================================ -->
        <div id="tab-needs" class="tab-content active">
            <div class="status-box">
                <?php foreach ($needMeta as $n): ?>
                    <?php
                        $stage = (int)($needs[$n['stageKey']] ?? 0);
                        $label = $needs[$n['labelKey']] ?? 'Healthy';
                        $color = getStageColor($stage);
                    ?>
                    <div class="status-item">
                        <div class="status-label"><?= htmlspecialchars($n['label']) ?></div>
                        <div class="status-val" style="color: <?= $color ?>;"><?= htmlspecialchars($label) ?></div>
                        <div class="status-stage">Stage <?= $stage ?> / 5</div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="form-group">
                <label>Tracked Needs</label>
                <div style="margin-top: 8px;">
                    <label class="checkbox-label">
                        <input type="checkbox" name="track_hunger" <?= !empty($settings['track_hunger']) ? 'checked' : '' ?>> Hunger
                    </label>
                    <label class="checkbox-label">
                        <input type="checkbox" name="track_thirst" <?= !empty($settings['track_thirst']) ? 'checked' : '' ?>> Thirst
                    </label>
                    <label class="checkbox-label">
                        <input type="checkbox" name="track_fatigue" <?= !empty($settings['track_fatigue']) ? 'checked' : '' ?>> Fatigue
                    </label>
                    <label class="checkbox-label">
                        <input type="checkbox" name="track_cold" <?= !empty($settings['track_cold']) ? 'checked' : '' ?>> Cold (Hypothermia)
                    </label>
                </div>
                <div class="hint">Toggle which core physical conditions are evaluated and injected into companion prompts.</div>
            </div>

            <div class="form-group">
                <label for="min_stage">Minimum Stage to Report</label>
                <select id="min_stage" name="min_stage">
                    <option value="1" <?= ($settings['min_stage'] ?? 1) == 1 ? 'selected' : '' ?>>Stage 1 - Mild (Satisfied / Chilly)</option>
                    <option value="2" <?= ($settings['min_stage'] ?? 1) == 2 ? 'selected' : '' ?>>Stage 2 - Moderate (Peckish / Thirsty / Tired / Cold)</option>
                    <option value="3" <?= ($settings['min_stage'] ?? 1) == 3 ? 'selected' : '' ?>>Stage 3 - Severe (Hungry / Parched / Weary / Freezing)</option>
                    <option value="4" <?= ($settings['min_stage'] ?? 1) == 4 ? 'selected' : '' ?>>Stage 4 - Critical (Starving / Exhausted / Frigid)</option>
                </select>
                <div class="hint">Determines how intense a physical need must be before companions start noticing.</div>
            </div>

            <div class="form-group">
                <label for="prompt_template">Needs Prompt Template</label>
                <textarea id="prompt_template" name="prompt_template"><?= htmlspecialchars($settings['prompt_template'] ?? '') ?></textarea>
                <div class="hint">Available placeholder: <code>{conditions}</code> (e.g. "thirsty and freezing").</div>
            </div>

            <button type="submit" class="btn-primary">&#x1F4BE; Save All Settings</button>
        </div>

        <!-- ================================================================ -->
        <!-- TAB 2: DISEASES & MEDICINE                                       -->
        <!-- ================================================================ -->
        <div id="tab-diseases" class="tab-content">
            <?php
                $hasDis    = !empty($disease['has_disease']);
                $dStage    = (int)($disease['stage'] ?? 0);
                $dName     = $disease['disease_name'] ?? 'Healthy';
                $dLabel    = $disease['stage_label'] ?? ($hasDis ? $dName : 'No Active Afflictions');
                $dCues     = $disease['visual_cues'] ?? 'None';
                $borderCol = getDiseaseColor($dStage);
            ?>
            <div class="disease-card" style="border-left-color: <?= $borderCol ?>;">
                <div class="disease-info">
                    <h3><?= htmlspecialchars($dLabel) ?></h3>
                    <p><strong>Visual Symptoms:</strong> <?= htmlspecialchars($dCues) ?></p>
                    <p style="margin-top: 4px; font-size: 11px; color: #72757e;">Updated: <?= htmlspecialchars($disease['updated_at'] ?? 'Never') ?></p>
                </div>
                <div>
                    <span class="disease-badge" style="background: <?= $borderCol ?>; color: #16161a;">
                        <?= $hasDis ? 'Stage ' . $dStage : 'Clean' ?>
                    </span>
                </div>
            </div>

            <div class="form-group">
                <label>Disease Tracking</label>
                <label class="checkbox-label">
                    <input type="checkbox" name="track_diseases" <?= !empty($settings['track_diseases']) ? 'checked' : '' ?>>
                    Enable Disease & Medical Lore Integration
                </label>
                <div class="hint">Global toggle for all disease perception and medical prompt injections.</div>
            </div>

            <div class="form-group">
                <label for="min_disease_stage">Minimum Stage to Draw Attention</label>
                <select id="min_disease_stage" name="min_disease_stage">
                    <option value="1" <?= ($settings['min_disease_stage'] ?? 2) == 1 ? 'selected' : '' ?>>Stage 1 - Mild (Early onset; subtle remarks)</option>
                    <option value="2" <?= ($settings['min_disease_stage'] ?? 2) == 2 ? 'selected' : '' ?>>Stage 2 - Acute (Recommended; visible rash/limp, clear remarks)</option>
                    <option value="3" <?= ($settings['min_disease_stage'] ?? 2) == 3 ? 'selected' : '' ?>>Stage 3 - Severe (Emergency only; full agony, locked joints/cough)</option>
                </select>
                <div class="hint">Commoners and shopkeepers will not notice Stage 1 if set to Acute (recommended for realism).</div>
            </div>

            <div class="form-group">
                <label>Ambient Town Barks (Path B)</label>
                <label class="checkbox-label">
                    <input type="checkbox" name="enable_ambient_barks" <?= !empty($settings['enable_ambient_barks']) ? 'checked' : '' ?>>
                    Allow nearby town NPCs & passersby to react aloud to Stage 2/3 diseases
                </label>
                <div class="hint">When walking past guards or citizens in town, they will occasionally make short spontaneous comments regarding your visible sickness.</div>
            </div>

            <div class="form-group">
                <label for="ambient_bark_cooldown_seconds">Ambient Bark Cooldown (Seconds)</label>
                <input type="number" id="ambient_bark_cooldown_seconds" name="ambient_bark_cooldown_seconds" min="60" max="1800" step="30" value="<?= (int)($settings['ambient_bark_cooldown_seconds'] ?? 300) ?>">
                <div class="hint">Default is 300 seconds (5 minutes). Prevents passersby from spamming comments too frequently.</div>
            </div>

            <div class="form-group">
                <label for="disease_prompt_template">Disease Prompt Template</label>
                <textarea id="disease_prompt_template" name="disease_prompt_template"><?= htmlspecialchars($settings['disease_prompt_template'] ?? '') ?></textarea>
                <div class="hint">Available placeholders: <code>{disease_stage}</code>, <code>{visual_cues}</code>, <code>{knowledge_context}</code>.</div>
            </div>

            <button type="submit" class="btn-primary">&#x1F4BE; Save All Settings</button>

            <div class="card-panel" style="margin-top: 32px;">
                <h3 style="margin-top: 0; color: #7f5af0;">&#x1F4D9; Oghma Infinium Medical Lore Pack</h3>
                <p style="font-size: 13px; color: #94a1b2; line-height: 1.5;">
                    Seeds 15 authentic Tamrielic medical entries (Ataxia, Tetanus, Breakbone Fever, Meningitis, Dracunculiasis, etc.) with <strong>Two-Tier Knowledge separation</strong> into PostgreSQL:
                    Commoners know vanilla folk names, while Alchemists and Healers know true clinical pathology and alchemical remedies.
                </p>
                <button type="submit" name="action" value="seed_oghma" class="btn-secondary">&#x21BB; Re-seed / Update Oghma Medical Lore</button>
            </div>
        </div>

        <!-- ================================================================ -->
        <!-- TAB 3: MOD INTEGRATIONS & COMPATIBILITY                           -->
        <!-- ================================================================ -->
        <div id="tab-mods" class="tab-content">
            <div class="callout-box">
                <strong>&#x2139;&#xFE0F; Zero-Dependency Modular Architecture:</strong><br>
                Core SunHelm (<code>SunHelmSurvival.esp</code>) is the only required master. All other mods are dynamically bound at runtime via Papyrus <code>Game.GetFormFromFile()</code>.
                Use the toggles below to selectively enable or bypass features based on your personal load order and preferences.
            </div>

            <!-- Mod 1: SunHelm Diseases -->
            <?php $modShDiseasesActive = !empty($settings['mod_sunhelm_diseases']); ?>
            <div class="mod-card">
                <div class="mod-header">
                    <div class="mod-title">&#x1FA7A; SunHelm Diseases (<code>SunHelmDiseases.esp</code>)</div>
                    <span class="<?= $modShDiseasesActive ? 'badge-active' : 'badge-bypassed' ?>">
                        <?= $modShDiseasesActive ? 'Active' : 'Bypassed' ?>
                    </span>
                </div>
                <label class="checkbox-label">
                    <input type="checkbox" name="mod_sunhelm_diseases" <?= $modShDiseasesActive ? 'checked' : '' ?>>
                    <strong>Enable SunHelm Multi-Stage Progression (Stages 1–3)</strong>
                </label>
                <div class="hint">
                    Tracks full multi-tier progression (Stage 1 Mild &rarr; Stage 2 Acute &rarr; Stage 3 Severe Emergency).<br>
                    <em>When bypassed:</em> Diseases are treated as standard static single-stage Skyrim illnesses without escalating emergency behavioral directives.
                </div>
            </div>

            <!-- Mod 2: Water-Borne Diseases -->
            <?php $modWaterborneActive = !empty($settings['mod_waterborne_diseases']); ?>
            <div class="mod-card">
                <div class="mod-header">
                    <div class="mod-title">&#x1F4A7; Water-Borne Diseases for SunHelm (<code>SunHelmDirtyWater.esp</code>)</div>
                    <span class="<?= $modWaterborneActive ? 'badge-active' : 'badge-bypassed' ?>">
                        <?= $modWaterborneActive ? 'Active' : 'Bypassed' ?>
                    </span>
                </div>
                <label class="checkbox-label">
                    <input type="checkbox" name="mod_waterborne_diseases" <?= $modWaterborneActive ? 'checked' : '' ?>>
                    <strong>Enable Dirty Water Contraction Detection</strong>
                </label>
                <div class="hint">
                    Detects when illnesses (Chills, Dampworm, Droops, Feeble Limb, Shakes, Swamp Fever, Wither) are contracted from drinking untreated wild water.<br>
                    <em>When bypassed:</em> Contractions resulting directly from dirty water are ignored and not forwarded to NPC dialogue prompts.
                </div>
            </div>

            <!-- Mod 3: Immersive Diseases 2.0 -->
            <?php $modImmersiveActive = !empty($settings['mod_immersive_diseases']); ?>
            <div class="mod-card">
                <div class="mod-header">
                    <div class="mod-title">&#x1F3A8; Immersive Diseases 2.0 (<code>Immersive Diseases.esp</code>)</div>
                    <span class="<?= $modImmersiveActive ? 'badge-active' : 'badge-bypassed' ?>">
                        <?= $modImmersiveActive ? 'Active' : 'Bypassed' ?>
                    </span>
                </div>
                <label class="checkbox-label">
                    <input type="checkbox" name="mod_immersive_diseases" <?= $modImmersiveActive ? 'checked' : '' ?>>
                    <strong>Enable RaceMenu Overlays & Visual Symptom Mirroring</strong>
                </label>
                <div class="hint">
                    Mirrors cosmetic visual overlays (bulging dark purple veins, raw blotches, boils, necrotic skin, crooked elbows, limp arm) directly into NPC vision prompts.<br>
                    <em>When bypassed:</em> NPCs observe general sickness and fatigue without describing specific cosmetic skin textures or posture overlays that may not be present on your character.
                </div>
            </div>

            <!-- Mod 4: Two-Tier Oghma Clinical Lore System -->
            <?php $modClinicalActive = !empty($settings['mod_oghma_clinical_lore']); ?>
            <div class="mod-card">
                <div class="mod-header">
                    <div class="mod-title">&#x1F4DA; Two-Tier Oghma Medical Lore (Clinical Pathology vs Folklore)</div>
                    <span class="<?= $modClinicalActive ? 'badge-active' : 'badge-bypassed' ?>">
                        <?= $modClinicalActive ? 'Active' : 'Bypassed' ?>
                    </span>
                </div>
                <label class="checkbox-label">
                    <input type="checkbox" name="mod_oghma_clinical_lore" <?= $modClinicalActive ? 'checked' : '' ?>>
                    <strong>Enable Clinical Diagnoses for Apothecaries & Scholars</strong>
                </label>
                <div class="hint">
                    Allows master alchemists, healers, and scholars (Arcadia, Danica, Farengar, Colette, etc.) to diagnose illnesses by true clinical pathology (Tetanus, Breakbone Fever, Meningitis, Dracunculiasis, Ergotism) and prescribe alchemical remedies.<br>
                    <em>When bypassed:</em> All NPCs, including expert healers, use traditional Skyrim folk names (Rockjoint, Bone Break Fever) and divine shrine advice.
                </div>
            </div>

            <!-- Mod 5: Follower condition -->
            <?php
            $modFollowersActive = !empty($settings['mod_sunhelm_followers']);
            // Detected rather than declared: the card reports whether follower data has actually
            // arrived, so "I enabled it and nothing happens" is answerable from this page instead
            // of by reading logs.
            $knownFollowers = array_keys((array)($state['followers'] ?? []));
            $followerSeen   = count($knownFollowers) > 0;
            ?>
            <div class="mod-card">
                <div class="mod-header">
                    <div class="mod-title">&#x1F9D1;&#x200D;&#x1F91D;&#x200D;&#x1F9D1; Follower Condition (<code>SunHelm - Individual Follower Needs</code>)</div>
                    <span class="<?= $modFollowersActive ? 'badge-active' : 'badge-bypassed' ?>">
                        <?= $modFollowersActive ? 'Active' : 'Bypassed' ?>
                    </span>
                </div>
                <label class="checkbox-label">
                    <input type="checkbox" name="mod_sunhelm_followers" <?= $modFollowersActive ? 'checked' : '' ?>>
                    <strong>Let followers speak from their own hunger, thirst, drink and illness</strong>
                </label>
                <div class="hint">
                    Requires the <em>SunHelm - Individual Follower Needs</em> SKSE plugin, which tracks each companion separately. Entirely optional: without it no follower data is ever sent and nothing here runs.<br>
                    <?php if ($followerSeen): ?>
                        <strong>Reporting now:</strong> <?= htmlspecialchars(implode(', ', $knownFollowers)) ?>
                    <?php else: ?>
                        <em>No follower data received yet.</em> Expected if you do not have that mod, or have not travelled with a companion since installing it.
                    <?php endif; ?>
                </div>

                <div style="margin-top:12px; padding-left:18px; border-left:2px solid #444;">
                    <label class="checkbox-label">
                        <input type="checkbox" name="followers_profile_line" <?= !empty($settings['followers_profile_line']) ? 'checked' : '' ?>>
                        Standing note on the follower's own profile
                    </label>
                    <label class="checkbox-label">
                        <input type="checkbox" name="followers_turn_instruction" <?= !empty($settings['followers_turn_instruction']) ? 'checked' : '' ?>>
                        Prompt them to bring it up in conversation
                    </label>
                    <label class="checkbox-label">
                        <input type="checkbox" name="followers_observable_to_others" <?= !empty($settings['followers_observable_to_others']) ? 'checked' : '' ?>>
                        Let other NPCs notice a drunk or ill companion
                    </label>
                    <div class="hint" style="margin-top:4px;">
                        Only what is visible is shared with others - a stranger can see a companion is drunk or unwell, never that they are hungry or thirsty.
                    </div>

                    <label class="checkbox-label" style="margin-top:10px;">
                        <input type="checkbox" name="followers_report_needs" <?= !empty($settings['followers_report_needs']) ? 'checked' : '' ?>>
                        Report hunger and thirst
                    </label>
                    <label class="checkbox-label">
                        <input type="checkbox" name="followers_report_drunk" <?= !empty($settings['followers_report_drunk']) ? 'checked' : '' ?>>
                        Report drunkenness
                    </label>
                    <label class="checkbox-label">
                        <input type="checkbox" name="followers_report_disease" <?= !empty($settings['followers_report_disease']) ? 'checked' : '' ?>>
                        Report illness
                    </label>

                    <div style="margin-top:12px;">
                        <label><strong>Chance of mentioning it when you talk to them</strong></label><br>
                        <input type="number" name="followers_mention_chance" min="0" max="100"
                               value="<?= (int)($settings['followers_mention_chance'] ?? 35) ?>"> %
                        <div class="hint">
                            The dial that decides between characterful and exhausting. Rises automatically when a follower is badly off, and a starving one always speaks regardless.
                        </div>
                    </div>

                    <div style="margin-top:12px;">
                        <label><strong>Stay quiet below stage</strong></label><br>
                        <input type="number" name="followers_min_stage" min="1" max="5"
                               value="<?= (int)($settings['followers_min_stage'] ?? 3) ?>">
                        <div class="hint">
                            1 Satisfied &middot; 2 Peckish &middot; 3 Hungry &middot; 4 Ravenous &middot; 5 Starving. Being slightly peckish is not worth a line of dialogue.
                        </div>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn-primary">&#x1F4BE; Save All Settings</button>
        </div>
    </form>
</div>

<script>
function switchTab(tabId) {
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
    document.getElementById(tabId).classList.add('active');
    event.target.classList.add('active');
}
</script>
</body>
</html>
