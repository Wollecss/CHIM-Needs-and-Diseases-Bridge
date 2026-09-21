<?php
/**
 * Follower state for the SunHelm CHIM bridge.
 *
 * The rest of this extension describes the *player*: one subject, one set of needs, injected into
 * every NPC's context so they can react to you. Followers are the opposite shape - many subjects,
 * each of whom is sometimes the one speaking - so they are handled per actor and keyed by name.
 *
 * Data arrives from the SunHelm - Individual Follower Needs SKSE plugin, which is the only thing
 * that knows a companion's hunger, thirst, drink count and illness. Nothing here talks to the game;
 * it parses what the bridge script forwards and answers questions about it when CHIM asks.
 *
 * Prompt delivery uses CHIM's registration API where it exists and falls back to appending to
 * HERIKA_PERS where it doesn't, so this works on older servers as well as current ones.
 */

const SUNHELM_FOLLOWER_PLUGIN_ID = 'sunhelm_needs.followers';

function sunhelmFollowersStatePath(): string
{
    return dirname(__DIR__) . DIRECTORY_SEPARATOR . 'state.json';
}

function sunhelmFollowersSettingsPath(): string
{
    return dirname(__DIR__) . DIRECTORY_SEPARATOR . 'settings.json';
}

function sunhelmFollowersDefaultSettings(): array
{
    return [
        // Master switch for everything in this file. Named mod_* to match the other optional-mod
        // toggles in the WebUI, because that is what this is: support for a mod you may not have.
        // Off leaves the player side untouched.
        'mod_sunhelm_followers' => true,
        // Standing line on the follower's own profile: what they are, always available to the model.
        'followers_profile_line' => true,
        // Per-turn nudge to actually bring it up. Separate from the profile line because one is
        // knowledge and the other is behaviour, and people want different amounts of each.
        'followers_turn_instruction' => true,
        // Chance a follower mentions their condition when you talk to them. The single most
        // important dial here: at 100 they will not shut up about being hungry.
        'followers_mention_chance' => 35,
        // Below this stage nothing is reported at all. Peckish is not worth a line of dialogue.
        'followers_min_stage' => 3,
        // Individual sources, so anyone can keep the parts they like.
        'followers_report_needs' => true,
        'followers_report_drunk' => true,
        'followers_report_disease' => true,
        // Let other NPCs notice a visibly drunk or ill companion, not just the follower themselves.
        'followers_observable_to_others' => true,
    ];
}

function sunhelmFollowersSettings(): array
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }

    $settings = sunhelmFollowersDefaultSettings();
    $path = sunhelmFollowersSettingsPath();
    if (is_file($path)) {
        $raw = json_decode((string) file_get_contents($path), true);
        if (is_array($raw)) {
            foreach ($settings as $key => $default) {
                if (!array_key_exists($key, $raw)) {
                    continue;
                }
                $settings[$key] = is_int($default)
                    ? max(0, min(100, (int) $raw[$key]))
                    : (bool) $raw[$key];
            }
        }
    }

    $cache = $settings;
    return $cache;
}

function sunhelmFollowersIsEnabled(): bool
{
    $settings = sunhelmFollowersSettings();
    return !empty($settings['mod_sunhelm_followers']);
}

function sunhelmFollowersReadState(): array
{
    $path = sunhelmFollowersStatePath();
    if (!is_file($path)) {
        return [];
    }
    $state = json_decode((string) file_get_contents($path), true);
    return is_array($state) ? $state : [];
}

/**
 * Writes through a temporary file and renames it. preprocessing.php writes this same file for the
 * player's needs and diseases, and a half-written state.json read mid-request would take the whole
 * extension down rather than degrade it.
 */
function sunhelmFollowersWriteState(array $state): void
{
    $path = sunhelmFollowersStatePath();
    $tmp = $path . '.tmp';
    $json = json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    if ($json === false) {
        return;
    }
    if (file_put_contents($tmp, $json, LOCK_EX) !== false) {
        @rename($tmp, $path);
    }
}

