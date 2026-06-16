<?php
/**
 * Example config for MichiRyu Content API.
 *
 * Copy to config.php on the server and fill in deployment values.
 * Do not commit real secrets.
 */

return array(
    'library' => 'michiryu-basic',
    'version' => '2026.06.16',
    'license' => 'MichiRyu Content License',
    'base_url' => 'https://www.bowerr31x.com/michiryu-content-api',
    'content_root' => '/home1/bowerrx1/public_html/michiryu-content',

    // Leave empty for public testing. For a soft gate, set this to hash('sha256', '<token>').
    // Premium content should use server-side user/license validation instead.
    'basic_token_hash' => '',
);
