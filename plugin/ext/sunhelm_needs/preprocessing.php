<?php
// Catch SunHelm needs and disease updates sent through AIAgentFunctions
require_once __DIR__ . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'sunhelm_followers.php';

$req = $GLOBALS["gameRequest"] ?? (isset($gameRequest) ? $gameRequest : null);

if (is_array($req)) {
    $msg = $req[3] ?? '';

    // Follower payloads are per actor and arrive more often than the player's, so they take their
    // own parser and return early rather than adding another branch to the chain below. Doing this
    // before state.json is read also keeps the two writers off the same in-memory copy: the
    // follower path does its own atomic read-modify-write.
    if (sunhelmFollowersIngestCurrentRequest((string) $msg)) {
        return;
    }

    $stateFile = __DIR__ . '/state.json';
    $state = file_exists($stateFile) ? json_decode(file_get_contents($stateFile), true) : [];
    if (!is_array($state)) {
        $state = [];
    }

    if (strpos($msg, 'sunhelm_needs@') === 0) {
        $parts = explode('@', $msg);
        if (count($parts) >= 5) {
            $hungerStage  = (int)$parts[1];
            $thirstStage  = (int)$parts[2];
            $fatigueStage = (int)$parts[3];
            $coldStage    = (int)$parts[4];

            $hungerLabels  = ['Well Fed', 'Satisfied', 'Peckish', 'Hungry', 'Ravenous', 'Starving'];
            $thirstLabels  = ['Quenched', 'Sated', 'Thirsty', 'Parched', 'Dehydrated', 'Severely Dehydrated'];
            $fatigueLabels = ['Well Rested', 'Rested', 'Slightly Tired', 'Tired', 'Weary', 'Exhausted'];
            $coldLabels    = ['Warm', 'Comfortable', 'Chilly', 'Cold', 'Freezing', 'Frigid'];

            $state['player_needs'] = [
                'hunger_stage'  => $hungerStage,
                'hunger_label'  => $hungerLabels[$hungerStage] ?? 'Unknown',
                'thirst_stage'  => $thirstStage,
                'thirst_label'  => $thirstLabels[$thirstStage] ?? 'Unknown',
                'fatigue_stage' => $fatigueStage,
                'fatigue_label' => $fatigueLabels[$fatigueStage] ?? 'Unknown',
                'cold_stage'    => $coldStage,
                'cold_label'    => $coldLabels[$coldStage] ?? 'Unknown',
            ];
            $state['updated_at'] = date('Y-m-d H:i:s');
            file_put_contents($stateFile, json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        }
    } elseif (strpos($msg, 'sunhelm_disease@') === 0) {
        $parts = explode('@', $msg);
        if (count($parts) >= 4) {
            $diseaseName = trim($parts[1]);
            $stage       = (int)$parts[2];
            $visualCues  = trim($parts[3]);

            $stageLabels = [0 => 'Healthy', 1 => 'Mild', 2 => 'Acute', 3 => 'Severe'];
            $stageLabel  = $stageLabels[$stage] ?? 'Afflicted';

            $formattedStage = ($stage > 0 && strcasecmp($diseaseName, 'None') !== 0 && strcasecmp($diseaseName, 'Food Poisoning') !== 0)
                ? ($stageLabel . ' ' . $diseaseName)
                : $diseaseName;

            $isWaterborne = (strpos($visualCues, 'waterborne') !== false);

            $state['player_disease'] = [
                'has_disease'    => ($stage > 0 && strcasecmp($diseaseName, 'None') !== 0),
                'disease_name'   => $diseaseName,
                'stage'          => $stage,
                'stage_label'    => $formattedStage,
                'visual_cues'    => $visualCues,
                'is_waterborne'  => $isWaterborne,
                'updated_at'     => date('Y-m-d H:i:s')
            ];
            $state['updated_at'] = date('Y-m-d H:i:s');
            file_put_contents($stateFile, json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        }
    }
}

