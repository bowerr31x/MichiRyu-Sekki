# Roadmap

## Current Focus

- Preserve the existing public shortcode and admin behavior.
- Keep the plugin repository GPL-only.
- Route story, map, character, image, and educational content through providers.
- Keep installable ZIP builds reliable for LocalWP and WordPress testing.

## Near-Term

- Continue testing the 1.2.x seasonal display and provider-empty states locally.
- Test the file provider against a private content directory for story, map, banner, and character testing.
- Use `docs/CONTENT-PROVIDER-SCHEMA.md` as the contract for private provider work.
- Use the admin Content Provider Status panel to confirm file provider path validity during LocalWP provider testing.
- Design the admin-approved MichiRyu Content Library import flow.
- Keep imported-content updates manual by default.
- Use `docs/CONTENT-IMPORT-BDD.md` as acceptance criteria for import behavior.
- Store import consent acknowledgements and update mode before enabling network import.
- Design the protected content endpoint described in `docs/CONTENT-API-SPEC.md`.
- Keep changelog entries aligned with version bumps.
- Keep generated archives out of Git.

## Content Provider Continuity

- Keep canonical Yuki no Sato story-world references in the separate proprietary content library.
- Keep recurring villagers consistent in provider-supplied character metadata and story roles.
- Preserve provider boundaries so proprietary content does not enter the plugin repository.
- Keep the local provider useful without stories, maps, images, or remote content.
- Store official imported MichiRyu content locally in WordPress after explicit admin approval.

## Technical Maintenance

- Keep admin-only code under `michiryu-sekki/admin`.
- Keep shared runtime code under `michiryu-sekki/includes`.
- Run PHP syntax checks on edited PHP files.
- Prefer small, focused changes with low behavior risk.

## Release Workflow

- For local test builds, create a ZIP without changing the plugin version.
- For production-ready batches, update the plugin version, changelog, and release notes before packaging.
- Use patch version bumps for production bug fixes, minor version bumps for stable feature batches, and major version bumps only for larger stable milestones.
