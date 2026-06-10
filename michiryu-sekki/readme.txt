=== MichiRyu-Sekki-Calendar ===
Contributors: michiryu
Tags: sekki, seasons, japan, shortcode, widget, block, ikebana
Requires at least: 6.0
Tested up to: 6.5
Requires PHP: 7.4
Stable tag: 1.2.13
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Display the current Japanese 24 Sekki solar term with shortcode, widget, block, card, banner, optional 72 ko microseason display, and ikebana views.

== Description ==

MichiRyu-Sekki-Calendar is a lightweight WordPress plugin for showing the current Japanese 24 Sekki season. Sekki are best described as 24 solar terms: traditional divisions of the year based on the sun's movement, about 15 days each.

The plugin includes display plans for minimal naming, standard seasonal context, ikebana material suggestions, visual banners, and educational text.

== Usage ==

Default shortcode:

[michiryu_sekki]
[michiryu_sekki show_ko="true"]
[michiryu_sekki carousel="true"]
[michiryu_sekki carousel="true" show_ko="true"]
[michiryu_sekki show_date_stamp="true"]
[michiryu_sekki signature_position="bottom-right"]
[michiryu_sekki style="banner" signature_size="small"]

Seasonal journey entry:

[michiryu_journey]
[michiryu_journey variant="widget"]

Stable story reader:

[michiryu_story]
[michiryu_story ko="25"]
[michiryu_story story="Sekki_09_Boshu_Ko_25_MantisHatch"]
[michiryu_story sekki="boshu"]

Display style overrides:

[michiryu_sekki style="text"]
[michiryu_sekki style="compact"]
[michiryu_sekki style="banner"]
[michiryu_sekki style="banner" show_ko="true"]
[michiryu_sekki style="image_card"]
[michiryu_sekki style="ikebana"]

Seasonal plan overrides:

[michiryu_sekki plan="minimal"]
[michiryu_sekki plan="standard"]
[michiryu_sekki plan="ikebana"]
[michiryu_sekki plan="banner"]
[michiryu_sekki plan="educational"]
[michiryu_sekki plan="educational" show_ko="true"]

== Settings ==

Go to MichiRyu-Sekki-Calendar > Sekki to set the core journey defaults:

* Default display style
* Default Seasonal Plan
* Map progression style
* Story reader behavior
* Current season map highlight
* Signature opacity

Advanced display settings remain available for legacy widgets, older shortcodes, custom map page setups, and detailed image/icon controls.

Advanced settings include:

* Whether to show kanji, romanized names, English names, date ranges, descriptions, Sekki images, ko icons, and ikebana materials
* Whether to use bundled images
* Whether to show the current date stamp over Sekki images
* Signature position: bottom-right, bottom-left, top-right, or top-left
* Signature size: small, medium, or large
* Signature opacity: 0.5 to 1.0
* Custom fallback image URL
* Image style: square, banner, circle, or none
* Icon style: outline, circle, or none
* Map progression style: seasonal compass, slim seasonal timeline, or disabled
* Optional custom CSS

== Images ==

Add Sekki artwork using this structure:

assets/images/sekki/Sekki_01_Risshun.jpg
assets/images/sekki/Sekki_02_Usui.png
...
assets/images/sekki/Sekki_24_Daikan.jpg

Add ko icons using this structure:

assets/images/ko/KO_01_EastWindMeltsIce.svg
assets/images/ko/KO_02_BushWarblerSings.svg
...
assets/images/ko/KO_72_HensBeginLaying.svg

The plugin stores filenames in the data model, checks whether each file exists before rendering it, and falls back to text-only output when artwork is missing. Sekki artwork may be JPG, JPEG, or PNG as long as the basename matches, such as Sekki_01_Risshun.jpg or Sekki_01_Risshun.png.

== Future Structure ==

The data model includes the 72 ko micro-seasons and remains prepared for local seasonal notes, class and event integration, Boston ikebana seasonal material suggestions, and automatic current season inspiration.

== Changelog ==

= 1.2.12 =
* Test build with Ko story panel action updates.

= 1.2.11 =
* Let the reader's two-row Ko story path fill the available width instead of bunching to the left.

= 1.2.10 =
* Restore the journey card to a simple reading progress bar and keep the reader's Ko story path as a compact two-row navigation strip.

= 1.2.9 =
* Replace the abstract reading progress bar with a 72-step Ko story path that fills read stories and links each step to its reader.

= 1.2.8 =
* Make Continue Journey resume from the last story read in this browser instead of jumping to the first unread story.

= 1.2.7 =
* Stack the current Ko under the current Sekki in the journey card, use Ko artwork in the reader when available, and add an admin setting for pop-out or inline story reader behavior.

= 1.2.6 =
* Add seasonal image with signature to the journey card, add a compact journey widget variant, and open journey stories in a pop-out reader.

= 1.2.5 =
* Change the journey card progress to personal stories-read progress and route Continue Journey to the next unread story.

= 1.2.4 =
* Simplify the admin settings screen around journey defaults and move legacy display controls into an Advanced section.

= 1.2.3 =
* Add Restart Journey progress clearing, a close story control, and reader image signature placement.

= 1.2.2 =
* Add local browser story progress, read-count progress display, and a Journey Map entry after the story reader.

= 1.2.1 =
* Connect the seasonal journey card to the story reader and add journey position, Previous Story, Next Ko or Next Season, and Continue Journey navigation.

= 1.2.0 =
* Add a stable story reader shortcode with season context, Ko context, story body, character spotlight, ikebana reflection, and previous/next story navigation.

= 1.0.24 =
* Add the first seasonal journey shortcode with current Sekki, current Ko, story teaser, progress placeholder, and Continue Journey placeholder.

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
* Adjust the seasonal compass so the selected Sekki label stays on the arc and Ko numbers sit outside the selected point.

= 1.0.13 =
* Refine the default map progression into a compact seasonal compass with a partial arc, selected-season label, and ko dots.

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
* Initial release.
