=== Comment Reply Email Notification ===
Contributors: awelzel, guhemama
Tags: comment, email, reply, notification
Requires at least: 4.4.0
Tested up to: 6.7
Stable tag: 1.34.0
Donate link: https://paypal.me/ArnoWelzel
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

== Description ==

This plugin allows visitors to subscribe to get answers to their comments via e-mail.

== Installation ==

How to install the plugin:

1. Extract the contents of the package to the `/wp-content/plugins/comment-reply-email-notification` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. For better results, setup a plugin to send emails using SMTP.

== Warning ==

This plugin uses the "wp_insert_comment" hook, therefore, everytime a comment is created, a notification is likely to be sent. If you are importing comments into your blog, it's a good idea to disable this plugin.

== Sending e-mails does not work? ==

The plugin uses the standard WordPress e-mail function. If you have problems getting e-mails sent, you might try using plugins like https://wordpress.org/plugins/wp-mail-smtp/ to improve sending e-mails from your site.

== Customizing the layout of the checkboxes ==

The label next to the checkboxes don't contain a whitespace. Depending on your theme you might want to add a custom style like this to get a space between the checkbox and the label:

`
input#cren_subscribe_to_comment, input#cren_gdpr {
  margin-right: 0.5em;
}
`

The plugin does not add this style be default as it depends on your theme if this is neccessary.

== Customizing the email template ==

To customize the email template, copy the "templates" folder to your theme folder (a child theme should be used to avoid losing the custom templates when the theme is updated). The plugin will look for templates on the "/wp-content/themes/[THEME]/templates/cren/" folder; if a custom template is not found, then it will fallback to the default template.

Templates folder on GitHub: https://github.com/arnowelzel/worpdress-comment-reply-email-notification/tree/master/templates

== Changing the subscription checkbox label ==

The checkbox label can be changed with the `cren_comment_checkbox_label` filter. This way you can update the text to your taste and keep the plugin updated.

== Changing the GDPR checkbox label ==

The GDPR checkbox label can be changed with the `cren_gdpr_checkbox_label` filter. This way you can update the text to your taste and keep the plugin updated.

== Modifiying HTML output ==

Using the filters `cren_gdpr_checkbox_html` and `cren_comment_subscribe_html` you can modify the HTML output of the checkboxes if needed.

Example:

`add_filter('cren_gdpr_checkbox_html', function(string $html_output, string $label_text, string $privacy_policy_url): string {
    $html_output = '<div class="comment-form-gdpr-consent form-check mb-3"><input id="cren_gdpr" class="form-check-input" name="cren_gdpr" type="checkbox" value="yes" required checked><label for="cren_gdpr" class="form-check-label">' . $label_text . '<span class="text-danger fw-bold">*</span> (<a href="' . $privacy_policy_url . '" title="Privacy Policy" target="_blank" rel="internal">Privacy Policy</a>)</label></div>';

    return $html_output;
}, 10, 3);

add_filter('cren_comment_subscribe_html', function(string $html_output, string $label_text, bool $checked_default): string {
    $checked = $checked_default ? 'checked' : '';
    $html_output = '<div class="comment-form-email-consent form-check mb-3"><input id="cren_subscribe_to_comment" class="form-check-input" name="cren_subscribe_to_comment" type="checkbox" value="on" ' . $checked . '><label for="cren_subscribe_to_comment" class="form-check-label">' . $label_text . '</label></div>';

    return $html_output;
}, 10, 3);`

== Changelog ==

= 1.34.0 =

* Updated WordPress compatibility information.

= 1.33.0 =

* Updated WordPress compatibility information.

= 1.32.0 =

* Updated WordPress compatibility information.

= 1.31.0 =

* Added option to set a custom from address for outgoing e-mails.

= 1.30.0 =

* Updated WordPress compatibility information.

= 1.29.0 =

* Changed loading of translations so the custom files provided by LOCO Translate also work.
* Added option for custom e-mail subject for comment notifications.

= 1.28.0 =

* Added Farsi (Iran) translation (thanks to Mahdi for this contribution).

= 1.27.0 =

* Removed the option for comment approval as there is no real usecase for it and only causes confusion.

= 1.26.0 =

* Added option to send notification on comment approval (thanks to Saumya Majumder for this extension).
* Added filters to modify HTML output (thanks to Saumya Majumder for this extension).
* Added Japanese translation (thanks to Kaede Fujisaki for this).

= 1.24.0 =

* Update compatibility for WordPress 6.0

= 1.23.0 =

* Fixed a warning for wrong use of `add_submenu_page()`.

= 1.22.0 =

* Fixed a bug handling the template for the notification e-mail.

= 1.21.0 =

* Fixed sorting in subscription list.
* Updated some translations.

= 1.20.0 =

* Updated compatibility information for WordPress 5.9.
* Implemented subscriber list in WordPress backend comments menu.

= 1.13.0 =

* Updated compatibility information for WordPress 5.8.

= 1.12.0 =

* Reverted renaming of main plugin file to avoid potential update issues

= 1.11.0 =

* Major code refactoring (class based, better PSR compliance)

= 1.10.1 =

* Fixed localization domain (thanks to Arno Welzel!)

= 1.10.0 =

* Changed localization domain (thanks to Arno Welzel!)

= 1.9.0 =

* Added Greek translation (thanks to Chrysovalantis Chatzigeorgiou!)
* Added Czech translation (thanks to Zbyněk Gilar!)

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
