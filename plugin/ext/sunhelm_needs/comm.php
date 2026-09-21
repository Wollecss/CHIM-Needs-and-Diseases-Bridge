<?php
// SunHelm Needs Bridge - payload parser (player only).
// Catches messages sent via AIAgentFunctions.logMessage() that reach comm.php.
//
// Payload:
//   sunhelm_needs@<hunger>@<thirst>@<fatigue>@<cold>   (0-5 stages, player)
//
// State schema:
//   { "player_needs": {
//       "hunger_stage":2, "hunger_label":"Hungry",
//       "thirst_stage":0, "thirst_label":"Quenched",
//       "fatigue_stage":1, "fatigue_label":"Tired",
//       "cold_stage":4, "cold_label":"Freezing"
//     },
//     "updated_at": "..." }

$statePath = __DIR__ . '/state.json';

$labelMaps = [
    'hunger'  => [0 => 'Sated', 1 => 'Satisfied', 2 => 'Peckish', 3 => 'Hungry', 4 => 'Ravenous', 5 => 'Starving'],
    'thirst'  => [0 => 'Quenched', 1 => 'Sated', 2 => 'Thirsty', 3 => 'Parched', 4 => 'Dehydrated', 5 => 'Severely Dehydrated'],
    'fatigue' => [0 => 'Rested', 1 => 'Rested', 2 => 'Slightly Tired', 3 => 'Tired', 4 => 'Weary', 5 => 'Exhausted'],
    'cold'    => [0 => 'Warm', 1 => 'Comfortable', 2 => 'Chilly', 3 => 'Cold', 4 => 'Freezing', 5 => 'Frigid'],
];

function sh_load_state($statePath) {
    if (file_exists($statePath)) {
        $s = json_decode(file_get_contents($statePath), true);
        if (is_array($s)) return $s;
    }
    return [];
}

function sh_save_state($statePath, $state) {
    $state['updated_at'] = date('Y-m-d H:i:s');
    file_put_contents($statePath, json_encode($state, JSON_PRETTY_PRINT));
}

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

if (isset($gameRequest) && is_array($gameRequest)) {
    $msg = $gameRequest[3] ?? '';

    if (strpos($msg, 'sunhelm_needs@') === 0) {
        $parts = explode('@', $msg);
        if (count($parts) >= 5) {
            $state = sh_load_state($statePath);
            // Migrate legacy flat schema if present.
            if (!isset($state['player_needs']) && isset($state['hunger'])) {
                $state['player_needs'] = sh_build_player_needs([0, $state['hunger'], $state['thirst'], $state['fatigue'], $state['cold'] ?? 0], $labelMaps);
                unset($state['hunger'], $state['thirst'], $state['fatigue'], $state['cold']);
            }
            // Migrate previous nested 'player' schema if present.
            if (!isset($state['player_needs']) && isset($state['player'])) {
                $p = $state['player'];
                $state['player_needs'] = sh_build_player_needs([0, $p['hunger'] ?? 0, $p['thirst'] ?? 0, $p['fatigue'] ?? 0, $p['cold'] ?? 0], $labelMaps);
                unset($state['player']);
            }
            $state['player_needs'] = sh_build_player_needs($parts, $labelMaps);
            sh_save_state($statePath, $state);
        }
    }
}
