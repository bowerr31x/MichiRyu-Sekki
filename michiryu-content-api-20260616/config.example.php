<?php
/**
 * Copy this file to config.php and edit values on the hosted server.
 */

return array(
    'library' => 'michiryu-basic',
    'version' => '2026.06.16',
    'license' => 'MichiRyu Content License',
    'base_url' => 'https://www.bowerr31x.com/michiryu-content-api',
    'content_root' => '/home1/bowerrx1/public_html/michiryu-content',

    // Leave empty only during initial setup. Use a SHA-256 token hash for basic access.
    'basic_token_hash' => '',

    // File serving guardrails.
    'allowed_extensions' => array('json', 'jpg', 'jpeg', 'png', 'svg', 'webp', 'pdf', 'md', 'txt'),
    'max_file_bytes' => 26214400,
);
