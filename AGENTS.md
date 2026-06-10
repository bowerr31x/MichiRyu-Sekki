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
- Commit each logical batch to Git with a clear message.
- Push to GitHub regularly after useful commits so the work is backed up.
- Do not create a new ZIP for every tiny change.
- Create a ZIP only when the user asks for a test build, install build, shareable build, or release build.
- Keep generated ZIP files out of Git.
- Do not increment the plugin version for ordinary development commits.
- Increment the plugin version only when making a ZIP/release build that may be installed or distributed.
- Use patch-style version bumps for test ZIPs, minor version bumps for stable feature batches, and reserve major version bumps for stable larger milestones.
