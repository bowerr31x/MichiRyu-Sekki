# Architecture

## Overview

MichiRyu-Sekki is a WordPress plugin contained in `michiryu-sekki/`. The plugin displays the 24 Sekki seasonal calendar, 72 Ko story content, Yuki no Sato map experience, character data, and bundled seasonal artwork.

The repository root contains project documentation, licensing notices, and build artifacts used for local testing. The installable plugin root remains `michiryu-sekki/`.

## Plugin Root

```text
michiryu-sekki/
├── michiryu-sekki.php
├── admin/
├── includes/
├── public/
├── assets/
├── stories/
├── readme.txt
└── uninstall.php
```

## Runtime Areas

- `michiryu-sekki/michiryu-sekki.php` is the main WordPress plugin file and metadata source.
- `michiryu-sekki/admin/` contains admin-only settings and WordPress dashboard behavior.
- `michiryu-sekki/includes/` contains shared runtime classes, shortcode rendering, content loading, data access, and widget behavior.
- `michiryu-sekki/assets/css/` contains frontend styles.
- `michiryu-sekki/assets/js/` contains frontend interactivity.
- `michiryu-sekki/assets/images/` contains bundled images, icons, maps, and character portraits.
- `michiryu-sekki/stories/` contains Ko story Markdown, character metadata, and story-world reference material.

## Content Flow

Story content is stored as Markdown files with front matter. Runtime content loading parses those files, joins them with character metadata from `stories/characters.json`, and renders story, map, and character-facing UI through the plugin classes.

Creative content is bundled with the plugin for normal display inside the MichiRyu-Sekki experience. Code and creative materials have different licensing expectations; see `docs/LICENSE-STRATEGY.md`.

## Content Provider Migration

Runtime content access is routed through `MichiRyu_Sekki_Content_Provider_Interface`.

Current providers:

- `MichiRyu_Sekki_Bundled_Content_Provider`: temporary migration provider that preserves the current bundled stories, characters, and images.
- `MichiRyu_Sekki_Local_Content_Provider`: GPL-safe provider that returns factual Sekki and Ko calendar data only.

The default mode remains `bundled` during migration. To exercise the GPL-safe mode, define:

```php
define( 'MICHIRYU_SEKKI_CONTENT_PROVIDER', 'local' );
```

The provider key can also be changed with the `michiryu_sekki_content_provider_key` filter. A full provider object can be supplied with the `michiryu_sekki_content_provider` filter.

## Packaging

Installable ZIP files should be generated from the `michiryu-sekki/` folder so the archive contains `michiryu-sekki/` as its top-level directory.

Generated archives such as `.zip` files are build artifacts and should stay out of Git.
