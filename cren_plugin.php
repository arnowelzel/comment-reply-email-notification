<?php
/*
Plugin Name: Comment Reply Email Notification
Plugin URI: https://wordpress.org/plugins/comment-reply-email-notification/
Description: Sends an email notification to the comment author when someone replies to his comment.
Version: 1.35.0
Author: Arno Welzel
Author URI: http://arnowelzel.de
Text Domain: comment-reply-email-notification
*/

defined('ABSPATH') or die();

if(!class_exists('WP_List_Table')) {
    require_once(ABSPATH.'wp-admin/includes/class-wp-list-table.php');
}

require_once('classes/CommentReplyEmailNotification.php');
require_once('classes/SubscriptionsTable.php');

$comment_reply_email_notification = new CommentReplyEmailNotification\CommentReplyEmailNotification();

/**
 * Callback for existing email templates
 */
function cren_get_unsubscribe_link($comment)
{
    global $comment_reply_email_notification;

    return $comment_reply_email_notification->getUnsubscribeLink($comment);
}
