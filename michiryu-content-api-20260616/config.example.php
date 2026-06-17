<?php
/**
 * Copy this file to config.php and edit values on the hosted server.
 */

return array(
    'base_url' => 'https://example.com/michiryu-content-api',
    'default_library' => 'basic',

    // Global file serving guardrails. Individual libraries may override these.
    'allowed_extensions' => array('json', 'jpg', 'jpeg', 'png', 'svg', 'webp', 'pdf', 'md', 'txt'),
    'max_file_bytes' => 26214400,

    'libraries' => array(
        'basic' => array(
            'library' => 'michiryu-basic',
            'version' => '2026.06.16',
            'license' => 'MichiRyu Content License',
            'content_root' => '/absolute/path/to/michiryu-content/basic',

            // Required before manifest or file routes will serve this library.
            'token_hash' => '',

            // Explicit manifest allow-list.
            'manifests' => array(
                'default' => array(
                    'featured_content' => 'featured-content.json',
                    'images' => 'images.json',
                ),
            ),
        ),

        // Future premium content should use its own root and its own token
        // validation strategy. Leave this disabled until entitlement validation
        // exists server-side.
        'premium' => array(
            'library' => 'michiryu-premium',
            'version' => '2026.06.16',
            'license' => 'MichiRyu Premium Content License',
            'content_root' => '/absolute/path/to/michiryu-content/premium',
            'token_hash' => '',
            'manifests' => array(
                'default' => array(
                    'featured_content' => 'featured-content.json',
                    'images' => 'images.json',
                ),
            ),
        ),
    ),
);
