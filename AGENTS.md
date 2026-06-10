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
