# MichiRyu-Sekki

MichiRyu-Sekki is a WordPress plugin for displaying the Japanese 24 Sekki seasonal calendar with seasonal cards, story reading progress, image assets, and ikebana prompts.

## Repository Structure

```text
MichiRyu-Sekki/
├── AGENTS.md
├── README.md
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

1. Copy or symlink `michiryu-sekki/` into your local WordPress `wp-content/plugins/` directory.
2. In WordPress Admin, go to **Plugins** and activate **MichiRyu-Sekki-Calendar**.
3. Add one of the plugin shortcodes to a page, such as `[michiryu_story]`.
4. Visit the page and confirm the seasonal story reader, progress dots, images, and navigation still load.

## Packaging

Generated zip files are intentionally ignored by Git. Keep source changes in `michiryu-sekki/`, then create release zips from that plugin folder when needed.
