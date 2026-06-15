# Roadmap

## Current Focus

- Preserve the existing public shortcode and admin behavior.
- Keep Yuki no Sato story continuity consistent across all 24 Sekki and 72 Ko stories.
- Maintain Masaru-sensei as the canonical Flower Teacher across interface labels, story metadata, and future educational materials.
- Keep installable ZIP builds reliable for LocalWP and WordPress testing.

## Near-Term

- Continue testing the 1.2.x story, map, banner, and character experience locally.
- Verify the story reader and map character rail after content updates.
- Keep changelog entries aligned with version bumps.
- Keep generated archives out of Git.

## Content Continuity

- Use `michiryu-sekki/stories/YukiNoSato_CharacterBible.md` as the canonical story-world reference.
- Keep recurring villagers consistent with their character metadata and story roles.
- Avoid turning Yuki no Sato stories into adventure, conflict, samurai, or politics-driven tales.
- Let seasonal observation, ikebana, community, and impermanence remain the center.

## Technical Maintenance

- Keep admin-only code under `michiryu-sekki/admin`.
- Keep shared runtime code under `michiryu-sekki/includes`.
- Run PHP syntax checks on edited PHP files.
- Prefer small, focused changes with low behavior risk.

## Release Workflow

- For local test builds, create a ZIP without changing the plugin version.
- For production-ready batches, update the plugin version, changelog, and release notes before packaging.
- Use patch version bumps for production bug fixes, minor version bumps for stable feature batches, and major version bumps only for larger stable milestones.

