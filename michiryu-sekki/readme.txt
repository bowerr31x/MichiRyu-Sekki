=== MichiRyu-Sekki-Calendar ===
Contributors: michiryu
Plugin URI: https://michiryu.com
Author URI: https://michiryu.com
Tags: sekki, seasons, japan, shortcode, widget
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.3.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Display the current Japanese 24 Sekki season, current Ko microseason, and provider-ready seasonal content views.

== Description ==

MichiRyu-Sekki-Calendar displays the current Japanese 24 Sekki solar term in
WordPress. The main shortcode can show the current Sekki, current Ko
microseason, date stamp, and optional content-provider enhancements.

Sekki are 24 traditional divisions of the solar year. Each Sekki lasts about
fifteen days and is further divided into three Ko microseasons.

The plugin is designed for MichiRyu seasonal storytelling, ikebana study, and
simple seasonal display on WordPress sites.

== Shortcodes ==

Main seasonal experience:

[michiryu_sekki]

Optional journey entry:

[michiryu_journey]
[michiryu_journey variant="widget"]

Story reader:

[michiryu_story]
[michiryu_story ko="25"]
[michiryu_story sekki="boshu"]

Seasonal map:

[michiryu_sekki_map]

== Display Styles ==

The main shortcode supports display style overrides:

[michiryu_sekki style="text"]
[michiryu_sekki style="small"]
[michiryu_sekki style="standard_vertical"]
[michiryu_sekki style="standard_horizontal"]
[michiryu_sekki style="banner_tall"]
[michiryu_sekki style="banner_narrow"]

== Settings ==

Go to MichiRyu-Sekki-Calendar in WordPress Admin to set core defaults:

* Default display style for [michiryu_sekki], widgets, and blocks.
* Show Ko microseason section.
* Show Japanese kanji, romanized name, English name, Sekki image, ikebana
  materials and theme, date stamp, and story teaser.
* Dedicated map page URL.
* Optional custom CSS.
* Optional creator website link in the About panel. This public-facing link is off by default.
* Content Provider Status showing the active source, content counts, and whether map or signature images are supplied.
* Admin-approved Basic MichiRyu Content import with local WordPress storage.
* Advanced custom content source settings for support, testing, and self-hosted content libraries.
* Manual content updates through admin-approved import actions.

Recommended setup: use [michiryu_sekki] for the main experience. It shows the
current Sekki and current Ko.

For the full Yuki no Sato experience, import Basic MichiRyu Content from the
admin screen. Story, character, image, and map features require imported
content.

Optional after content import: [michiryu_story] [michiryu_sekki_map]

== Content Providers and Licensing ==

The plugin package contains GPL software only. Proprietary stories, artwork,
maps, icons, PDFs, educational materials, and Yuki no Sato content are not
included in this plugin package.

Plugin PHP, JavaScript, and CSS code is licensed under GPLv2 or later.

Story, map, image, character, and educational content should be supplied through
a Content Provider and licensed separately from the plugin code.

See CONTENT-LICENSE for more information.

== Installation ==

1. Upload the michiryu-sekki folder to wp-content/plugins, or install the plugin
   ZIP through WordPress Admin.
2. Activate MichiRyu-Sekki-Calendar.
3. Add [michiryu_sekki] to a page, post, widget area, or block-supported location.
4. Configure defaults under MichiRyu-Sekki-Calendar in WordPress Admin.

== Frequently Asked Questions ==

= What is the main shortcode? =

Use [michiryu_sekki] for the primary seasonal experience.

= Can I show only the story reader or map? =

Yes. Use [michiryu_story] for the story reader and [michiryu_sekki_map] for the map.

= Is the creator link required on the public site? =

No. The About panel can show the plain text credit "created by MichiRyu.com"
without a hyperlink. The public creator website link is optional and off by
default.

= Can I reuse the artwork or stories outside the plugin? =

No proprietary artwork or stories are included in this plugin package. Content
provided by a separate Content Provider follows that provider's license terms.

= Does uninstall remove imported content? =

Yes. Uninstalling the plugin removes plugin settings, import status, and the
local imported content copy stored under WordPress uploads.

== Changelog ==

= 1.3.0 =
* Add admin-approved Basic MichiRyu Content import through the protected MichiRyu Content API.
* Store imported stories, characters, image references, map images, and signature images locally in WordPress uploads.
* Keep proprietary MichiRyu content outside the GPL plugin package and document the content-provider separation model.
* Add a clearer admin settings layout with content status, import summary, content actions, and developer diagnostics.
* Point the built-in Basic Import flow to the branded MichiRyu API endpoint.
* Harden remote imports by limiting file types, file sizes, unsafe URLs, and token forwarding.
* Add helper packages and documentation for the external content API and static content lockdown.

