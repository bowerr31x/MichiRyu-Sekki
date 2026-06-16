# MichiRyu Content API Prototype

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

4. Test:

```text
https://www.bowerr31x.com/michiryu-content-api/manifest
https://www.bowerr31x.com/michiryu-content-api/file?path=featured-content.json
https://www.bowerr31x.com/michiryu-content-api/file?path=images/map/yuki-no-sato-sekki-map.jpg
```

## Token Gate

Leave `basic_token_hash` empty while testing.

For a soft basic token gate, set:

```php
'basic_token_hash' => hash('sha256', 'your-token-here'),
```

The plugin/import client sends:

```text
Authorization: Bearer your-token-here
```

This is only a soft gate. Premium content must use user-specific license tokens validated by a server-side entitlement service.

## Notes

The endpoint rejects absolute paths, URL paths, empty path segments, and `..` traversal. It only serves files resolved inside the configured `content_root`.
