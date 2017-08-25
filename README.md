# Wordpress Comment Reply Email Notification

This plugin notifies a comment author via email when someone replies to his comment.

## Requirements
* PHP 5.5+
* Wordpress 4.4+

## Installation

How to install the plugin:

1. Upload the ZIP file to your Wordpress installation directory
2. Extract the ZIP file contents to the /wp-content/plugins directory.
3. Activate the plugin.
4. For better results, setup a plugin to send emails using SMTP.

## Changelog
* 1.3.3: updated translations and translation domain
* 1.3.2: fixed email content type filter interfering with other emails; removed asterisk from comment form subscription checkbox
* 1.3.1: fixed missing variable on checkbox template; fixed notification being sent to the comment author when he replies his own comment
* 1.3.0: fixed opt-in checkbox for logged in users; added Russian and Ukrainian translations (thanks to Oleh Astappiev!) added unsubscribe link to email
* 1.2.0: added opt-in checkbox to comment form.
* 1.1.1: fixed typo in hook
* 1.1.0: added ES and PT-BR translations, added build script
* 1.0.0: First release
