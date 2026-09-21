<?php
// SunHelm Needs & Diseases Bridge - prompt injection hook.
// Executes before the system prompt is compiled and frozen.
// Injects the player's physical condition and visible disease symptoms into $GLOBALS["COMMAND_PROMPT"].

$stateFile    = __DIR__ . "/state.json";
$settingsFile = __DIR__ . "/settings.json";

if (!file_exists($stateFile)) return;
$state = json_decode(file_get_contents($stateFile), true);
if (!$state || !is_array($state)) return;

$settings = file_exists($settingsFile) ? json_decode(file_get_contents($settingsFile), true) : [];

$injections = [];

// ====================================================================
// 1. Physical Needs (Hunger, Thirst, Fatigue, Cold)
// ====================================================================
$minStage = (int)($settings["min_stage"] ?? 1);
$needKeys = [
    ["key" => "hunger",  "labelKey" => "hunger_label",  "default" => "well fed"],
    ["key" => "thirst",  "labelKey" => "thirst_label",  "default" => "hydrated"],
    ["key" => "fatigue", "labelKey" => "fatigue_label", "default" => "rested"],
    ["key" => "cold",    "labelKey" => "cold_label",    "default" => "comfortable"],
];

$needs = (isset($state["player_needs"]) && is_array($state["player_needs"]))
    ? $state["player_needs"]
    : [];

$conds = [];
foreach ($needKeys as $nk) {
    $stage = (int)($needs[$nk["key"] . "_stage"] ?? 0);
    if (($settings["track_" . $nk["key"]] ?? true) && $stage >= $minStage && !empty($needs[$nk["labelKey"]])) {
        $conds[] = $needs[$nk["labelKey"]];
    }
}

$joinList = function ($items) {
    $n = count($items);
    if ($n === 0) return "";
    if ($n === 1) return $items[0];
    $last = array_pop($items);
    return implode(", ", $items) . (count($items) > 0 ? ", and " : "") . $last;
};

if (!empty($conds)) {
    $playerCondStr = $joinList($conds);
    $playerTemplate = $settings["prompt_template"]
        ?? "Player physical condition: The player is {conditions}. Notice their physical state when appropriate and react naturally.";
    $injections[] = str_replace("{conditions}", $playerCondStr, $playerTemplate);
}

