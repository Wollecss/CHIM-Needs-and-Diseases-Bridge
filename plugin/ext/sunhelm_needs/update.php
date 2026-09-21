<?php
// SunHelm Needs Bridge - manual/HTTP update endpoint (player only).
header('Content-Type: application/json');

$stateFile = __DIR__ . '/state.json';

$labelMaps = [
    'hunger'  => [0 => 'Sated', 1 => 'Satisfied', 2 => 'Peckish', 3 => 'Hungry', 4 => 'Ravenous', 5 => 'Starving'],
    'thirst'  => [0 => 'Quenched', 1 => 'Sated', 2 => 'Thirsty', 3 => 'Parched', 4 => 'Dehydrated', 5 => 'Severely Dehydrated'],
    'fatigue' => [0 => 'Rested', 1 => 'Rested', 2 => 'Slightly Tired', 3 => 'Tired', 4 => 'Weary', 5 => 'Exhausted'],
    'cold'    => [0 => 'Warm', 1 => 'Comfortable', 2 => 'Chilly', 3 => 'Cold', 4 => 'Freezing', 5 => 'Frigid'],
];

function sh_build_player_needs($parts, $labelMaps) {
    return [
        'hunger_stage'  => (int)$parts[1],
        'hunger_label'  => $labelMaps['hunger'][(int)$parts[1]] ?? 'Unknown',
        'thirst_stage'  => (int)$parts[2],
        'thirst_label'  => $labelMaps['thirst'][(int)$parts[2]] ?? 'Unknown',
        'fatigue_stage' => (int)$parts[3],
        'fatigue_label' => $labelMaps['fatigue'][(int)$parts[3]] ?? 'Unknown',
        'cold_stage'    => (int)$parts[4],
        'cold_label'    => $labelMaps['cold'][(int)$parts[4]] ?? 'Unknown',
    ];
}

$state = [];
if (file_exists($stateFile)) {
    $existing = json_decode(file_get_contents($stateFile), true);
    if (is_array($existing)) $state = $existing;
}

$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!$data) $data = $_REQUEST;

// Accept either flat stages or a nested player_needs block.
$needs = [];
if (isset($data['player_needs']) && is_array($data['player_needs'])) {
    $needs = $data['player_needs'];
} elseif (isset($data['hunger']) || isset($data['player'])) {
    $p = $data['player'] ?? $data;
    $needs = sh_build_player_needs([0, $p['hunger'] ?? 0, $p['thirst'] ?? 0, $p['fatigue'] ?? 0, $p['cold'] ?? 0], $labelMaps);
}

if (!empty($needs)) {
    // Preserve existing labels if a partial update omits them.
    $existingNeeds = $state['player_needs'] ?? [];
    foreach (['hunger', 'thirst', 'fatigue', 'cold'] as $k) {
        $stageKey = $k . '_stage';
        $labelKey = $k . '_label';
        if (!isset($needs[$labelKey]) || $needs[$labelKey] === '') {
            $stage = (int)($needs[$stageKey] ?? ($existingNeeds[$stageKey] ?? 0));
            $needs[$labelKey] = $labelMaps[$k][$stage] ?? ($existingNeeds[$labelKey] ?? 'Unknown');
        }
    }
    $state['player_needs'] = $needs;
}

$state['updated_at'] = date('Y-m-d H:i:s');
file_put_contents($stateFile, json_encode($state, JSON_PRETTY_PRINT));

echo json_encode(['status' => 'success', 'updated_state' => $state]);