function sunhelmFollowersStageLabel(string $need, int $stage): string
{
    $labels = [
        'hunger' => ['Well Fed', 'Satisfied', 'Peckish', 'Hungry', 'Ravenous', 'Starving'],
        'thirst' => ['Quenched', 'Sated', 'Thirsty', 'Parched', 'Dehydrated', 'Severely Dehydrated'],
    ];
    return $labels[$need][$stage] ?? 'Unknown';
}

/**
 * Records one follower. Called from preprocessing when a payload arrives.
 */
function sunhelmFollowersUpsert(string $name, array $fields): void
{
    $name = trim($name);
    if ($name === '') {
        return;
    }

    $state = sunhelmFollowersReadState();
    if (!isset($state['followers']) || !is_array($state['followers'])) {
        $state['followers'] = [];
    }

    // Stored as a name-keyed map rather than the list the schema originally sketched: every read is
    // "what is this one actor doing", never "iterate the party", and a list would mean scanning.
    $state['followers'][$name] = array_merge($fields, [
        'name' => $name,
        'updated_at' => date('Y-m-d H:i:s'),
    ]);
    $state['updated_at'] = date('Y-m-d H:i:s');
    sunhelmFollowersWriteState($state);
}

function sunhelmFollowersForget(string $name): void
{
    $name = trim($name);
    if ($name === '') {
        return;
    }
    $state = sunhelmFollowersReadState();
    if (!isset($state['followers'][$name])) {
        return;
    }
    unset($state['followers'][$name]);
    $state['updated_at'] = date('Y-m-d H:i:s');
    sunhelmFollowersWriteState($state);
}

function sunhelmFollowersGet(string $name): array
{
    $name = trim($name);
    if ($name === '') {
        return [];
    }
    $state = sunhelmFollowersReadState();
    $followers = $state['followers'] ?? [];
    if (!is_array($followers)) {
        return [];
    }
    if (isset($followers[$name]) && is_array($followers[$name])) {
        return $followers[$name];
    }
    // Names reaching CHIM don't always match the case the game sent.
    foreach ($followers as $key => $entry) {
        if (is_array($entry) && strcasecmp((string) $key, $name) === 0) {
            return $entry;
        }
    }
    return [];
}

/**
 * How drunk, in words. Graded rather than binary because the interesting part of a drunk companion
 * is the slope - tipsy and hammered should not read the same.
 */
function sunhelmFollowersDrunkPhrase(array $follower): string
{
    $drinks = (int) ($follower['drinks'] ?? 0);
    if (empty($follower['drunk'])) {
        return $drinks >= 2 ? 'a drink or two in' : '';
    }
    if ($drinks >= 6) {
        return 'thoroughly drunk';
    }
    if ($drinks >= 4) {
        return 'drunk';
    }
    return 'tipsy';
}

/**
 * The worst stage a follower is carrying, 0-5. Illness counts as pressing but not desperate: it is
 * unpleasant and visible, and unlike hunger it does not get worse on its own here.
 */
function sunhelmFollowersSeverity(array $follower): int
{
    $worst = max((int) ($follower['hunger'] ?? 0), (int) ($follower['thirst'] ?? 0));
    if (!empty($follower['diseased'])) {
        $worst = max($worst, 3);
    }
    return $worst;
}

/**
 * How hard to push, by stage. Without this every stage read the same to the model and a follower
 * four fifths of the way to collapsing from thirst answered as mildly as one who merely fancied a
 * drink - the difference between "I'm a touch parched" and a companion who actually needs help.
 */
