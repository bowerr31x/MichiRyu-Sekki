# License Strategy

## Summary

MichiRyu-Sekki ships GPL WordPress plugin code only. Proprietary MichiRyu creative content is stored and distributed separately:

- Plugin code is licensed under GPLv2 or later.
- Original creative content is excluded from the plugin repository and accessed through a Content Provider.

This keeps the WordPress plugin compatible with WordPress licensing expectations while preserving ownership of Yuki no Sato stories, artwork, maps, icons, names, branding, PDFs, and educational content.

## Code License

The following are treated as GPLv2-or-later plugin code:

- PHP files
- JavaScript files
- CSS files
- WordPress integration code

The project root `LICENSE` file contains the GPL license text.

## Proprietary Content

The following must not be committed to the plugin repository unless explicitly relicensed for GPL distribution:

- Yuki no Sato stories
- Character names and story-world materials
- Maps
- Icons
- Original artwork
- Branding and icons
- PDFs
- Written seasonal and educational content

The project root `CONTENT-LICENSE` and plugin `michiryu-sekki/CONTENT-LICENSE` describe this content-provider and imported-content treatment.

## Distribution Intent

The plugin must function fully without any proprietary content source. Proprietary content may enhance the experience through a provider, but it is not required for plugin activation, settings, shortcodes, or seasonal display.

Official MichiRyu proprietary content may later be downloaded through an
admin-approved import workflow and stored locally in WordPress. Downloaded
content remains proprietary and separately licensed even though it is stored in
the site database or Media Library.

The default update mode for imported content should be manual updates only.
Automatic update checks must be opt-in.

## Contributor Guidance

- Keep code and content-provider licensing language consistent across docs and readme files.
- Do not add third-party assets without source and license notes.
- Do not commit proprietary content to the plugin repository.
- If new asset types are added, document whether they are code, creative content, or third-party material.
