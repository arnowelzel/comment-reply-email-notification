=== Comment Reply Email Notification ===
Contributors: Gustavo H. Mascarenhas Machado
Donate link: https://guh.me/
Tags: comment, email, reply, notification
Requires at least: 4.4.0
Tested up to: 4.9.8
Stable tag: 1.8.0
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

== Warning ==

This plugin uses the "wp_insert_comment" hook, therefore, everytime a comment is created, a notification is likely to be sent. If you are importing comments into your blog, it's a good idea to disable this plugin.

== Customizing the email template

To customize the email template, copy the "templates" folder to your theme folder. The plugin will look for templates on the "/wp-content/themes/[THEME]/templates/cren/" folder; if a custom template is not found, then it will fallback to the default template.

Templates folder on GitHub: https://github.com/guhemama/worpdress-comment-reply-email-notification/tree/master/templates

== Changing the subscription checkbox label ==

The checkbox label can be changed with the `cren_comment_checkbox_label` filter. This way you can update the text to your taste and keep the plugin updated.

== Changing the GDPR checkbox label ==

The GDPR checkbox label can be changed with the `cren_gdpr_checkbox_label` filter. This way you can update the text to your taste and keep the plugin updated.

== Buy me a coffee/beer ==

Do you like this plugin? Support it by buying me some human-fuel - coffee on weekdays, and beer on weekends. ;)

https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=HG8SRFWT4XY58

== Changelog ==
= 1.8.0 =
* Added Korean translation
* Fixed cren_gdpr_checkbox_label filter bug
= 1.7.1 =
* Fixed blog title encoding
= 1.7.0 =
* Fixed bug where subscription checkbox was always checked
* Added CN translation (thanks hsu1943)
= 1.6.1 =
* Fixed default settings not being set
= 1.6.0 =
* Added Turkish translation (thanks Bünyamin Yildirim!)
* Added filters to the checkbox label
* Added GDPR box
* Added admin settings page
* Added HTML entities decode to email title
= 1.5.0 =
* Added Italian translation (thanks Giacomo Bellisi!)
* Added Hebrew translation (thanks Lea Cohen!)
= 1.4.4 =
* Fixed undefined variable
* Updated French translation
= 1.4.3 =
* Updated Spanish translation
* Added German translation (thanks to Nathanael Dalliard!)
= 1.4.2 =
* Added french translation
= 1.4.1 =
* Fixed unsubscribe page template
= 1.4.0 =
* Added the ability to use a custom email template
* Added plugin rendering options
* Fixed unsubscribe link
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