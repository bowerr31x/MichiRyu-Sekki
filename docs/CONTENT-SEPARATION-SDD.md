# Software Design Document (SDD)

## MichiRyu-Sekki Content Separation Architecture

### Version 1.2

### June 2026

---

# 1. Purpose

This document defines the architectural separation between:

1. **MichiRyu-Sekki Plugin** (GPL software)
2. **MichiRyu Content Library** (proprietary content)

The goal is to allow public distribution of the MichiRyu-Sekki WordPress plugin while retaining exclusive ownership of all creative and educational content associated with Yuki no Sato and related MichiRyu intellectual property.

---

# 2. Business Objectives

## Primary Objectives

The MichiRyu-Sekki plugin shall:

* Be distributable under GPL.
* Be publishable on GitHub.
* Potentially be publishable in the WordPress Plugin Directory.
* Operate fully without proprietary content.

The MichiRyu content library shall:

* Remain copyright protected.
* Remain under the control of MichiRyu.
* Be independently versioned and managed.
* Be usable by future MichiRyu software products.

---

# 3. Architectural Principles

## Principle 1: Software and Content are Separate Products

The plugin is software.

The stories, artwork, maps, educational materials, icons, and imagery are content.

They shall not be stored together.

---

## Principle 2: Plugin Must Function Standalone

A user installing the plugin must receive a complete, usable seasonal calendar experience without requiring any external service.

The plugin shall never fail because proprietary content is unavailable.

---

## Principle 3: Content is Optional

All proprietary content shall be treated as an enhancement layer.

The plugin shall gracefully operate in either:

### Mode A

Calendar Only

### Mode B

Calendar + MichiRyu Content

---

# 4. Scope

---

## Included in Plugin

### Seasonal Calendar Data

24 Sekki

72 Kō

Date calculations

Current season determination

Season transitions

Localization support

---

### User Features

Widgets

Shortcodes

Blocks

Notifications

Progress tracking

Map framework

Navigation framework

Settings

Caching

API framework

Accessibility support

Responsive design

---

### Technical Features

Database schema

Admin interface

REST endpoints

Cron processing

Asset management

Upgrade routines

Security controls

---

## Excluded from Plugin

The following shall NOT be distributed in the plugin repository.

### Stories

Yuki no Sato stories

Microseason stories

Character stories

Expanded narratives

Seasonal essays

Fictional content

---

### Artwork

Sekki illustrations

Kō illustrations

Paintings

Backgrounds

Banners

Custom visual assets

---

### Maps

Village maps

Interactive story maps

Location illustrations

Journey maps

---

### Characters

Hana no Sensei

Villagers

Character biographies

Character descriptions

Character artwork

Character metadata

---

### Educational Content

Premium articles

Seasonal lessons

Study guides

PDF downloads

Course materials

---

### Branding Assets

Premium icon sets

Premium image libraries

Future commercial content

---

# 5. Content Provider Architecture

## Objective

Allow future content providers to deliver enhanced content without modifying plugin code.

---

## Provider Interface

Create a Content Provider abstraction.

### Interface

```php
interface ContentProviderInterface
{
    public function getSekkiContent($sekkiId);

    public function getKoContent($koId);

    public function getImage($id);

    public function getMapData();

    public function getFeaturedContent();
}
```

The plugin shall never directly reference proprietary content.

All content access must occur through a provider.

---

# 6. Default Provider

The plugin shall ship with:

## Local Provider

Purpose:

Provide only public domain or factual seasonal information.

Example:

```json
{
  "name": "Risshun",
  "translation": "Beginning of Spring",
  "date_range": "Feb 4 - Feb 18",
  "description": "First season of spring in the traditional Japanese calendar."
}
```

No proprietary text.

No proprietary images.

No proprietary maps.

---

# 7. MichiRyu Content Import

## Architecture

The preferred MichiRyu content model is:

```text
Public GPL plugin
        ↓
Admin-approved content import from MichiRyu
        ↓
Content stored locally in WordPress
        ↓
Plugin runs without constant API calls
```

Purpose:

Supply proprietary MichiRyu content while keeping plugin code public, GPL, and
usable without any external content service.

Potential content:

Stories

Artwork

Maps

Character information

Educational materials

Premium seasonal content

---

## Implemented Import Model

The current implementation supports a manual remote content import:

```text
MichiRyu Content Library URL
        ↓
Admin consent
        ↓
Manual import
        ↓
Local WordPress uploads storage
        ↓
Imported Content Provider
```

The import reads:

```text
featured-content.json
images.json
referenced image files
```

Imported content is stored under the local WordPress uploads directory and is
used for normal frontend rendering without constant remote calls.

---

## Target User Experience

The long-term admin experience should avoid exposing low-level content URLs to
ordinary site administrators.

Preferred basic-content flow:

```text
MichiRyu Content Library

[Import Basic MichiRyu Content]

Advanced settings
Remote content URL: hidden by default
Content access token: hidden by default
```

