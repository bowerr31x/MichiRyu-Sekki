# Architecture

## Overview

MichiRyu-Sekki is a WordPress plugin contained in `michiryu-sekki/`. The plugin displays the 24 Sekki seasonal calendar and 72 Ko microseason structure, with story, map, character, and image features supplied through a Content Provider.

The repository root contains project documentation, licensing notices, and build artifacts used for local testing. The installable plugin root remains `michiryu-sekki/`.

## Plugin Root

```text
michiryu-sekki/
├── michiryu-sekki.php
├── admin/
├── includes/
├── public/
├── assets/
├── readme.txt
└── uninstall.php
```

## Runtime Areas

- `michiryu-sekki/michiryu-sekki.php` is the main WordPress plugin file and metadata source.
- `michiryu-sekki/admin/` contains admin-only settings and WordPress dashboard behavior.
- `michiryu-sekki/includes/` contains shared runtime classes, shortcode rendering, content loading, data access, and widget behavior.
- `michiryu-sekki/assets/css/` contains frontend styles.
- `michiryu-sekki/assets/js/` contains frontend interactivity.
- `michiryu-sekki/assets/images/` contains GPL-safe placeholders only. Proprietary images are not stored in the plugin repository.
- `michiryu-sekki/includes/providers/` contains the content provider interface and core providers.
- `docs/CONTENT-PROVIDER-SCHEMA.md` describes the provider contract for private or remote content integrations.
- `docs/CONTENT-IMPORT-BDD.md` describes expected behavior for the future admin-approved import workflow.

## Content Flow

Runtime content access goes through the active provider. The default local provider returns factual Sekki and Ko calendar data only. Story, map, image, character, and educational content must be supplied by a separate provider and licensed outside the GPL plugin package.

The admin settings page includes a Content Provider Status panel that reports
the active provider key and class, content record counts, and whether provider
map or signature images are available. When the file provider is requested, the
panel also reports the configured external path, path validity, and content URL.

## Content Provider Migration

Runtime content access is routed through `MichiRyu_Sekki_Content_Provider_Interface`.

Current providers:

- `MichiRyu_Sekki_Local_Content_Provider`: GPL-safe provider that returns factual Sekki and Ko calendar data only.
- `MichiRyu_Sekki_File_Content_Provider`: GPL-safe adapter that reads JSON data from a separately managed content directory outside the plugin folder.

The default mode is `local`.

The provider key can be changed with the `michiryu_sekki_content_provider_key` filter and is passed to provider selection. A full provider object can be supplied with the `michiryu_sekki_content_provider` filter.

Provider responses are normalized at the runtime boundary. Invalid provider
lists are treated as empty, and Sekki, Ko, and map data fall back to the local
provider when a custom provider response is unavailable.

## MichiRyu Content Import

The preferred architecture for official MichiRyu proprietary content is an
admin-approved one-time import into local WordPress storage.

```text
Public GPL plugin
        ↓
Admin-approved import from MichiRyu
        ↓
Content stored locally in WordPress
        ↓
Plugin renders from the local copy
```

The plugin should not silently download content on activation. The administrator
must explicitly choose to connect and import, acknowledge the MichiRyu content
license, and confirm that no visitor or member personal data is transmitted.

Manual content updates should be the default. Optional periodic update checks
may be added later, but should be opt-in.

The admin settings may store import consent acknowledgements and the selected
content update mode before the network import service is implemented. Saving
those settings must not trigger any download by itself.

## Protected Content Endpoint

The current basic content library can be imported from a hosted content URL.
Long term, hosted content access should move behind a protected content API that
validates bearer tokens server-side before returning manifests or files.

The endpoint is separate content infrastructure, not plugin code. See
`docs/CONTENT-API-SPEC.md`.

## Packaging

Installable ZIP files should be generated from the `michiryu-sekki/` folder so the archive contains `michiryu-sekki/` as its top-level directory.

Generated archives such as `.zip` files are build artifacts and should stay out of Git.