function sunhelmFollowersUrgencyClause(int $severity): string
{
    // The "do not brush it aside" half is not padding. Told only to raise the subject, a follower
    // would raise it and then immediately talk himself out of it - "I could do with some stew,
    // but you look worse than I do, the hunger can wait" - because the player's own condition is
    // in the same prompt and gives him a noble reason to defer. Stating the need is not the same
    // as pressing it, and only the second one reads like someone who has not eaten all day.
    if ($severity >= 5) {
        return 'This is desperate and you can barely think past it. Say so plainly, without dignity '
            . 'if need be, and ask for help outright. Do not make light of it, wave it away, or put '
            . 'anyone else\'s troubles ahead of it - you are past that.';
    }
    if ($severity >= 4) {
        return 'This is pressing and it is wearing on you. Raise it directly and ask for what you '
            . 'need. Do not understate it, joke it off, or say it can wait - it cannot, and '
            . 'whatever else is going on does not change that.';
    }
    return 'It is an irritation rather than an emergency; mention it in passing.';
}

/**
 * Joins fragments the way a person would: "a, b and c".
 */
function sunhelmFollowersJoin(array $parts): string
{
    if (count($parts) <= 1) {
        return (string) ($parts[0] ?? '');
    }
    $last = array_pop($parts);
    return implode(', ', $parts) . ' and ' . $last;
}

/**
 * The one-line summary used for both the profile and the turn nudge. Returns '' when there is
 * genuinely nothing worth saying, which is most of the time and is the point.
 */
function sunhelmFollowersDescribe(array $follower, bool $observableOnly = false): string
{
    if (!$follower) {
        return '';
    }
    $settings = sunhelmFollowersSettings();
    $minStage = (int) $settings['followers_min_stage'];
    $parts = [];

    // Hunger and thirst are private - you can't see that someone is thirsty - so they're skipped
    // when describing a follower to somebody else.
    if (!$observableOnly && !empty($settings['followers_report_needs'])) {
        $hunger = (int) ($follower['hunger'] ?? 0);
        $thirst = (int) ($follower['thirst'] ?? 0);
        if ($hunger >= $minStage) {
            $parts[] = strtolower(sunhelmFollowersStageLabel('hunger', $hunger));
        }
        if ($thirst >= $minStage) {
            $parts[] = strtolower(sunhelmFollowersStageLabel('thirst', $thirst));
        }
    }

    if (!empty($settings['followers_report_drunk'])) {
        $drunk = sunhelmFollowersDrunkPhrase($follower);
        if ($drunk !== '') {
            $parts[] = $drunk;
        }
    }

    if (!empty($settings['followers_report_disease']) && !empty($follower['diseased'])) {
        $parts[] = 'ill';
    }

    // Every fragment is a bare complement - "ravenous", "drunk", "ill" - with no verb of its own,
    // so the caller can put "is" or "are" in front of it. An earlier version baked "is" into each
    // fragment and produced "You is ravenous, is drunk" the moment it was used in second person.
    return sunhelmFollowersJoin($parts);
}

/**
 * Enricher callback. CHIM calls this for whichever actor it is assembling a profile for, so the
 * name lookup is also the "is this one of ours" test.
 */
function sunhelmFollowersActorProfileLine($actorName, $actorType = '', array $context = [])
{
    if (!sunhelmFollowersIsEnabled()) {
        return '';
    }
    $settings = sunhelmFollowersSettings();
    if (empty($settings['followers_profile_line'])) {
        return '';
    }

    $follower = sunhelmFollowersGet((string) $actorName);
    if (!$follower) {
        return '';
    }

    $description = sunhelmFollowersDescribe($follower);
    return $description === '' ? '' : 'Condition: ' . ucfirst($description) . '.';
}

function sunhelmFollowersCurrentNpcName(): string
{
    $name = trim((string) ($GLOBALS['HERIKA_NAME'] ?? ''));
    if ($name === '' || strcasecmp($name, 'The Narrator') === 0) {
        return '';
    }
    return $name;
}

function sunhelmFollowersRequestType(): string
{
    $request = $GLOBALS['gameRequest'] ?? null;
    if (is_array($request)) {
        return strtolower(trim((string) ($request[0] ?? '')));
    }
    if (is_string($request) && $request !== '') {
        $parts = explode('|', $request, 2);
        return strtolower(trim($parts[0]));
    }
    return '';
}

