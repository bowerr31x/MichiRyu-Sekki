# Issue: Basic and Custom Content Imports Blocked by Protected File Downloads

## Status

Release blocker.

## Symptom

The WordPress plugin can reach the MichiRyu content API manifest, but protected `route=file` downloads return `403`, including:

- `https://michiryu.com/michiryu-content-api/index.php?route=file&library=basic&path=images.json`
- `https://michiryu.com/michiryu-content-api/index.php?route=file&library=basic&path=images%2Fcharacters%2FCharacter_Sora.svg`

Basic import and custom import both need to pass before release.

## Current Source State

The content API package supports configured libraries via `config.php`.

The example config now defines:

- `basic`
- `custom`
- `premium`

In the WordPress plugin, "custom" still means an administrator-entered content source URL and access token. The hosted source should expose an explicit `custom` library so the Custom Content Library import can be tested independently from the built-in basic import.

## Expected Behavior

Both flows should work:

- Basic import uses the built-in basic manifest URL and built-in token.
- Custom import uses the saved custom source URL and saved token.

Both flows must be able to download:

- `featured-content.json`
- `images.json`
- all image files referenced by `images.json`

## Recommended Fix

Confirm the hosted content API config has separate, explicit libraries for each release source that needs independent validation:

- `basic`
- `custom`
- `premium`

Each configured library should have:

- a readable `content_root`
- a valid `token_hash`
- manifest paths for `featured-content.json` and `images.json`
- file route access that accepts the same token for JSON and image paths

Custom import can point at `library=custom` during release testing. Basic import should continue to use the built-in basic manifest URL and token.

## Notes

The plugin importer now sends both supported auth headers for protected requests:

- `X-MichiRyu-Content-Token`
- `Authorization: Bearer`

If `route=file` still returns `403`, the remaining fix is likely in the hosted content API configuration or server/header handling.
