# ADR-002

Title:
Use admin-approved local import for MichiRyu proprietary content

Status:
Accepted

Date:
2026-06-15

Decision:

MichiRyu-Sekki shall prefer an admin-approved content import model for official
MichiRyu proprietary content.

The GPL plugin shall activate and run with basic local calendar data. An
administrator may then choose to connect to the MichiRyu Content Library,
download proprietary story text, images, maps, characters, metadata, and
educational materials, and store that content locally in WordPress.

Normal frontend rendering shall use the local WordPress copy and shall not
depend on constant API calls to MichiRyu.com.

The default content update mode shall be manual updates only. Automatic checks
may be added later, but must be opt-in.

Rationale:

- Preserve GPL distribution for plugin code.
- Keep proprietary MichiRyu content separate from the public plugin repository.
- Improve frontend performance by serving imported local content.
- Reduce dependency on MichiRyu servers for normal page views.
- Avoid silently downloading copyrighted content on plugin activation.
- Make privacy expectations clear by requiring admin approval and not sending
  visitor or member personal data during import.

Consequences:

- Imported content is easier for site owners to access and copy from their own
  WordPress database or media library.
- Licensing and admin consent language must be clear.
- Stronger controls for future premium content may require account or license
  key validation.
