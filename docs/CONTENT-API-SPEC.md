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
https://www.bowerr31x.com/michiryu-content-api/manifest
https://www.bowerr31x.com/michiryu-content-api/file?path=images/map/yuki-no-sato-sekki-map.jpg
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

Future plugin changes may add:

- Manifest endpoint support.
- Premium license token field.
- License validation status.
- Re-import/update checks.

## Migration Path

1. Keep the current static hosted folder public while testing.
2. Build and test `michiryu-content-api` against the same content directory.
3. Point the plugin basic content URL to the API endpoint.
4. Confirm basic import still works.
5. Add token validation for the basic endpoint.
6. Add premium token validation and premium manifests later.
7. Lock down or remove public access to the static folder only after the API
   endpoint is stable.
