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
        // Tab 1: Needs
        'track_hunger'                  => isset($_POST['track_hunger']),
        'track_thirst'                  => isset($_POST['track_thirst']),
        'track_fatigue'                 => isset($_POST['track_fatigue']),
        'track_cold'                    => isset($_POST['track_cold']),
        'min_stage'                     => (int)($_POST['min_stage'] ?? 1),
        'prompt_template'               => trim($_POST['prompt_template'] ?? 'Player physical condition: The player is {conditions}. Notice their physical state when appropriate and react naturally.'),
        // Tab 2: Diseases
        'track_diseases'                => isset($_POST['track_diseases']),
        'min_disease_stage'             => (int)($_POST['min_disease_stage'] ?? 2),
        'enable_ambient_barks'          => isset($_POST['enable_ambient_barks']),
        'ambient_bark_cooldown_seconds' => (int)($_POST['ambient_bark_cooldown_seconds'] ?? 300),
        'disease_prompt_template'       => trim($_POST['disease_prompt_template'] ?? 'Health Observation: The player is afflicted with {disease_stage} ({visual_cues}). {knowledge_context}'),
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
    'disease_prompt_template'       => 'Health Observation: The player is afflicted with {disease_stage} ({visual_cues}). {knowledge_context}',
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
        .container { max-width: 860px; margin: 0 auto; background: #242629; border-radius: 8px; padding: 32px; box-shadow: 0 4px 16px rgba(0,0,0,0.5); }
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

        /* Forms */
        .form-group { margin-bottom: 20px; }
        label { display: block; font-weight: 600; margin-bottom: 8px; color: #fffffe; font-size: 14px; }
        .checkbox-label { display: inline-flex; align-items: center; gap: 8px; margin-right: 20px; font-weight: normal; cursor: pointer; font-size: 14px; }
        input[type="text"], input[type="number"], textarea, select { width: 100%; padding: 10px; border-radius: 4px; border: 1px solid #72757e; background: #16161a; color: #fffffe; box-sizing: border-box; font-size: 14px; }
        textarea { resize: vertical; min-height: 80px; font-family: inherit; }
        .hint { font-size: 12px; color: #94a1b2; margin-top: 6px; line-height: 1.4; }
        
        button { background: #7f5af0; color: #fff; border: none; padding: 12px 24px; font-size: 15px; font-weight: bold; border-radius: 4px; cursor: pointer; transition: background 0.2s; }
        button:hover { background: #6b46c1; }
        .btn-secondary { background: #32353b; color: #fffffe; }
        .btn-secondary:hover { background: #42464e; }
        
        .alert { background: #2cb67d; color: #16161a; padding: 12px; border-radius: 4px; margin-bottom: 20px; font-weight: bold; }
        .alert-info { background: #3da9fc; color: #16161a; }
        .card-panel { background: #16161a; border: 1px solid #32353b; border-radius: 6px; padding: 18px; margin-bottom: 20px; }
    </style>
</head>
<body>
<div class="container">
    <h1>SunHelm AI Bridge</h1>
    <div class="subtitle">Unified Survival Needs & Medical Pathology Integration for CHIM</div>

    <?php if ($saved): ?>
        <div class="alert">&#x2714; Settings successfully saved!</div>
    <?php endif; ?>
    <?php if ($dbMessage): ?>
        <div class="alert alert-info">&#x1F4D9; <?= htmlspecialchars($dbMessage) ?></div>
    <?php endif; ?>

    <!-- Tab Navigation -->
    <div class="tab-nav">
        <button type="button" class="tab-btn active" onclick="switchTab('tab-needs')">&#x1F37D; Tab 1: Survival Needs</button>
        <button type="button" class="tab-btn" onclick="switchTab('tab-diseases')">&#x2695;&#xFE0F; Tab 2: Diseases & Medicine</button>
    </div>

    <!-- ================================================================ -->
    <!-- TAB 1: SURVIVAL NEEDS                                            -->
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

        <form method="POST">
            <input type="hidden" name="action" value="save_settings">
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
                <div class="hint">Toggle which physical conditions are evaluated and injected into the companion prompt.</div>
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

            <button type="submit">&#x1F4BE; Save All Settings</button>
        </form>
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

        <form method="POST">
            <input type="hidden" name="action" value="save_settings">
            <!-- Hidden mirrors for Tab 1 so saving doesn't clear them -->
            <?php if (!empty($settings['track_hunger'])): ?><input type="hidden" name="track_hunger" value="1"><?php endif; ?>
            <?php if (!empty($settings['track_thirst'])): ?><input type="hidden" name="track_thirst" value="1"><?php endif; ?>
            <?php if (!empty($settings['track_fatigue'])): ?><input type="hidden" name="track_fatigue" value="1"><?php endif; ?>
            <?php if (!empty($settings['track_cold'])): ?><input type="hidden" name="track_cold" value="1"><?php endif; ?>
            <input type="hidden" name="min_stage" value="<?= (int)($settings['min_stage'] ?? 1) ?>">
            <input type="hidden" name="prompt_template" value="<?= htmlspecialchars($settings['prompt_template'] ?? '') ?>">

            <div class="form-group">
                <label>Disease Tracking</label>
                <label class="checkbox-label">
                    <input type="checkbox" name="track_diseases" <?= !empty($settings['track_diseases']) ? 'checked' : '' ?>>
                    Enable Disease & Medical Lore Integration
                </label>
                <div class="hint">Tracks vanilla diseases, SunHelm progression stages (1–3), and Water-Borne illnesses.</div>
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

            <button type="submit">&#x1F4BE; Save All Settings</button>
        </form>

        <div class="card-panel" style="margin-top: 32px;">
            <h3 style="margin-top: 0; color: #7f5af0;">&#x1F4D9; Oghma Infinium Medical Lore Pack</h3>
            <p style="font-size: 13px; color: #94a1b2; line-height: 1.5;">
                Seeds 15 authentic Tamrielic medical entries (Ataxia, Tetanus, Breakbone Fever, Meningitis, Dracunculiasis, etc.) with <strong>Two-Tier Knowledge separation</strong> into PostgreSQL:
                Commoners know vanilla folk names, while Alchemists and Healers know true clinical pathology and alchemical remedies.
            </p>
            <form method="POST">
                <input type="hidden" name="action" value="seed_oghma">
                <button type="submit" class="btn-secondary">&#x21BB; Re-seed / Update Oghma Medical Lore</button>
            </form>
        </div>
    </div>
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
