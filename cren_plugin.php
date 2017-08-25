<?php
/**
 * Plugin Name:   Comment Reply Email Notification
 * Plugin URI:    https://github.com/guhemama/worpdress-comment-reply-email-notification
 * Description:   Sends an email notification to the comment author when someone replies to his comment.
 * Version:       1.3.3
 * Developer:     Gustavo H. Mascarenhas Machado
 * Developer URI: https://guh.me
 * License:       BSD-3
 *
 * Copyright (c) 2016-2017, Gustavo H. Mascarenhas Machado
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *     * Redistributions of source code must retain the above copyright
 *       notice, this list of conditions and the following disclaimer.
 *     * Redistributions in binary form must reproduce the above copyright
 *       notice, this list of conditions and the following disclaimer in the
 *       documentation and/or other materials provided with the distribution.
 *     * Neither the name of Gustavo H. Mascarenhas Machado nor the
 *       names of its contributors may be used to endorse or promote products
 *       derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL GUSTAVO H. MASCARENHAS MACHADO BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 */

load_plugin_textdomain('cren-plugin', false, basename(dirname(__FILE__)) . '/i18n/');

add_action('wp_insert_comment',    'cren_comment_notification',  99, 2);
add_action('wp_set_comment_status','cren_comment_status_update', 99, 2);

add_filter('comment_form_default_fields', 'cren_comment_fields');
add_filter('comment_form_submit_field', 'cren_comment_fields_logged_in');

add_action('comment_post', 'cren_persist_subscription_opt_in');

add_action('init', 'cren_unsubscribe_route');

/**
 * Sends an email notification when a comment receives a reply
 *
 * @param  int    $commentId The comment ID
 * @param  object $comment   The comment object
 * @return boolean
 */
function cren_comment_notification($commentId, $comment) {
    if ($comment->comment_approved == 1 && $comment->comment_parent > 0) {
        $parent = get_comment($comment->comment_parent);
        $email  = $parent->comment_author_email;

        // Parent comment author == new comment author
        // In this case, we don't send a notification.
        if ($email == $comment->comment_author_email) {
            return false;
        }

        $subscription = get_comment_meta($parent->comment_ID, 'cren_subscribe_to_comment', true);

        // If we don't find the option, we assume the user is subscribed.
        if ($subscription && $subscription == 'off') {
            return false;
        }

        $body  = __('Hi ', 'cren-plugin') . $parent->comment_author . ',';
        $body .= '<br><br>' . $comment->comment_author . __(' has replied to your comment on ', 'cren-plugin');
        $body .= '<a href="' . get_permalink($parent->comment_post_ID) . '">' . get_the_title($parent->comment_post_ID) . '</a>';
        $body .= '<br><br><em>"' . esc_html($comment->comment_content) . '"</em>';
        $body .= '<br><br><a href="' . get_comment_link($parent->comment_ID) . '">' . __('Click here to reply', 'cren-plugin') . '</a>';

        $body .= '<br><a href="' . cren_get_unsubscribe_link($parent) . '">' . __('Click here to stop receiving these messages', 'cren-plugin') . '</a>';

        $title = get_option('blogname') . ' - ' . __('New reply to your comment', 'cren-plugin', $body);

        add_filter('wp_mail_content_type', 'cren_wp_mail_content_type_filter');

        wp_mail($email, $title, $body);

        remove_filter('wp_mail_content_type', 'cren_wp_mail_content_type_filter');
    }
}

/**
 * Filter that changes the email content type when the notification is sent.
 * @param  string $contentType The content type
 * @return string
 */
function cren_wp_mail_content_type_filter($contentType) {
    return 'text/html';
}

/**
 * Generates the unsubscribe link for a comment/user.
 *
 * @param  StdClass $comment The comment object
 * @return string
 */
function cren_get_unsubscribe_link($comment) {
    $key = cren_secret_key($comment->comment_ID);

    $params = [
        'comment' => $comment->comment_ID
      , 'key'     => $key
    ];

    $uri = site_url() . '/cren/unsubscribe?' . http_build_query($params);

    return $uri;
}

/**
 * Generates a secret key to validate the requests
 *
 * @param  int    $commentId The comment ID
 * @return string
 */
function cren_secret_key($commentId) {
    return hash_hmac('sha512', $commentId, wp_salt(), false);
}

/**
 * Processes the unsubscribe request.
 *
 * @return void
 */
function cren_unsubscribe_route() {
    $requestUri = $_SERVER['REQUEST_URI'];

    if (preg_match('/cren\/unsubscribe/', $requestUri)) {
        $commentId = filter_input(INPUT_GET, 'comment', FILTER_SANITIZE_NUMBER_INT);
        $comment   = get_comment($commentId);

        if (!$comment) {
            echo 'Invalid request.';
            exit;
        }

        $userKey = filter_input(INPUT_GET, 'key', FILTER_SANITIZE_STRING);
        $realKey = cren_secret_key($commentId);

        if ($userKey != $realKey) {
            echo 'Invalid request.';
            exit;
        }

        $uri = get_permalink($comment->comment_post_ID);

        cren_persist_subscription_opt_out($commentId);

        echo '<p>' . __('Your subscription for this comment has been cancelled.' , 'cren-plugin') . '</p>';
        echo '<script type="text/javascript">setTimeout(function() { window.location.href="' . $uri . '"; }, 3000);</script>';
        exit;
    }
}

/**
 * Sends a notification if a comment is approved
 * @param  int    $commentId     The comment ID
 * @param  string $commentStatus The new comment status
 * @return boolean
 */
function cren_comment_status_update($commentId, $commentStatus) {
    $comment = get_comment($commentId);

    if ($commentStatus == 'approve') {
        cren_comment_notification($comment->comment_ID, $comment);
    }
}

/**
 * Adds a checkbox to the comment form which allows the user to not receive
 * new replies.
 *
 * @param  array $fields The default form fields
 * @return array
 */
function cren_comment_fields($fields) {
    $fields['cren_subscribe_to_comment'] = '<p class="comment-form-comment-subscribe">'.
      '<label for="cren_subscribe_to_comment"><input id="cren_subscribe_to_comment" name="cren_subscribe_to_comment" type="checkbox" value="on" checked>' . __('Subscribe to comment' , 'cren-plugin') . '</label></p>';

    return $fields;
}

/**
 * Adds a checkbox to the logged in comment form which allows the user to not
 * receive new replies.
 *
 * Uses the comment form submit hook as a workaround for logged in users.
 *
 * @param  string $submitField
 * @return string
 */
function cren_comment_fields_logged_in($submitField) {
    if (is_user_logged_in()) {
        $checkbox = '<p class="comment-form-comment-subscribe">'.
            '<label for="cren_subscribe_to_comment"><input id="cren_subscribe_to_comment" name="cren_subscribe_to_comment" type="checkbox" value="on" checked>' . __('Subscribe to comment', 'cren-plugin') . '</label></p>';
    }

    return $checkbox . $submitField;
}

/**
 * Persists the customer choice.
 *
 * @param  int     $commentId The comment ID
 * @return boolean
 */
function cren_persist_subscription_opt_in($commentId) {
    $value = (isset($_POST['cren_subscribe_to_comment']) && $_POST['cren_subscribe_to_comment'] == 'on') ? 'on' : 'off';
    return add_comment_meta($commentId, 'cren_subscribe_to_comment', $value, true);
}

/**
 * Persists the customer subscription removal.
 *
 * @param  int     $commentId The comment ID
 * @return boolean
 */
function cren_persist_subscription_opt_out($commentId) {
    return add_comment_meta($commentId, 'cren_subscribe_to_comment', 'off   ', true);
}
