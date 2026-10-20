<?php

/*
 * Hazelchat for Statamic. Every value can also come from .env, so a site
 * without the Control Panel settings page (Statamic 5) or with settings in
 * Git works the same way.
 */

return [

    // The installation code from Hazelchat, or just its 64-character key.
    // On Statamic 6 the code saved under Addons → Hazelchat wins.
    'installation_code' => env('HAZELCHAT_INSTALLATION_CODE', ''),

    // Start the chat only after your consent manager allows it
    // (adds data-require-consent="true").
    'wait_for_consent' => env('HAZELCHAT_WAIT_FOR_CONSENT', false),

    // Put the chat before </body> of every front-end page. Set to false if
    // your templates place {{ hazelchat }} themselves on chosen pages.
    'auto_inject' => env('HAZELCHAT_AUTO_INJECT', true),

];
