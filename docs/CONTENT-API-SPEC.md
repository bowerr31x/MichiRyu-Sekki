# MichiRyu Content API Spec

## Purpose

This document describes the future protected content endpoint used by the public
GPL MichiRyu-Sekki plugin to import proprietary MichiRyu content.

The endpoint is server/content infrastructure. It should live outside the public
plugin repository and outside the installable WordPress plugin ZIP.

## Goals

- Keep proprietary content out of the GPL plugin repository.
- Allow the plugin to import basic MichiRyu content without exposing raw content
  URLs in the normal admin flow.
- Support future premium content through user-specific license or subscription
  tokens.
- Keep frontend rendering local after import.
- Avoid sending visitor or member personal data.

## Non-Goals

- This endpoint is not a frontend runtime dependency.
- This endpoint does not prevent a site owner from accessing content already
  imported into their own WordPress installation.
- A shared basic token is not strong protection for premium content.

## Proposed Package

```text
michiryu-content-api/
├── index.php
├── config.example.php
├── .htaccess
└── README.md
```

This package should be deployed separately from the WordPress plugin.

## Endpoint Shape

Temporary current-host shape:

```text
https://www.bowerr31x.com/michiryu-content-api/index.php?route=manifest
https://www.bowerr31x.com/michiryu-content-api/index.php?route=file&path=images/map/yuki-no-sato-sekki-map.jpg
```

Preferred future host:

```text
https://content.michiryu.com/basic/manifest
https://content.michiryu.com/premium/manifest
```

## Authentication

Import requests may send:

```text
Authorization: Bearer <token>
```

Token behavior:

- Empty token: allowed only for public/basic testing endpoints.
- Shared basic token: may unlock basic MichiRyu content as a soft gate.
- User license token: required for premium or subscription content.

The server validates token entitlement before returning protected manifests or
files.

## Basic Manifest Response

`GET /manifest`

Response:

```json
{
  "library": "michiryu-basic",
  "version": "2026.06.16",
  "license": "MichiRyu Content License",
  "featured_content": "featured-content.json",
  "images": "images.json",
  "base_url": "https://www.bowerr31x.com/michiryu-content-api/file?path="
}
```

The first implementation may return direct JSON content instead of manifest
links if that is simpler, but the plugin import model should remain:

```text
remote endpoint
        ↓
admin-approved import
        ↓
local WordPress copy
        ↓
frontend rendering from local storage
```

## File Endpoint

`GET /file?path=<relative-path>`

Rules:

- `path` must be relative.
- `..`, empty path segments, absolute paths, and URL values are rejected.
- The resolved path must remain inside the configured content directory.
- Response should use an appropriate content type.
- Protected content should require a valid token.

Example:

```text
GET /file?path=images/sekki/Sekki_01_Risshun.png
Authorization: Bearer <token>
```

## Recommended Server Config

Configuration should not be committed with real secrets.

`config.example.php` may define:

```php
return array(
	'content_root' => '/home1/bowerrx1/private-content',
	'basic_token_hash' => '',
	'premium_token_validator' => null,
);
```

Real deployment config should live outside Git or be excluded from Git.

## Plugin Compatibility

The current plugin importer already supports:

- Remote content URL.
- Optional bearer token.
- Basic content default URL.
- Local imported provider.
- Manifest endpoint imports.

Current operating decision:

- Basic Import uses the hardened protected API manifest:
  `https://www.bowerr31x.com/michiryu-content-api/index.php?route=manifest`
- Advanced content settings remain available for support, testing, and
  self-hosted content libraries.
- The current built-in basic token is a soft gate only. It is acceptable for
  basic import testing but must not be used as premium-content protection.

Future plugin changes may add:

- Premium license token validation.
- License validation status returned by the server.
- Re-import/update checks.

## Premium License Scaffold

The plugin may save a future premium license token locally, but the current
basic import must not use that token. Premium access becomes active only after
the server exposes an entitlement-aware endpoint.

Future validation request:

```text
GET /license/status
Authorization: Bearer <license-token>
```

Future validation response:

```json
{
  "status": "active",
  "library": "michiryu-premium",
  "allowed_manifests": [
    "premium"
  ],
  "expires": "2027-06-16"
}
```

The server must return premium manifest and file URLs only after validating the
license token. The plugin must import premium content into local WordPress
storage and must not expose license tokens on frontend pages.

## Migration Path

1. Build and test `michiryu-content-api` against the same content directory. ✓
2. Test the protected API endpoint through Advanced content settings. ✓
3. Add token validation for the basic endpoint. ✓
4. Confirm token-based import works. ✓
5. Point the plugin basic content URL to the API endpoint when hardened. ✓
6. Add premium token validation and premium manifests later.
7. Lock down or remove public access to the static folder only after the API
   endpoint is stable.