// ====================================================================
// 2. Diseases & Medical Observation (Two-Tier Knowledge)
// ====================================================================
if (!empty($settings["track_diseases"])) {
    $diseaseData = (isset($state["player_disease"]) && is_array($state["player_disease"]))
        ? $state["player_disease"]
        : null;

    if ($diseaseData && !empty($diseaseData["has_disease"])) {
        $stage       = (int)($diseaseData["stage"] ?? 0);
        $minDisStage = (int)($settings["min_disease_stage"] ?? 2);
        $dName       = trim($diseaseData["disease_name"] ?? '');
        $stageLabel  = trim($diseaseData["stage_label"] ?? $dName);
        $rawCues     = trim($diseaseData["visual_cues"] ?? '');
        $isWaterborne = (!empty($diseaseData["is_waterborne"]) || strpos($rawCues, 'waterborne') !== false);

        // Modular Toggle 1: Water-Borne Diseases (SunHelmDirtyWater.esp)
        $enableWaterborne = $settings["mod_waterborne_diseases"] ?? true;
        if ($isWaterborne && !$enableWaterborne) {
            // Water-borne illness tracking toggled off; ignore dirty water contractions
            $stage = 0;
        }

        // Modular Toggle 2: SunHelm Diseases Progression (SunHelmDiseases.esp)
        $enableStageProgression = $settings["mod_sunhelm_diseases"] ?? true;
        if (!$enableStageProgression && $stage > 0) {
            // Flatten to standard single-stage illness without acute/severe progression
            $stage = 1;
            $stageLabel = $dName;
        }

        // Only inject if disease stage meets threshold (or if food poisoning / single-stage)
        $meetsThreshold = $enableStageProgression 
            ? ($stage >= $minDisStage || strcasecmp($dName, 'Food Poisoning') === 0)
            : ($stage >= 1);

        if ($stage > 0 && $meetsThreshold) {
            
            // Modular Toggle 3: Immersive Diseases 2.0 (Immersive Diseases.esp)
            $enableImmersive = $settings["mod_immersive_diseases"] ?? true;
            if ($enableImmersive) {
                // Map raw cue flags to evocative physical descriptions
                $cueMap = [
                    'locked_joints'   => 'unnatural crooked locked elbows and rigid joint posture',
                    'limp_arm'        => 'left arm hanging loose and flaccid at their side',
                    'violent_cough'   => 'paroxysmal coughing fits with audible bronchial rattling in the chest',
                    'purple_veins'    => 'prominent dark purple veins bulging beneath the skin',
                    'boils'           => 'clusters of burning raised boils across their limbs',
                    'blotches'        => 'raw, reddish-purple epidermal blotches across their skin',
                    'headache'        => 'frequently clutching their temples in acute cranial distress',
                    'necrotic_skin'   => 'decayed, necrotic dark lesions marking their face and neck',
                    'stomach_cramps'  => 'doubled over clutching their abdomen in violent nausea and cramps',
                    'chills_shiver'   => 'uncontrollable teeth-chattering rigors and icy skin pallor',
                    'tremors'         => 'violent involuntary kinetic tremors shaking their hands'
                ];

                $visualDescriptions = [];
                foreach (explode(',', $rawCues) as $cue) {
                    $cue = trim($cue);
                    if ($cue !== 'waterborne' && isset($cueMap[$cue])) {
                        $visualDescriptions[] = $cueMap[$cue];
                    }
                }
                $visualText = !empty($visualDescriptions)
                    ? implode('; ', $visualDescriptions)
                    : 'visible signs of physical distress and exhaustion';
            } else {
                // Immersive Diseases disabled: generic non-cosmetic malaise
                $visualText = 'visible signs of illness and physical fatigue';
            }

            // Determine if current speaking NPC is a medical/scholarly expert
            $speakerName = trim((string)($GLOBALS["HERIKA_NAME"] ?? ''));
            $expertNames = [
                'arcadia', 'danica pure-spring', 'danica', 'farengar secret-fire', 'farengar',
                'nurelion', 'elgrim', 'colette mence', 'colette', 'runil', 'bothela',
                'milore ienth', 'elynea mothren', 'teldryn sero', 'marcurio', 'urag gro-shub'
            ];

            $knowledgeTags = [];
            if (!empty($GLOBALS["OGHMA_KNOWLEDGE"])) {
                $knowledgeTags = is_array($GLOBALS["OGHMA_KNOWLEDGE"])
                    ? $GLOBALS["OGHMA_KNOWLEDGE"]
                    : array_map('trim', explode(',', strtolower((string)$GLOBALS["OGHMA_KNOWLEDGE"])));
            }

            $expertTags = ['healer', 'alchemist', 'scholar', 'mage', 'priest', 'college_of_winterhold', 'witch'];
            $isExpert = in_array(strtolower($speakerName), $expertNames, true)
                || !empty(array_intersect($expertTags, $knowledgeTags));

            // Clinical mappings for experts
            $clinicalInfo = [
                'Rockjoint' => [
                    'name'      => 'Tetanus',
                    'pathology' => 'acute synovial calcification and tendon contracture from predator saliva',
                    'cure'      => 'Mudcrab Chitin and Hawk Feathers'
                ],
                'Bone Break Fever' => [
                    'name'      => 'Breakbone Fever',
                    'pathology' => 'severe periosteal inflammation of the skeletal sheath',
                    'cure'      => 'Hawk Feathers and Vampire Dust'
                ],
                'Brain Rot' => [
                    'name'      => 'Meningitis',
                    'pathology' => 'acute meningeal inflammation causing intracranial pressure',
                    'cure'      => 'Restoration cleansing or Lavender and Vampire Dust'
                ],
                'Rattles' => [
                    'name'      => 'Pertussis',
                    'pathology' => 'bronchial ulceration from subterranean spores and cave dust',
                    'cure'      => 'vaporized Hawk Feather tinctures or Shrine of Kynareth'
                ],
                'Ataxia' => [
                    'name'      => 'Ataxia',
                    'pathology' => 'spinal motor neuropathy with loss of proprioception',
                    'cure'      => 'Mudcrab Chitin and Hawk Feathers'
                ],
                'Witbane' => [
                    'name'      => 'Ergotism',
                    'pathology' => 'cerebral neurovascular vasoconstriction choking magicka flow',
                    'cure'      => 'Elixir of Cure Disease or Shrine of Julianos'
                ],
                'Dampworm' => [
                    'name'      => 'Dracunculiasis',
                    'pathology' => 'parasitic water-worm larvae maturing in leg muscle fascias from unboiled water',
                    'cure'      => 'Hawk Feathers and alchemical vermifuges'
                ],
                'Swamp Fever' => [
                    'name'      => 'The Ague',
                    'pathology' => 'visceral spleen and lumbar musculature inflammation',
                    'cure'      => 'Mudcrab Chitin and Charred Skeever Hide'
                ],
                'Chills' => [
                    'name'      => 'Hypothermic Rigors',
                    'pathology' => 'cryo-vascular thrombosis halting cellular tissue regeneration',
                    'cure'      => 'blood-warming alchemical draughts and Shrine of Mara'
                ],
                'Feeble Limb' => [
                    'name'      => 'Necrotizing Fasciitis',
                    'pathology' => 'putrefactive liquefaction of deep muscle fascias destroying structural block tension',
                    'cure'      => 'potent alchemical antiseptics and restorative magic'
                ],
                'Shakes' => [
                    'name'      => 'Rat-Bite Fever',
                    'pathology' => 'rodent neurotoxins inducing violent kinetic intention tremors',
                    'cure'      => 'Charred Skeever Hide and Hawk Feathers'
                ],
                'Wither' => [
                    'name'      => 'Cutaneous Atrophy',
                    'pathology' => 'dermal collagen breakdown leaving skin translucent and fragile',
                    'cure'      => 'Hawk Feathers and restorative skin salves'
                ],
                'Droops' => [
                    'name'      => 'Ash-Palsy (Myasthenia)',
                    'pathology' => 'vitrified volcanic ash particulates blocking neuromuscular exertion',
                    'cure'      => 'ash-salves and Cyrodilic Cure Disease elixirs'
                ],
                'Astral Vapors' => [
                    'name'      => 'Miasmatic Sickness',
                    'pathology' => 'ancient crypt miasma decoupling spiritual resonance and draining magicka',
                    'cure'      => 'concentrated Void Salts and Shrine of Arkay'
                ],
                'Food Poisoning' => [
                    'name'      => 'Trichinosis',
                    'pathology' => 'enteric toxicosis and parasitic muscle cysts from raw predator meat',
                    'cure'      => 'Charcoal, Charred Skeever Hide, or digestive purgatives'
                ]
            ];

            // Modular Toggle 4: Two-Tier Oghma Clinical Lore System
            $enableClinicalLore = $settings["mod_oghma_clinical_lore"] ?? true;

            if ($enableClinicalLore && $isExpert && isset($clinicalInfo[$dName])) {
                $c = $clinicalInfo[$dName];
                $knowledgeContext = "As a trained apothecary, healer, or scholar, you diagnose this as {$c['name']} (known to commoners as {$dName}), caused by {$c['pathology']}. You know it requires {$c['cure']} to cure.";
            } else {
                $knowledgeContext = "You recognize this as the common affliction known as {$dName}. Sufferers are in visible pain, and folk wisdom advises visiting an apothecary or praying at a divine shrine.";
            }

            // Urgency Directive based on stage
            if ($enableStageProgression && $stage >= 3) {
                $urgency = "CRITICAL MEDICAL EMERGENCY: The player is in late-stage agony and physical collapse. Your dialogue MUST reflect alarm, urging immediate cessation of travel and treatment.";
            } elseif ($enableStageProgression && $stage == 2) {
                $urgency = "ACUTE CONCERN: The player's symptoms are conspicuous and impairing. Express concern or caution about contagion naturally in your reply.";
            } else {
                $urgency = "MILD SYMPTOM: The condition is subtle. Remark on their tired or unwell appearance if the topic fits the moment.";
            }

            $diseaseTemplate = $settings["disease_prompt_template"]
                ?? "Health Observation: You visibly notice the player exhibiting {visual_cues}. {knowledge_context}";

            // If template uses placeholders, substitute them; otherwise use default formatting
            if (strpos($diseaseTemplate, '{visual_cues}') !== false || strpos($diseaseTemplate, '{disease_stage}') !== false) {
                $diseaseInjection = str_replace(
                    ['{disease_stage}', '{visual_cues}', '{knowledge_context}'],
                    [$stageLabel, $visualText, $knowledgeContext . " [Behavior Directive: {$urgency}]"],
                    $diseaseTemplate
                );
            } else {
                $diseaseInjection = "Health Observation: You visibly notice the player exhibiting {$visualText}. {$knowledgeContext} [Behavior Directive: {$urgency}]";
            }

            $injections[] = $diseaseInjection;
        }
    }
}

// ====================================================================
// 3. Inject Combined Context into COMMAND_PROMPT
// ====================================================================
if (!empty($injections)) {
    $fullPayload = implode("\n\n", $injections);
    if (!empty($GLOBALS["COMMAND_PROMPT"])) {
        $GLOBALS["COMMAND_PROMPT"] .= "\n\n" . $fullPayload;
    } else {
        $GLOBALS["COMMAND_PROMPT"] = $fullPayload;
    }
}
