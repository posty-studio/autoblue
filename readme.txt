=== Autoblue ===
Contributors: danielpost
Tags: social, bluesky, auto, share, post
Stable tag: 0.0.5
Requires at least: 6.6
Tested up to: 6.7
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

With Autoblue, you can automatically share new posts to Bluesky from your WordPress site.

== Description ==

With Autoblue, you can share your WordPress posts to Bluesky automatically and effortlessly. It uses your featured image to make your posts stand out, and you can add a custom message to truly make each post your own. In addition, you can display replies to your Bluesky posts on your website.

== External services ==

This plugin connects to the Bluesky API to retrieve user information and share posts to Bluesky. It's needed to connect your Bluesky account to your WordPress site, share your posts to Bluesky, and display likes and replies on your site.

When you share a post, it sends the post title, featured image, and custom message to the Bluesky API.

This service is provided by "Bluesky PBC": [terms of use](https://bsky.social/about/support/tos), [privacy policy](https://bsky.social/about/support/privacy-policy).

=== Stay connected ===

* [Visit Autoblue website](https://autoblue.co)
* [View on GitHub](https://github.com/posty-studio/autoblue)
* [Follow on Bluesky](https://bsky.app/profile/danielpost.com/)

== Frequently Asked Questions ==

= Is Autoblue free? =

Yes, Autoblue is completely free. There will be a premium version in the future, but the core functionality will always be free (and is already super useful!).

= Does Autoblue support the classic editor? =

Currently, Autoblue only works in the block editor. It uses a lot of modern WordPress components that are not available in the classic editor.

== Screenshots ==

1. Easily connect your Bluesky account to your WordPress site and start sharing.
2. Keep track of everything that Autoblue does.

== Changelog ==

= 0.0.5 =
* Feature: Add filter for setting a custom share message
* Fix: Excerpts are now trimmed correctly before being shared

= 0.0.4 =
* Fix: Script translations are now loaded properly (props [@imath](https://github.com/imath))
* Fix: Shared posts no longer trigger warning in editor about invalid type (props [@imath](https://github.com/imath))
* Fix: Custom table for logs now works with older versions of MySQL and MariaDB (props [@imath](https://github.com/imath))

= 0.0.3 =
* Fix: Autoblue now supports PHP 7.4 again.

= 0.0.2 =
* Delete Autoblue data when the plugin is uninstalled.

= 0.0.1 =
* Initial release.
