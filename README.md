# MichiRyu-Sekki

MichiRyu-Sekki is a WordPress plugin for displaying the Japanese 24 Sekki
seasonal calendar with current Sekki details, Ko microseason details, seasonal
provider-ready story, image, and journey map features.

Author: MichiRyu
Author URI: https://michiryu.com

## What It Provides

- `[michiryu_sekki]` for the main seasonal experience.
- `[michiryu_journey]` for a journey entry view.
- `[michiryu_story]` for provider-supplied stories.
- `[michiryu_sekki_map]` for a provider-supplied seasonal map.
- Admin settings for display style, Ko display, name fields, image display,
  date stamp, content-provider enhancements, map URL, and optional custom CSS.
- Admin provider status showing the active provider, content counts, file
  provider path status, and whether map/signature images are supplied.
- Optional file content provider for testing a separate private content library
  without bundling proprietary content in the plugin.
- Future admin-approved MichiRyu Content Library import, storing proprietary
  content locally in WordPress after consent.
- BDD acceptance scenarios for the future content import flow in
  `docs/CONTENT-IMPORT-BDD.md`.
- Admin settings for future content import consent and manual or opt-in update
  checks.

## Repository Structure

```text
MichiRyu-Sekki/
├── AGENTS.md
├── README.md
├── LICENSE
├── CONTENT-LICENSE
├── .gitignore
└── michiryu-sekki/
    ├── michiryu-sekki.php
    ├── admin/
    ├── includes/
    ├── public/
    ├── assets/
    │   ├── css/
    │   ├── js/
    │   └── images/
    ├── readme.txt
    └── uninstall.php
```

The `michiryu-sekki/` directory is the installable WordPress plugin folder.

## Local WordPress Testing

1. Copy or symlink `michiryu-sekki/` into a local WordPress `wp-content/plugins/` directory.
2. In WordPress Admin, go to Plugins and activate MichiRyu-Sekki-Calendar.
3. Add `[michiryu_sekki]` to a page.
4. Confirm the current Sekki, current Ko, About panel, and map handoff render correctly.
5. For debug testing, enable `WP_DEBUG` and `WP_DEBUG_LOG` in the local
   WordPress site and confirm no warnings, notices, or deprecated-function
   messages are logged.

## Packaging

Generated ZIP files are intentionally ignored by Git. Keep source changes in
`michiryu-sekki/`, then create installable ZIP packages from that plugin folder
when needed.

For routine local testing, create a ZIP without changing the plugin version
number. For a production release, update the version, changelog, and release
notes before packaging.

## Licensing

Plugin PHP, JavaScript, and CSS code is licensed under GPLv2 or later. See
`LICENSE`.

The plugin repository contains GPL software only. Proprietary stories, artwork,
maps, icons, PDFs, educational materials, and Yuki no Sato content are not
included in this repository and must be supplied through a Content Provider.

See `CONTENT-LICENSE` for the content-provider notice.

## Changelog

### 1.0.0

- Initial production release of the MichiRyu-Sekki seasonal display experience.
- Includes the main Sekki shortcode, seasonal display settings, and
  GPLv2-or-later plugin code licensing.
