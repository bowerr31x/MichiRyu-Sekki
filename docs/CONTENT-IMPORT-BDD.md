# Content Import BDD

## Feature: MichiRyu Content Library Import

The public GPL plugin works with basic local Sekki and Ko data. Proprietary
MichiRyu content is imported only after explicit administrator approval and is
then served from local WordPress storage.

## Scenario: Plugin Activates Without Imported Content

Given the plugin has just been installed
When the administrator activates the plugin
Then the plugin uses the local provider
And the site can render the basic seasonal calendar
And no proprietary content is downloaded automatically

## Scenario: Administrator Is Offered A Content Import Choice

Given the plugin is active
When the administrator opens the MichiRyu Content Library screen
Then the administrator sees an option to connect and import content
And the administrator sees an option to use basic local content
And the screen explains that seasonal stories, images, and metadata will be
downloaded from MichiRyu.com and stored on the WordPress site
And the screen states that no visitor or member personal data is sent

## Scenario: Administrator Must Approve Licensing Before Import

Given the administrator chooses to import MichiRyu content
When the import confirmation is shown
Then the administrator must acknowledge that copyrighted MichiRyu content will
be downloaded to the site
And the administrator must agree to the MichiRyu Content License
And the administrator must acknowledge that no visitor personal data is
transmitted
And the import cannot start until those acknowledgements are complete

## Scenario: Imported Content Is Stored Locally

Given the administrator has approved the import
When the import completes
Then story records and metadata are stored locally in WordPress
And images or documents are stored locally in the WordPress Media Library or
another approved local WordPress storage area
And normal frontend rendering uses the local WordPress copy
And normal frontend rendering does not require constant API calls to MichiRyu.com

## Scenario: Manual Updates Are The Default

Given the administrator opens the MichiRyu content settings
When the administrator views content update settings
Then Manual updates only is selected by default
And monthly update checks are available only as an opt-in option
And every-Sekki update checks are available only as an opt-in option

## Scenario: Administrator Imports From A Remote Content URL

Given the administrator has entered a remote content URL
And the administrator has saved all import acknowledgements
When the administrator opens the MichiRyu Content Library settings
Then the Connect and Import Content action is enabled
When the administrator starts the import
Then the plugin downloads featured-content.json and images.json
And the plugin downloads referenced images
And the plugin stores the imported content under the local WordPress uploads
directory
And the imported content provider can render the local copy without constant
remote requests

## Scenario: Import Action Is Disabled Until Ready

Given the administrator has not entered a remote content URL
Or the administrator has not saved all import acknowledgements
When the administrator opens the MichiRyu Content Library settings
Then the Connect and Import Content action is disabled
And the screen explains what is required before importing

## Scenario: MichiRyu Service Is Unavailable

Given the administrator attempts an import or update
When MichiRyu.com is unavailable
Then the plugin shows a recoverable error
And the basic local calendar remains usable
And previously imported content remains usable
And no frontend page fails because the service is unavailable

## Scenario: Imported Content Remains Separately Licensed

Given content has been imported into WordPress
When the administrator views content licensing information
Then the plugin code license is shown as GPL-2.0-or-later
And downloaded MichiRyu content is shown as copyright Russell Bowers / MichiRyu
And the content is described as all rights reserved unless otherwise stated