= 1.2.20 =
* Add the Content Provider architecture for separating GPL plugin software from proprietary content.
* Make the GPL-safe local provider the default plugin content source.
* Add admin Content Provider Status diagnostics for local and external provider testing.
* Add a file content provider for JSON-based private content library testing outside the plugin folder.
* Show file provider path validity in the admin status panel.
* Document the future admin-approved content import model for proprietary MichiRyu content.
* Add saved admin settings for future content import consent and update mode.

= 1.2.19 =
* Test build with shorter narrow banner image and longer story preview.

= 1.2.18 =
* Test build with tall and narrow banner display options.

= 1.2.17 =
* Test build with bulleted seasonal materials and stacked Ko title lines.

= 1.2.16 =
* Test build with revised display styles and ikebana materials/theme setting.

= 1.2.15 =
* Test build with Ko name display settings and story teaser setting.

= 1.2.14 =
* Test build with restored core Sekki display settings on the main admin page.

= 1.2.12 =
* Test build with Ko story panel action updates.

= 1.2.11 =
* Let the reader's two-row Ko story path fill the available width instead of bunching to the left.

= 1.2.10 =
* Restore the journey card to a simple reading progress bar and keep the
  reader's Ko story path as a compact two-row navigation strip.

= 1.2.9 =
* Replace the abstract reading progress bar with a 72-step Ko story path that
  fills read stories and links each step to its reader.

= 1.2.8 =
* Make Continue Journey resume from the last story read in this browser instead of jumping to the first unread story.

= 1.2.7 =
* Stack the current Ko under the current Sekki in the journey card, use Ko
  artwork in the reader when available, and add an admin setting for pop-out or
  inline story reader behavior.

= 1.2.6 =
* Add seasonal image with signature to the journey card, add a compact journey
  widget variant, and open journey stories in a pop-out reader.

= 1.2.5 =
* Change the journey card progress to personal stories-read progress and route
  Continue Journey to the next unread story.

= 1.2.4 =
* Simplify the admin settings screen around journey defaults and move legacy display controls into an Advanced section.

= 1.2.3 =
* Add Restart Journey progress clearing, a close story control, and reader image signature placement.

= 1.2.2 =
* Add local browser story progress, read-count progress display, and a Journey Map entry after the story reader.

= 1.2.1 =
* Connect the seasonal journey card to the story reader and add journey
  position, Previous Story, Next Ko or Next Season, and Continue Journey
  navigation.

= 1.2.0 =
* Add a stable story reader shortcode with season context, Ko context, story
  body, character spotlight, ikebana reflection, and previous/next story
  navigation.

= 1.0.24 =
* Add the first seasonal journey shortcode with current Sekki, current Ko, story
  teaser, progress placeholder, and Continue Journey placeholder.

= 1.0.23 =
* Center the active slim timeline item after the map opens and prevent duplicate carousel items from appearing active.

= 1.0.22 =
* Make the slim seasonal timeline behave like a continuous carousel at the beginning and end of the year.

= 1.0.21 =
* Rebuild story cache when it does not include all Markdown stories and improve map path visibility.

= 1.0.20 =
* Add a dotted directional map path from previous season to selected season to next season.

= 1.0.19 =
* Reorder the mobile map experience around reading first: story, season details, progression, then map.

= 1.0.18 =
* Reorder Ko story reading flow to story, character spotlight, ikebana reflection, then navigation.

= 1.0.17 =
* Add a brief current-season settling state when the map opens to prevent accidental season confusion.

= 1.0.16 =
* Move season detail previous and next buttons below details when a top progression control is enabled.

= 1.0.15 =
* Hide the slim timeline scrollbar and center the selected Sekki in the visible row.

= 1.0.14 =
* Adjust the seasonal compass so the selected Sekki label stays on the arc and
  Ko numbers sit outside the selected point.

= 1.0.13 =
* Refine the default map progression into a compact seasonal compass with a
  partial arc, selected-season label, and ko dots.

= 1.0.12 =
* Add map progression controls with circular year wheel, slim timeline, or disabled admin option.

= 1.0.11 =
* Confirm complete Ko story coverage through all 72 microseasons.
* Bump package version after story and map experience updates.

= 1.0.3 =
* Scale the signature and hanko overlay by image height so it fits within the visible artwork.
* Shrink right-side overlays when the current date stamp is shown.

= 1.0.2 =
* Always show the signature and hanko overlay on Sekki images.
* Remove the signature visibility setting.

= 1.0.1 =
* Focus carousels on the current season and microseason.
* Add an optional current date stamp overlay for Sekki images.
* Improve current-season carousel highlighting.

= 1.0.0 =
* Initial production release of the MichiRyu-Sekki seasonal display experience.
* Includes the main Sekki shortcode, seasonal display settings, and
  GPLv2-or-later plugin code licensing.
