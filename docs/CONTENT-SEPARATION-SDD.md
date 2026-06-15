# Software Design Document (SDD)

## MichiRyu-Sekki Content Separation Architecture

### Version 1.0

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

# 7. Remote Content Provider

Future implementation.

Provider name:

MichiRyu Content Service

Purpose:

Supply proprietary content.

Potential content:

Stories

Artwork

Maps

Character information

Educational materials

Premium seasonal content

---

## Failure Requirements

If remote content is unavailable:

Plugin continues operating.

No fatal errors.

No broken pages.

No missing widget failures.

Gracefully fall back to Local Provider.

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
│   └── RemoteProvider.php
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

Official content

### Community Provider

User-created content

### Educational Provider

Institutional content

### Premium Provider

Subscription content

---

No core plugin modification should be required to support future providers.

---

# 13. Success Criteria

The project is considered compliant when:

✓ Plugin functions completely without proprietary content.

✓ GitHub repository contains no proprietary stories or artwork.

✓ Proprietary content can be added through a provider.

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

1. Build MichiRyu Content Service.
2. Create Remote Provider.
3. Connect proprietary content.
4. Add caching and synchronization.

**Phase 3 (Future)**

1. Premium content support.
2. Community content providers.
3. Subscription services.
4. Educational content marketplace.

This approach turns MichiRyu-Sekki into a reusable seasonal calendar platform, while keeping Yuki no Sato and all associated creative works exclusively under your control.
