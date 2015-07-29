=== Kopa Framework ===
Contributors: kopatheme
Tags: framework, tool, feature, theme-options, sidebar-manager, layout-manager, custom-layouts
Donate link: 
Requires at least: 3.8
Tested up to: 4.0
Stable tag: 1.0.7
License: GPLv2 or later

A WordPress framework by Kopatheme

== Description ==

The Kopa Framework plugin is an easy way to get theme options, sidebar manager, layout manager and custom layouts feature to your WordPress site.

== Installation ==

1. Upload the files to the /wp-content/plugins/kopa-framework/ directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to Appearance->Theme Options and use.

== Changelog ==

= 1.0.8 =
* Updated: FontAwesome from 4.0.3 to 4.3.0
* Fix: move include Master widget "Kopa_Widget" from hook "widgets_init" to __construct 
* Add: filter kopa_widget_form_field_[field_type]

= 1.0.7 =
* Updated: 'validate' attribute of textarea control arguments to save textarea control value without validating

= 1.0.6 =
* Placeholder for font size field of select_font control

= 1.0.5 =
* Add support for register metabox
* Types are supported by metabox (text, number, url, password, email, select, multiselect, checkbox, multicheck, textarea, radio, upload )
* Updated: sanitize for select_font, make sure all attributes are available

= 1.0.4 =
* Add support for widget upload control
* Add media uploader script (kopa_media_uploader)

= 1.0.3 =
* Allows some html tags (em, strong, code, a, abbr, acronym) in 'desc'(description) attribute of option arguments

= 1.0.2 =
* Removed: font-awesome from custom layout style

= 1.0.1 =
* Sanitize for number option type

= 1.0.0 =
* First version