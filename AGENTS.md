# AGENTS.md

## Project Rules for Codex

- Treat `michiryu-sekki/` as the WordPress plugin root.
- Keep the main plugin file at `michiryu-sekki/michiryu-sekki.php`.
- Do not change plugin behavior while performing structure-only cleanup.
- Do not rename database tables or option keys unless explicitly requested.
- Do not remove existing working code, stories, or assets.
- Keep generated archives such as `.zip` files out of Git.
- Prefer small, focused changes that preserve the existing public shortcode and admin behavior.
- Run PHP syntax checks on edited PHP files before handing work back.
- When adding new frontend code, keep assets under `michiryu-sekki/assets/css`, `michiryu-sekki/assets/js`, or `michiryu-sekki/assets/images`.
- Keep admin-only code under `michiryu-sekki/admin` and shared/runtime code under `michiryu-sekki/includes`.

## Git, ZIP, and Version Workflow

- Make related changes in small batches.
- For routine development, make the requested code changes without incrementing the plugin version unless the user specifically asks for a release/version bump.
- When the user asks for a local testing package, generate a complete installable WordPress plugin ZIP and keep the same plugin version.
- Expect test cycles to repeat: change, ZIP, local WordPress/LocalWP testing, fix, ZIP, and test again.
- Bug fixes during testing should get a new test ZIP with the same version number.
- Do not create a ZIP for every tiny change unless the user asks for a test build, install build, shareable build, or release build.
- Keep generated ZIP files out of Git.
- Do not treat test ZIPs as GitHub releases.
- When a feature batch is complete and ready for production, update the plugin version, update changelog entries, commit to Git with a clear message, push to GitHub, and generate a release ZIP.
- Use patch-style version bumps for production bug-fix releases, minor version bumps for stable feature batches, and reserve major version bumps for stable larger milestones.
