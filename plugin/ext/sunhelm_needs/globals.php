<?php
/**
 * Runs early, before the request is processed, which is where CHIM expects prompt contributions to
 * be registered. Registration only declares intent - the callbacks are invoked later, per actor and
 * per turn - so this stays cheap even though it runs on every request.
 *
 * The player side of this extension predates CHIM's registration API and still injects directly in
 * context_pre.php. That is left alone deliberately: it works, and rewriting a working prompt path
 * is a change with no upside and a real chance of quietly altering how existing saves behave.
 */

require_once __DIR__ . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'sunhelm_followers.php';

if (function_exists('sunhelmFollowersRegisterPromptHooks')) {
    sunhelmFollowersRegisterPromptHooks();
}
