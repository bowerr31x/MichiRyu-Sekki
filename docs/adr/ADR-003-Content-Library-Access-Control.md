# ADR-003

Title:
Protect hosted MichiRyu content through server-side access control

Status:
Accepted

Date:
2026-06-16

Decision:

MichiRyu-Sekki shall keep the public GPL plugin separate from the hosted
MichiRyu Content Library and shall move content access control to the server
side.

The current static hosted content folder may remain public during transition,
but it is not the long-term protection model. Basic Import should use the
hardened content API manifest once the endpoint passes hosted import tests.

Basic MichiRyu content may use a shared or hidden basic access token as a soft
gate for import requests. This token is a convenience control only, because any
token embedded in a public GPL plugin can be inspected.

Premium or subscription content shall require user-specific license or
subscription tokens validated by the MichiRyu server before premium manifests or
assets are served.

The preferred future hosted-content shape is an authenticated content endpoint,
for example:

```text
https://content.michiryu.com/basic/manifest
https://content.michiryu.com/premium/manifest
```

or a temporary endpoint under the current host:

```text
https://michiryu.com/michiryu-content-api/index.php?route=manifest
```

The plugin shall continue to import content into local WordPress storage and
shall not require remote requests during normal frontend rendering.

Rationale:

- Keep the plugin GPL-safe and free of proprietary content.
- Avoid relying on obscurity of public static file paths.
- Allow the current basic import workflow to remain stable while protection is
  introduced.
- Support future premium content with real entitlement checks.
- Keep visitor-facing pages private from remote-service dependency.

Consequences:

- The public static folder may be locked down once the protected endpoint
  remains stable in hosted testing. The API reads the same files through the
  server filesystem, so direct browser access is not required.
- Basic shared tokens are not strong security and must not be used for valuable
  premium content.
- Premium imports require a server component that can validate license tokens
  and return the correct manifest.
- Imported content remains copyable from a site owner's WordPress storage, so
  license terms remain important even with server-side access control.
