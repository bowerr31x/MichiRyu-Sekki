# Release QA Checklist

Use this checklist before publishing a production-ready MichiRyu-Sekki plugin
build.

## Package Checks

- Confirm the plugin ZIP installs as `michiryu-sekki/`.
- Confirm the ZIP does not contain proprietary stories, artwork, maps, icons,
  PDFs, educational materials, or Yuki no Sato content.
- Confirm the plugin version and readme stable tag match.
- Run PHP syntax checks on edited PHP files.

## WordPress Admin Checks

- Fresh install with no imported content.
- Confirm Content Provider Status shows the GPL-safe local calendar state.
- Confirm story, image, character, and map-dependent settings are clearly
  unavailable until content is imported.
- Save the content acknowledgements.
- Import Basic MichiRyu Content from `michiryu.com`.
- Confirm the import reports 72 stories, 31 characters, and 61 image
  references.
- Confirm Content Provider Status switches to the imported local WordPress copy.
- Confirm Developer diagnostics does not expose tokens.
- Confirm the mobile admin screen remains readable and usable.

## Frontend Checks

- Before import, confirm `[michiryu_sekki]` renders the basic calendar.
- Before import, confirm `[michiryu_story]` and `[michiryu_sekki_map]` fail
  gracefully.
- After import, confirm `[michiryu_sekki]` renders imported story/image
  enhancements.
- After import, confirm `[michiryu_story]` renders a story.
- After import, confirm `[michiryu_sekki_map]` renders the map experience.
- Check desktop and mobile viewport behavior.

## Content Lifecycle Checks

- Remove imported content from the admin screen.
- Confirm the plugin returns to the basic local calendar.
- Re-import Basic MichiRyu Content.
- Deactivate and reactivate the plugin.
- Confirm imported content and settings remain available after reactivation.
- Uninstall/delete the plugin on a disposable local site.
- Confirm uninstall behavior matches the intended release policy.

