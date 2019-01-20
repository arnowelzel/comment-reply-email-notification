# Wordpress Comment Reply Email Notification

This plugin notifies a comment author via email when someone replies to his comment.

## Requirements
* PHP 7.0+
* Wordpress 4.4+

## Installation

How to install the plugin:

1. Upload the ZIP file to your Wordpress installation directory
2. Extract the ZIP file contents to the /wp-content/plugins directory.
3. Activate the plugin.
4. For better results, setup a plugin to send emails using SMTP.

## Warning

This plugin uses the `wp_insert_comment` hook, therefore, everytime a comment is created, a notification is likely to be sent. If you are importing comments into your blog, it's a good idea to disable this plugin.

## Customizing the email template

To customize the email template, copy the `templates` folder to your theme folder. The plugin will look for templates on the `/wp-content/themes/[THEME]/templates/cren/` folder; if a custom template is not found, then it will fallback to the default template.

## Changing the subscription checkbox label

The checkbox label can be changed with the `cren_comment_checkbox_label` filter. This way you can update the text to your taste and keep the plugin updated.

## Changing the GDPR checkbox label

The GDPR checkbox label can be changed with the `cren_gdpr_checkbox_label` filter. This way you can update the text to your taste and keep the plugin updated.

## Buy me a coffee/beer

Do you like this plugin? Support it by buying me some human-fuel - coffee on weekdays, and beer on weekends. ;)

[Donate on PayPal](https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=HG8SRFWT4XY58)


## Changelog
* 1.9.0: added Greek translation (thanks to Chrysovalantis Chatzigeorgiou!), added Czech translation (thanks to Zbyněk Gilar!)
* 1.8.0: added Korean translation; fixed cren_gdpr_checkbox_label filter bug
* 1.7.1: fixed blog title encoding
* 1.7.0: fixed bug where subscription checkbox was always checked; added CN translation
* 1.6.1: fixed default settings not being set
* 1.6.0: added Turkish translation (thanks Bünyamin Yildirim!); added filters to the checkbox label; added GDPR box; added admin settings page; decode HTML entities on email title
* 1.5.0: added Italian translation (thanks Giacomo Bellisi!); added Hebrew translation (thanks Lea Cohen!)
* 1.4.4: fixed undefined variable; updated French translation
* 1.4.3: updated Spanish translation, added German translation  (thanks to Nathanael Dalliard!)
* 1.4.2: added French translation
* 1.4.1: fixed unsubscribe page template
* 1.4.0: added the ability to use a custom email template; added plugin rendering options; fixed unsubscribe link
* 1.3.3: updated translations and translation domain
* 1.3.2: fixed email content type filter interfering with other emails; removed asterisk from comment form subscription checkbox
* 1.3.1: fixed missing variable on checkbox template; fixed notification being sent to the comment author when he replies his own comment
* 1.3.0: fixed opt-in checkbox for logged in users; added Russian and Ukrainian translations (thanks to Oleh Astappiev!) added unsubscribe link to email
* 1.2.0: added opt-in checkbox to comment form.
* 1.1.1: fixed typo in hook
* 1.1.0: added ES and PT-BR translations, added build script
* 1.0.0: First release
