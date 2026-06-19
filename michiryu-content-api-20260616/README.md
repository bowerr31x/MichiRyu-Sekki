# MichiRyu Content API

This package is server-side content infrastructure. It is not part of the
public GPL WordPress plugin.

## Deploy

1. Upload this folder as:

```text
public_html/your-site/michiryu-content-api/
```

2. Copy `config.example.php` to `config.php` on the server.
3. Set `base_url` to the public API folder URL.
4. Configure the content libraries. The recommended private content-root layout is:

```text
/absolute/path/to/michiryu-content-libraries/
  basic/
  custom/
  premium/
```

Configure at least `basic`; configure `custom` when you want the plugin's
Custom Content Library import to test a distinct hosted source:

```php
'libraries' => array(
    'basic' => array(
        'library' => 'michiryu-basic',
        'version' => '2026.06.16',
        'license' => 'MichiRyu Content License',
        'content_root' => '/absolute/path/to/michiryu-content-libraries/basic',
        'token_hash' => hash('sha256', 'your-basic-token'),
        'manifests' => array(
            'default' => array(
                'featured_content' => 'featured-content.json',
                'images' => 'images.json',
            ),
        ),
    ),
    'custom' => array(
        'library' => 'michiryu-custom',
        'version' => '2026.06.16',
        'license' => 'MichiRyu Content License',
        'content_root' => '/absolute/path/to/michiryu-content-libraries/custom',
        'token_hash' => hash('sha256', 'your-custom-token'),
        'manifests' => array(
            'default' => array(
                'featured_content' => 'featured-content.json',
                'images' => 'images.json',
            ),
        ),
    ),
    'premium' => array(
        'library' => 'michiryu-premium',
        'version' => '2026.06.16',
        'license' => 'MichiRyu Premium Content License',
        'content_root' => '/absolute/path/to/michiryu-content-libraries/premium',
        'token_hash' => hash('sha256', 'your-premium-token'),
        'manifests' => array(
            'default' => array(
                'featured_content' => 'featured-content.json',
                'images' => 'images.json',
            ),
        ),
    ),
),
```

The API still accepts the older single-library config shape for transition, but
new deployments should use `libraries`.

## Bluehost-Friendly Test URLs

Use explicit URLs first because they do not depend on clean URL rewriting:

```text
https://michiryu.com/michiryu-content-api/index.php?route=health
https://michiryu.com/michiryu-content-api/index.php?route=manifest
https://michiryu.com/michiryu-content-api/index.php?route=file&path=featured-content.json
https://michiryu.com/michiryu-content-api/index.php?route=file&path=images/map/yuki-no-sato-sekki-map.jpg
```

The default library is used when no `library` parameter is supplied. You may
also request a specific configured library:

```text
https://michiryu.com/michiryu-content-api/index.php?route=manifest&library=basic
https://michiryu.com/michiryu-content-api/index.php?route=manifest&library=custom
https://michiryu.com/michiryu-content-api/index.php?route=manifest&library=premium
```

If `.htaccess` rewriting is working, these shorter URLs should also work:

```text
https://michiryu.com/michiryu-content-api/health
https://michiryu.com/michiryu-content-api/manifest
https://michiryu.com/michiryu-content-api/file?path=featured-content.json
```

## Token Gate

For the current basic token gate, set:

```php
'token_hash' => hash('sha256', 'your-token-here'),
```

For the current test token, the value is:

```php
'token_hash' => '693e9ce2996d348e2720c198be73be1b81c670cb766296a55f70249ba8c1d56e',
```

The plugin/import client sends:

```text
X-MichiRyu-Content-Token: your-token-here
```

The API also accepts `Authorization: Bearer <token>` for compatibility, but the
custom header is preferred because some shared hosts block bearer auth before
PHP receives the request.

This is a soft gate. Premium content should later use user-specific license
tokens validated by a server-side entitlement service.

## Hardening Included

- Supports explicit `basic` and future `premium` library separation.
- Allows only configured manifest keys.
- Keeps each library tied to its own configured `content_root`.
- Rejects absolute paths, URL paths, empty path segments, `.`, and `..`
  traversal.
- Serves only files resolved inside the configured library root.
- Restricts served file extensions with `allowed_extensions`.
- Rejects files larger than `max_file_bytes`.
- Fails closed when a library token hash is missing.
- Returns JSON error responses for normal API failures.
- Uses `X-Content-Type-Options: nosniff` and conservative cache headers.
- Keeps `config.php` and `config.example.php` blocked from direct web access
  through `.htaccess`.
- Provides a `/health` route for setup checks.

## Later

Rate-limit-style safeguards should be added at the hosting or edge layer when
premium entitlement validation exists. The current API avoids local mutable rate
state so it remains simple to deploy on shared hosting.