function sunhelmFollowersIsPlayerTalkRequest(string $type): bool
{
    if ($type === '') {
        return false;
    }
    if (strpos($type, 'inputtext') === 0) {
        return true;
    }
    return in_array($type, ['talk', 'dialogue', 'playerinput', 'chatinput'], true);
}

function sunhelmFollowersIsBoredRequest(string $type): bool
{
    return strpos($type, 'bored') === 0;
}

/**
 * The per-turn nudge. A profile line tells the model what is true; this decides whether it should
 * do anything about it right now, which is a different question and the one that governs whether a
 * follower is characterful or exhausting.
 */
function sunhelmFollowersTurnInstruction(): string
{
    if (!sunhelmFollowersIsEnabled()) {
        return '';
    }
    $settings = sunhelmFollowersSettings();
    if (empty($settings['followers_turn_instruction'])) {
        return '';
    }

    $npc = sunhelmFollowersCurrentNpcName();
    if ($npc === '') {
        return '';
    }

    $follower = sunhelmFollowersGet($npc);
    if (!$follower) {
        return '';
    }

    $description = sunhelmFollowersDescribe($follower);
    if ($description === '') {
        return '';
    }

    $type = sunhelmFollowersRequestType();
    $drunk = !empty($follower['drunk']);
    $severity = sunhelmFollowersSeverity($follower);
    $urgency = sunhelmFollowersUrgencyClause($severity);

    // Idle chatter is the natural home for this: nobody minds a companion muttering about being
    // hungry when they had nothing else to say.
    if (sunhelmFollowersIsBoredRequest($type)) {
        return "This is a quiet moment. You are {$description}. {$urgency} Let that colour this "
            . "idle line in character. Do not mention mods, meters, stages or game menus.";
    }

    if (sunhelmFollowersIsPlayerTalkRequest($type)) {
        // Drunkenness changes how someone talks rather than what they report, so it is applied
        // every turn instead of being rationed - rolling for it would produce a companion who
        // sobers up mid-conversation.
        if ($drunk) {
            return "You are {$description}. Speak accordingly: looser, bolder, more familiar, less "
                . "careful about what you admit. Do not slur phonetically or mention mods, meters "
                . "or game menus.";
        }

        // The roll is for a follower who is merely uncomfortable. Someone at stage 4 or 5 is not
        // choosing whether to mention it, so the chance climbs steeply and stage 5 always speaks:
        // rationing a starving companion into silence is the one case where the anti-nag guard
        // produces worse behaviour than none at all.
        $chance = (int) $settings['followers_mention_chance'];
        if ($severity >= 5) {
            $chance = 100;
        } elseif ($severity >= 4) {
            $chance = min(100, $chance + 40);
        }
        if ($chance <= 0) {
            return '';
        }
        if ($chance < 100 && random_int(1, 100) > $chance) {
            return '';
        }
        // No "keep it short" any more: it fought the urgency clause, telling a starving follower
        // to be desperate and brief in the same breath.
        return "You are {$description}. {$urgency} You may ask the player for food, drink or coin. "
            . "Stay in character and do not mention mods, meters, stages or game menus.";
    }

    return '';
}

/**
 * What a *different* NPC can see. An innkeeper can tell a companion is drunk; they cannot tell the
 * companion is thirsty. Kept deliberately thin - this fires for every nearby follower, so it is the
 * line most likely to bloat a prompt.
 */
