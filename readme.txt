=== WP Blame ===
Contributors: jfcby
Tags: logs, security, audit, history
Requires at least: 4.0
Tested up to: 4.9
Requires PHP: 5.4
License: GNU GPL v3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Keep a record of the activity on your website.

== Description ==

= About =

WP Blame lets you keep a record of everything that has happened on your website by logging the actions of your users in a simple but useful table of data.

= Developers =

There are currently no hooks available for this plugin as of yet, however whilst it is discouraged, you can log actions using the `WPB_Log_Hooks::save_new_log` function.

== Screenshots ==

1. An example of the logs table.

== Installation ==

1. Download, unzip and upload the package to your plugins directory.
2. Log into the dashboard and activate within the plugins page.
3. Review your website logs under Tools > Logs.

== Frequently Asked Questions ==

= What kind of things will be logged? =

Almost anything that can be logged, is logged. This includes but is not limited to posts, terms, media and users as well as general options.

= Can I log a custom action by another plugin? =

You can, but it's not advised as the Log API has not bee fully developed yet and may not work very well with other plugins. `WPB_Log_Hooks` is the class that handles logging.

= Can I only log particular users? =

You can add usernames to a whitelist of people who's actions should not be logged on the website.

== Changelog ==

= 2.1.3 = 

* Updated author information - Removed Daniel James danieltj

= 2.1.2 =

* Updated for new developer to maintain.

= 2.1.1 =

* Updated the plugin translations to be in en_US by default.
* Fixed a bug relating to terms being shown in the log table.
* Minor improvements to some translation strings.
* Minor updates to the plugin readme.txt file.

= 2.1 =

* Fixed a bug where the per page option wasn't added on install.
* Fixed a bug with the item column being blank after deletion.
* Fixed an untranslated string for the logs per page option.
* Minor improvements to a variety of core functions.

= 2.0 =

* Major rewrite of the entire plugin code.
* Changed the license from GNU GPL v2 to v3.