The plugin may provide internal defaults for basic MichiRyu content:

```text
Default content library URL
Default basic access token
```

Current default basic content library URL:

```text
https://michiryu.com/michiryu-content-api/index.php?route=manifest
```

These defaults are used only to import the basic MichiRyu content package into
the site. After import, the site uses its local WordPress copy.

The remote URL and token fields should remain available as advanced settings for
development, testing, self-hosted content libraries, and support.

---

## Token and License Strategy

The import architecture supports an optional content access token.

When a token is present, the importer sends:

```text
X-MichiRyu-Content-Token: <token>
```

The API may also accept `Authorization: Bearer <token>` for compatibility, but
the custom MichiRyu header is preferred because some shared hosts block bearer
authorization before PHP receives the request.

The token applies to:

* `featured-content.json`
* `images.json`
* Referenced image files

For basic content, a built-in or hidden token may be used as a convenience gate.
However, a token embedded in a public GPL plugin is not a true secret because
users can inspect the plugin code.

Therefore:

* Hidden/basic tokens may reduce accidental public discovery.
* Hidden/basic tokens must not be treated as strong premium-content protection.
* Premium content must use user-specific license or subscription tokens.
* Premium token validation must happen server-side.
* Premium content should be imported locally only after the server confirms
  entitlement.

Preferred future premium flow:

```text
Premium Content
License token: [ user enters token ]
[Connect Premium Library]
        ↓
Server validates entitlement
        ↓
Premium manifest is imported locally
        ↓
Frontend renders from local WordPress copy
```

The plugin must not expose license tokens on frontend pages.

Current scaffold:

* The admin screen may save a Premium license token for future use.
* The saved premium token is not used by the Basic Import action.
* No premium manifest should be requested until server-side entitlement
  validation exists.
* Basic content continues to use the protected basic manifest and soft basic
  token.

---

## Hosted Content Protection

The static hosted content library may remain publicly reachable during
transition, but it is no longer the preferred basic import path.

Long term, the project should move from direct public static file access toward
a server-side content endpoint that can validate access before serving manifests
or assets.

Preferred endpoint direction:

```text
https://content.michiryu.com/basic/manifest
https://content.michiryu.com/premium/manifest
```

Temporary endpoint direction if hosted under the current account:

```text
https://michiryu.com/michiryu-content-api/index.php?route=manifest
```

The server endpoint should:

* Accept bearer tokens from the importer.
* Validate basic or premium access server-side.
* Return only the manifest and assets allowed for that token.
* Avoid receiving visitor or member personal data.
* Preserve the plugin's local-import model after content is downloaded.

The current static folder may now be locked down with a reversible `.htaccess`
file because the protected endpoint has passed hosted Basic Import testing. The
API still reads from that same content directory through the server filesystem.

Current operating decision:

```text
Basic Import -> protected API manifest endpoint
Advanced Custom Import -> custom manifest endpoint or static folder
```

The built-in Basic Import action now uses the hardened API manifest endpoint:

```text
https://michiryu.com/michiryu-content-api/index.php?route=manifest
```

The prior static folder path remains useful as a fallback and for server-side
content storage, but should not be treated as the long-term public access model.

The hosted API should separate content libraries explicitly in configuration.
Basic and future premium content should use separate configured library records,
separate content roots, and explicit manifest allow-lists. The API should not
discover manifests by scanning content directories. Unknown libraries, unknown
manifest keys, invalid paths, missing token hashes, and other normal failures
should return JSON error responses.

Rate-limit-style safeguards should be added later at the hosting, CDN, or
entitlement-service layer when premium license validation exists.

Static-folder lockdown package:

```text
michiryu-content-lockdown/
```

Rollback is removing:

```text
/home1/bowerrx1/public_html/website_935ed7d0/michiryu-content/.htaccess
```

See:

```text
docs/adr/ADR-003-Content-Library-Access-Control.md
```

---

## Admin Approval

The plugin shall not silently download proprietary content on activation.

On setup, an administrator may be offered:

```text
Connect to MichiRyu Content Library?

This will download seasonal story text, images, and related metadata from
MichiRyu.com and store it on your WordPress site.

No visitor or member personal data is sent.

[Connect and Import Content]
[Use Basic Local Content]
```

Before import, the administrator must explicitly acknowledge:

```text
☑ I understand this will download MichiRyu copyrighted content to this site.
☑ I agree to use the content under the MichiRyu Content License.
☑ I understand no personal visitor data is transmitted.
```

---

## Local Storage

Imported content shall be stored locally in WordPress, such as:

* WordPress database tables or options for metadata and story records.
* WordPress Media Library for imported images and documents.
* Plugin-managed cache records for import manifests and version metadata.

After import, normal site rendering shall use the local WordPress copy.

The plugin shall not require constant API calls to MichiRyu.com for normal page
views.

---

## Content Updates

