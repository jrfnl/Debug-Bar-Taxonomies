=== Debug Bar Taxonomies ===
Contributors: jrf
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=995SSNDTCVBJG
Tags: debugbar, debug-bar, Debug Bar, Taxonomies, Debug Bar Taxonomies, Custom Taxonomy, Custom Taxonomies
Requires at least: 3.4
Tested up to: 4.8
Stable tag: 1.1
Depends: Debug Bar
Requires PHP: 5.2.4
License: GPLv2

Debug Bar Taxonomies adds a new panel to the Debug Bar with detailed information about registered taxonomies. Requires "Debug Bar" plugin.

== Description ==

Debug Bar Taxonomies adds a new panel to the Debug Bar that displays detailed information about the registered taxonomies for your site.

= Important =

This plugin requires the [Debug Bar](https://wordpress.org/plugins/debug-bar/) plugin to be installed and activated.

Also note that this plugin should be used solely for debugging and/or in a development environment and is not intended for use on a production site.

***********************************

If you like this plugin, please [rate and/or review](https://wordpress.org/support/view/plugin-reviews/debug-bar-taxonomies) it. If you have ideas on how to make the plugin even better or if you have found any bugs, please report these in the [Support Forum](https://wordpress.org/support/plugin/debug-bar-taxonomies) or in the [GitHub repository](https://github.com/jrfnl/Debug-Bar-Taxonomies/issues).



== Frequently Asked Questions ==

= Can it be used on live site ? =
This plugin is only meant to be used for development purposes, but shouldn't cause any issues if run on a production site.


= What are taxonomies ? =
>Basically, a taxonomy is a way to group things together. [...] In WordPress, a "taxonomy" is a grouping mechanism for some posts (or links or custom post types).
> The names for the different groupings in a taxonomy are called terms. [...] As an example from WordPress, a category or tag is a term.
>
>Since WordPress 2.3, you've been able to create your own custom taxonomies, but these have been a rarely used feature of WordPress until Version 2.9. In truth, they are an extremely powerful way to group various items in all sorts of ways.

[More information in the Codex](https://codex.wordpress.org/Taxonomies)


= Why won't the plugin activate ? =
Have you read what it says in the beautifully red bar at the top of your plugins page ? As it says there, the Debug Bar plugin needs to be active for this plugin to work. If the Debug Bar plugin is not active, this plugin will automatically de-activate itself.


== Changelog ==

= 1.1 (2017-07-10) =
* Improved usability of the admin notice in case the Debug Bar plugin is not active.
* The plugin will now add itself to the list of "recently active" plugins if it self-deactivates bcause the Debug Bar plugin is not active.
* Defer to just in time loading of translations for WP > 4.5.
* Updated the pretty print dependency to v1.7.0.
* Minor housekeeping.
* The minimum supported WP version is now 3.4, in line with the 0.9 version of the Debug Bar.
* Tested & found compatible WP 4.8.

= 1.0 (2016-04-27) =
* Initial release


== Upgrade Notice ==

= 1.0 =
* Initial release


== Installation ==

1. Install Debug Bar if not already installed (https://wordpress.org/plugins/debug-bar/)
1. Extract the .zip file for this plugin and upload its contents to the `/wp-content/plugins/` directory. Alternatively, you can install directly from the Plugin directory within your WordPress Install.
1. Activate the plugin through the "Plugins" menu in WordPress.


== Screenshots ==
1. Debug Bar Taxonomies - Standard Taxonomy Properties view
1. Debug Bar Taxonomies - Custom Taxonomy Properties view
1. Debug Bar Taxonomies - Capabilities view
1. Debug Bar Taxonomies - Defined labels view

