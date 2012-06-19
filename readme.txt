=== Cases. Kernel. Restrict Access ===
Contributors: SergeyBiryukov
Tags: cases, access, users
Requires at least: 3.0
Tested up to: 3.4
Stable tag: 0.2.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

ACM Cases permissions setup.

== Description ==

Only the case initiator, responsible person or participant is allowed to view, edit or comment on the case.

== Installation ==

1. Upload `cases-restrict-access` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.

== Changelog ==

= 0.2.1 =
* Fixed multiple output of the same cases
* Fixed SQL error when meta_query parameter is specified
* Restored access to all cases for site administrators

= 0.2 =
* Case queries only return the cases which the current user has access to
* Fixed membership check in cases with multiple participants

= 0.1.2 =
* Fixed the ability to create new cases

= 0.1.1 =
* Fixed display of custom "Add New" menu items when viewing a case

= 0.1 =
* Initial release