Default update mode:

```text
Manual updates only
```

Optional update modes:

```text
(•) Manual updates only
( ) Check monthly for updates
( ) Check every Sekki
```

Automatic update checks shall be opt-in.

---

## Advanced Settings

Advanced settings may include:

* Custom remote content URL.
* Optional content access token.
* Manual re-import action.
* Remove imported content action.
* Last import timestamp.
* Imported story, character, and image counts.

Advanced settings should be available for debugging and custom deployments, but
the default setup path should remain a simple import action.

---

## Privacy Requirements

Content import and update checks shall not send visitor or member personal data
to MichiRyu.

Requests may include plugin version, content manifest version, site language,
and license/account metadata if premium content is enabled in a later phase.

---

## Failure Requirements

If MichiRyu content import or update checks are unavailable:

Plugin continues operating.

No fatal errors.

No broken pages.

No missing widget failures.

Gracefully fall back to Local Provider.

Previously imported local content remains usable.

---

# 8. Map Architecture

The plugin shall provide:

## Generic Map Engine

Features:

Marker rendering

Path rendering

Zoom

Pan

Responsive layouts

Accessibility

Navigation

Popup framework

---

The plugin shall NOT include:

Yuki no Sato map artwork

Village geography

Village imagery

Story locations

Character locations

---

Those assets shall be loaded only through content providers.

---

# 9. Story Framework

The plugin may support:

Story cards

Story navigation

Story progress

Story reading interfaces

Bookmarking

Tracking

---

The plugin shall NOT contain:

Actual stories

Story text

Story images

Story metadata

Character narratives

---

All story content must originate from a content provider.

---

# 10. Repository Structure

## GPL Repository

```text
MichiRyu-Sekki
│
├── admin
├── api
├── assets
├── blocks
├── includes
├── providers
│   ├── ContentProviderInterface.php
│   ├── LocalProvider.php
│   ├── FileProvider.php
│   └── ImportProvider.php
├── templates
├── languages
├── tests
└── readme.txt
```

---

## Proprietary Repository

```text
MichiRyu-Content
│
├── stories
├── artwork
├── maps
├── icons
├── educational
├── api
└── metadata
```

Not distributed publicly.

Not GPL.

---

## Imported WordPress Storage

Imported MichiRyu content may be stored locally in WordPress after explicit
admin approval.

Imported storage may include:

* Plugin-owned database tables.
* WordPress options for manifests and import status.
* WordPress Media Library attachments.
* Local content indexes used by providers.

Imported content remains proprietary even when stored in WordPress.

---

# 11. Licensing Requirements

## Plugin

License:

GPL v2 or later

Contains:

Code only

No proprietary content

---

## Content

License:

Copyright © Russell Bowers / MichiRyu

All Rights Reserved

Contains:

Stories

Artwork

Maps

Images

Educational materials

Icons

Character content

Narrative content

---

# 12. Future Expansion

The architecture shall support future providers:

### MichiRyu Provider

Official imported content

### Community Provider

User-created content

### Educational Provider

Institutional content

### Premium Provider

Subscription content

---

No core plugin modification should be required to support future providers.

The official MichiRyu provider should prefer local imported content over
constant remote API calls.

---

# 13. Success Criteria

The project is considered compliant when:

✓ Plugin functions completely without proprietary content.

✓ GitHub repository contains no proprietary stories or artwork.

✓ Proprietary content can be added through a provider or admin-approved import.

✓ Plugin remains GPL-compliant.

✓ MichiRyu retains ownership of all creative works.

✓ Future WordPress.org distribution remains possible.

✓ Yuki no Sato remains a separate intellectual property asset.

---

## Implementation Priority

**Phase 1 (Immediate)**

1. Create Content Provider architecture.
2. Remove all stories from plugin.
3. Remove all artwork from plugin.
4. Remove all maps from plugin.
5. Create Local Provider.
6. Verify plugin functions standalone.

**Phase 2 (Future)**

1. Build MichiRyu Content Library import service. ✓
2. Add admin-approved import workflow. ✓
3. Store imported content locally in WordPress. ✓
4. Use manual content updates by default. ✓
5. Add optional content access token support. ✓
6. Point the built-in Basic Import action to the protected API manifest. ✓
7. Provide import status, error handling, re-import controls, and remove-imported-content controls. ✓
8. Add opt-in update checks.

**Phase 3 (Future)**

1. Hide basic content URL behind a simple Import Basic MichiRyu Content action. ✓
2. Keep custom URL/token controls as advanced settings. ✓
3. Add user-specific premium license token scaffold. ✓
4. Lock down public browser access to the static content folder. ✓
5. Add server-side entitlement validation for premium manifests.
6. Add community content providers.
7. Add subscription services.
8. Add educational content marketplace.

This approach turns MichiRyu-Sekki into a reusable seasonal calendar platform, while keeping Yuki no Sato and all associated creative works exclusively under your control.
