# Content Provider Schema

## Purpose

This document defines the GPL-safe contract that external content providers use
to supply stories, images, maps, characters, and educational content to
MichiRyu-Sekki.

The plugin repository must not contain proprietary content. Providers may be
private plugins, private libraries, remote services, or site-specific code.

Official MichiRyu proprietary content should eventually use an admin-approved
import workflow. The import service may deliver the same content shapes
described here, but imported content should be stored locally in WordPress and
served from the local copy after import.

## Registration

Providers register with the `michiryu_sekki_content_provider` filter:

```php
add_filter(
	'michiryu_sekki_content_provider',
	function ( $provider, $provider_key ) {
		if ( 'private' !== $provider_key ) {
			return $provider;
		}

		return new Private_Content_Provider();
	},
	10,
	2
);
```

The provider key may be selected with the `MICHIRYU_SEKKI_CONTENT_PROVIDER`
constant or the `michiryu_sekki_content_provider_key` filter.

## File Provider

The GPL plugin includes a file provider for LocalWP and private-library testing.
It reads JSON files from a separately managed directory outside the
`michiryu-sekki/` plugin folder.

Configuration:

```php
define( 'MICHIRYU_SEKKI_CONTENT_PROVIDER', 'file' );
define( 'MICHIRYU_SEKKI_CONTENT_PATH', '/absolute/path/to/private-content' );
define( 'MICHIRYU_SEKKI_CONTENT_URL', 'https://example.test/private-content' );
```

The path can also be supplied with the
`michiryu_sekki_file_content_provider_path` filter. The optional base URL can be
supplied with `michiryu_sekki_file_content_provider_url`.

Expected files:

```text
private-content/
├── featured-content.json
├── images.json
├── map-locations.json
├── sekki.json
└── ko.json
```

Only `featured-content.json`, `images.json`, and `map-locations.json` are
normally needed. If `sekki.json` or `ko.json` are missing, the plugin uses the
local factual calendar data.

The file provider will not read a content path inside the GPL plugin directory.
When the `file` provider is requested, the admin Content Provider Status panel
shows the configured path, whether it is a valid external directory, and the
configured content URL.

## Imported Provider

The GPL plugin also includes an imported provider for admin-approved remote
content imports. The administrator enters a remote content URL in
MichiRyu -> Content Library, accepts the required content acknowledgements, and
clicks Connect and Import Content.

The remote URL must expose:

```text
featured-content.json
images.json
images/...
```

After import, the plugin stores the local copy under:

```text
wp-content/uploads/michiryu-sekki-content/
├── featured-content.json
├── images.json
└── images/
```

When imported content exists and no stronger provider is configured, the plugin
uses the imported provider instead of making ongoing remote requests.

## Required Interface

Custom providers must implement `MichiRyu_Sekki_Content_Provider_Interface`.

Required methods:

- `get_sekki_content()`
- `get_ko_content()`
- `get_image( $id )`
- `get_map_data()`
- `get_featured_content()`

If provider data is unavailable, return empty arrays or empty strings. The
plugin will fall back to local factual calendar data for Sekki, Ko, and map
coordinates.

## Featured Content

`get_featured_content()` returns:

```php
array(
	'stories'    => array(),
	'characters' => array(),
)
```

Story records should include:

- `id`
- `sekki_number`
- `sekki_slug`
- `ko_number`
- `ko_slug`
- `title`
- `ko_name`
- `body_html`
- `body_text`
- `characters`
- `spotlight_character`
- `arrangement_materials`
- `theme`
- `lesson`
- `image`
- `icon`
- `location`

Character records should be keyed by character id and may include:

- `id`
- `name`
- `role`
- `bio`
- `portrait_file`

## Images

`get_image( $id )` may return:

- An absolute `http` or `https` URL.
- A plugin-relative path for GPL-safe assets included by another provider
  plugin.
- An array with a `url` value.
- An empty string when no image is available.

Common image ids:

- `map`
- `signature`
- `sekki/{filename}`
- `ko/{filename}`
- `characters/{filename}`

For the file provider, `images.json` is an object keyed by image id:

```json
{
  "map": "map.jpg",
  "signature": "signature.png",
  "sekki/Sekki_01_Risshun.png": "sekki/Sekki_01_Risshun.png"
}
```

Relative image paths require `MICHIRYU_SEKKI_CONTENT_URL`. Absolute `http` and
`https` URLs can be used without a content base URL.

## Map Data

`get_map_data()` returns an array of map location records:

```php
array(
	array(
		'sekki_number' => 1,
		'sekki_slug'   => 'risshun',
		'x_percent'    => 50,
		'y_percent'    => 50,
	),
)
```

Private providers may add additional fields for their own use, but core plugin
rendering should not require proprietary fields.

## Failure Behavior

Provider methods should not throw during normal operation. If they do, the
plugin catches provider read failures while building the content model, uses the
local provider fallback where possible, and treats failed image lookups as
unavailable images.

The plugin must remain usable with:

- No remote service.
- No private content plugin.
- Empty provider story data.
- Empty provider image data.
- Empty provider character data.