function sunhelmFollowersObservableInjection(): string
{
    if (!sunhelmFollowersIsEnabled()) {
        return '';
    }
    $settings = sunhelmFollowersSettings();
    if (empty($settings['followers_observable_to_others'])) {
        return '';
    }

    $speaker = sunhelmFollowersCurrentNpcName();
    $state = sunhelmFollowersReadState();
    $followers = $state['followers'] ?? [];
    if (!is_array($followers) || !$followers) {
        return '';
    }

    $notes = [];
    foreach ($followers as $name => $follower) {
        if (!is_array($follower) || strcasecmp((string) $name, $speaker) === 0) {
            continue;  // They describe themselves through their own profile line.
        }
        $visible = sunhelmFollowersDescribe($follower, true);
        if ($visible !== '') {
            $notes[] = ucfirst((string) $name) . ' is ' . $visible;
        }
    }

    if (!$notes) {
        return '';
    }
    return "Travelling with the player: " . implode('. ', $notes)
        . ". You can see this much; react only if it is natural to.";
}

function sunhelmFollowersPromptInstructions(): string
{
    if (!sunhelmFollowersIsEnabled()) {
        return '';
    }

    return trim(<<<'TXT'
A companion's own condition - hunger, thirst, drink or illness - is theirs to feel, not the player's
to be told about. Treat it as mood and motivation rather than a status report: it shapes how they
speak, what they notice, and what they ask for. Never state a stage, a number, or a mod name.
TXT);
}

function sunhelmFollowersRegisterPromptHooks(): void
{
    if (!sunhelmFollowersIsEnabled()) {
        return;
    }

    // Feature-detected rather than assumed: this extension is installed on servers older than the
    // registration API, and silently losing follower context there would be worse than not using it.
    if (function_exists('chimRegisterActorProfileEnricher')) {
        chimRegisterActorProfileEnricher(
            SUNHELM_FOLLOWER_PLUGIN_ID . '.condition',
            'sunhelmFollowersActorProfileLine',
            60
        );
    }

    if (function_exists('chimRegisterPromptInjection')) {
        $instructions = sunhelmFollowersPromptInstructions();
        if ($instructions !== '') {
            chimRegisterPromptInjection(
                'prompt_bottom',
                SUNHELM_FOLLOWER_PLUGIN_ID . '.instructions',
                $instructions,
                80
            );
        }
    }
}

/**
 * Payload contract, sent per follower by the bridge script:
 *
 *   sunhelm_follower@<Name>@<hunger 0-5>@<thirst 0-5>@<drunk 0|1>@<drinks>@<diseased 0|1>@<gold>
 *   sunhelm_follower_gone@<Name>
 *
 * Positional to match the existing sunhelm_needs@ payload. New fields append to the end, so an
 * older server reading a newer payload ignores what it doesn't know rather than failing.
 */
function sunhelmFollowersParsePayload(string $message): bool
{
    $message = trim($message);

    if (strpos($message, 'sunhelm_follower_gone@') === 0) {
        $parts = explode('@', $message);
        if (isset($parts[1])) {
            sunhelmFollowersForget($parts[1]);
            return true;
        }
        return false;
    }

    if (strpos($message, 'sunhelm_follower@') !== 0) {
        return false;
    }

    $parts = explode('@', $message);
    if (count($parts) < 8) {
        return false;
    }

    sunhelmFollowersUpsert($parts[1], [
        'hunger' => max(0, min(5, (int) $parts[2])),
        'hunger_label' => sunhelmFollowersStageLabel('hunger', max(0, min(5, (int) $parts[2]))),
        'thirst' => max(0, min(5, (int) $parts[3])),
        'thirst_label' => sunhelmFollowersStageLabel('thirst', max(0, min(5, (int) $parts[3]))),
        'drunk' => ((int) $parts[4]) === 1,
        'drinks' => max(0, (int) $parts[5]),
        'diseased' => ((int) $parts[6]) === 1,
        'gold' => max(0, (int) $parts[7]),
    ]);
    return true;
}

function sunhelmFollowersIngestCurrentRequest(string $message): bool
{
    if (!sunhelmFollowersIsEnabled()) {
        return false;
    }
    if (strpos($message, 'sunhelm_follower') !== 0) {
        return false;
    }
    return sunhelmFollowersParsePayload($message);
}
