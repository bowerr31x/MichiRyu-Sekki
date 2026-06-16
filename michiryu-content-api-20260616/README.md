# MichiRyu Content API

This package is server-side content infrastructure. It is not part of the public GPL WordPress plugin.

## Deploy

1. Upload this folder as:

```text
/home1/bowerrx1/public_html/michiryu-content-api/
```

2. Copy `config.example.php` to `config.php` on the server.
3. Confirm `content_root` points to the existing hosted content folder:

```text
/home1/bowerrx1/public_html/michiryu-content
```

4. Set `basic_token_hash` in `config.php` before using this for anything beyond initial testing.

## Bluehost-Friendly Test URLs

Use these explicit URLs first because they do not depend on clean URL rewriting:

```text
https://www.bowerr31x.com/michiryu-content-api/index.php?route=health
https://www.bowerr31x.com/michiryu-content-api/index.php?route=manifest
https://www.bowerr31x.com/michiryu-content-api/index.php?route=file&path=featured-content.json
https://www.bowerr31x.com/michiryu-content-api/index.php?route=file&path=images/map/yuki-no-sato-sekki-map.jpg
```

If `.htaccess` rewriting is working, these shorter URLs should also work:

```text
https://www.bowerr31x.com/michiryu-content-api/health
https://www.bowerr31x.com/michiryu-content-api/manifest
https://www.bowerr31x.com/michiryu-content-api/file?path=featured-content.json
```

## Token Gate

For the current basic token gate, set:

```php
'basic_token_hash' => hash('sha256', 'your-token-here'),
```

For the current test token, the value is:

```php
'basic_token_hash' => '693e9ce2996d348e2720c198be73be1b81c670cb766296a55f70249ba8c1d56e',
```

The plugin/import client sends:

```text
Authorization: Bearer your-token-here
```

This is a soft gate. Premium content should later use user-specific license tokens validated by a server-side entitlement service.

## Hardening Included

- Rejects absolute paths, URL paths, empty path segments, `.`, and `..` traversal.
- Serves only files resolved inside the configured `content_root`.
- Restricts served file extensions with `allowed_extensions`.
- Rejects files larger than `max_file_bytes`.
- Uses `X-Content-Type-Options: nosniff` and conservative cache headers.
- Keeps `config.php` and `config.example.php` blocked from direct web access through `.htaccess`.
- Provides a `/health` route for setup checks.
