=== Comment Reply Email Notification ===
Contributors: Gustavo H. Mascarenhas Machado
Donate link: https://guh.me/
Tags: comment, email, reply, notification
Requires at least: 4.4.0
Tested up to: 4.8
Stable tag: 1.3.3
License: BSD
License URI: http://opensource.org/licenses/BSD-3-Clause

== Description ==

This plugin notifies a comment author via email when someone replies to his comment.

== Requirements ==
* PHP 5.6+
* Wordpress 4.4+

== Installation ==

How to install the plugin:

1. Upload the ZIP file to your Wordpress installation directory
2. Extract the ZIP file contents to the /wp-content/plugins directory.
3. Activate the plugin.
4. For better results, setup a plugin to send emails using SMTP.

== Changelog ==
= 1.3.3 =
* Updated translations and translation domain
= 1.3.2 =
* Fixed email content type filter interfering with other emails
* Removed asterisk from comment form subscription checkbox
= 1.3.1 =
* Fixed missing variable on checkbox template
* Fixed notification being sent to the comment author when he replies his own comment
= 1.3.0 =
* Fixed opt-in checkbox for logged in users
* Added Russian and Ukrainian translations (thanks to Oleh Astappiev!)
* Added unsubscribe link to email
= 1.2.0 =
* Added opt-in checkbox to comment form
= 1.1.1 =
* Fixed typo in hook
= 1.1.0 =
* Added ES and PT-BR translations
* Added build script
= 1.0.0 =
* First release