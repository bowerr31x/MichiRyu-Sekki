# MichiRyu-Sekki

MichiRyu-Sekki is a WordPress plugin for displaying the Japanese 24 Sekki
seasonal calendar with current Sekki details, Ko microseason details, seasonal
stories, image assets, and a journey map experience.

Author: MichiRyu
Author URI: https://michiryu.com

## What It Provides

- `[michiryu_sekki]` for the main seasonal experience.
- `[michiryu_journey]` for a journey entry view.
- `[michiryu_story]` for the story reader.
- `[michiryu_sekki_map]` for the seasonal map.
- Admin settings for display style, Ko display, name fields, image display,
  date stamp, story teaser, map URL, and optional custom CSS.

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
    ├── stories/
    ├── readme.txt
    └── uninstall.php
```

The `michiryu-sekki/` directory is the installable WordPress plugin folder.

## Local WordPress Testing

1. Copy or symlink `michiryu-sekki/` into a local WordPress `wp-content/plugins/` directory.
2. In WordPress Admin, go to Plugins and activate MichiRyu-Sekki-Calendar.
3. Add `[michiryu_sekki]` to a page.
4. Confirm the current Sekki, current Ko, story teaser, image, About panel, and map handoff render correctly.
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

Original artwork, Yuki no Sato stories, maps, icons, names, branding, and
written seasonal content are copyright Russell Bowers / MichiRyu unless
explicitly stated otherwise. These creative materials are provided for normal
use within the MichiRyu-Sekki plugin experience and are not offered as
standalone reusable creative assets.

See `CONTENT-LICENSE` for the non-code creative content notice.

## Changelog

### 1.0.0

- Initial production release of the MichiRyu-Sekki seasonal display experience.
- Includes the main Sekki shortcode, seasonal display settings, bundled
  MichiRyu creative content, and GPLv2-or-later plugin code licensing.
