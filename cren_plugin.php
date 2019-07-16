<?php
/**
 * Plugin Name:   Comment Reply Email Notification
 * Plugin URI:    https://github.com/guhemama/worpdress-comment-reply-email-notification
 * Description:   Sends an email notification to the comment author when someone replies to his comment.
 * Version:       1.9.0
 * Developer:     Gustavo H. Mascarenhas Machado
 * Developer URI: https://guh.me
 * License:       BSD-3
 * Text Domain:   comment-reply-email-notification
 *
 * Copyright (c) 2016-2018, Gustavo H. Mascarenhas Machado
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
load_plugin_textdomain('comment-reply-email-notification', false, basename(dirname(__FILE__)) . '/languages/');

require_once 'cren_admin.php';

add_action('wp_insert_comment',    'cren_comment_notification',  99, 2);
add_action('wp_set_comment_status','cren_comment_status_update', 99, 2);

add_filter('preprocess_comment', 'cren_verify_comment_meta_data');

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

        ob_start();
        require cren_notification_template_path();
        $body = ob_get_clean();

        $title = html_entity_decode(get_option('blogname'), ENT_QUOTES) . ' - ' . __('New reply to your comment', 'comment-reply-email-notification', $body);

        add_filter('wp_mail_content_type', 'cren_wp_mail_content_type_filter');

        wp_mail($email, $title, $body);

        remove_filter('wp_mail_content_type', 'cren_wp_mail_content_type_filter');
    }
}

/**
 * Returns the notification template path. It's either a custom one, located at
 * wp-content/themes/[THEME]/templates/notification.php or the default one, located
 * at wp-content/plugins/comment-reply-email-notification/templates/notification.php.
 * @return string
 */
function cren_notification_template_path() {
    $customTemplate = locate_template('templates/cren/notification.php');

    if ($customTemplate) {
        return $customTemplate;
    }

    return __DIR__ . '/templates/cren/notification.php';
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
        'comment' => $comment->comment_ID,
        'key'     => $key
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

        echo '<!doctype html><html><head><meta charset="utf-8"><title>' . get_bloginfo('name') . '</title></head><body>';
        echo '<p>' . __('Your subscription for this comment has been cancelled.' , 'comment-reply-email-notification') . '</p>';
        echo '<script type="text/javascript">setTimeout(function() { window.location.href="' . $uri . '"; }, 3000);</script>';
        echo '</body></html>';
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
    $label = apply_filters('cren_comment_checkbox_label', __('Subscribe to comment' , 'comment-reply-email-notification'));
    $checked = cren_get_default_checked() ? 'checked' : '';

    $fields['cren_subscribe_to_comment'] = '<p class="comment-form-comment-subscribe">'.
      '<label for="cren_subscribe_to_comment"><input id="cren_subscribe_to_comment" name="cren_subscribe_to_comment" type="checkbox" value="on" ' . $checked . '>' . $label . '</label></p>';

    if (cren_display_gdpr_notice()) {
        $fields['cren_gdpr'] = cren_render_gdpr_notice();
    }

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
    $checkbox = '';

    if (is_user_logged_in()) {
        $label   = apply_filters('cren_comment_checkbox_label', __('Subscribe to comment' , 'comment-reply-email-notification'));
        $checked = cren_get_default_checked() ? 'checked' : '';

        $checkbox = '<p class="comment-form-comment-subscribe">'.
            '<label for="cren_subscribe_to_comment"><input id="cren_subscribe_to_comment" name="cren_subscribe_to_comment" type="checkbox" value="on"  ' . $checked . '>' . $label . '</label></p>';

        if (cren_display_gdpr_notice()) {
            $checkbox .= cren_render_gdpr_notice();
        }
    }

    return $checkbox . $submitField;
}

/**
 * Get a plugin option.
 *
 * @param  $option
 * @param  $default
 * @return mixed
 */
function cren_get_option($option, $default) {
    $options = get_option('cren_settings');

    if ($options && isset($options[$option])) {
        return $options[$option];
    }

    return $default;
}

/**
 * Returns whether the checkbox should be checked by default or not.
 *
 * @return bool
 */
function cren_get_default_checked() {
    return cren_get_option('cren_subscription_check_by_default', false);
}

/**
 * Returns whether the GDPR checkbox should be shown or not.
 *
 * @return bool
 */
function cren_display_gdpr_notice() {
    return cren_get_option('cren_display_gdpr_notice', false);
}

/**
 * Gets the privacy policy URL.
 *
 * @return string
 */
function cren_get_privacy_policy_url() {
    return cren_get_option('cren_privacy_policy_url', '');
}

/**
 * Renders the GDPR checkbox.
 *
 * @return string
 */
function cren_render_gdpr_notice() {
    $label = apply_filters(
        'cren_gdpr_checkbox_label',
        sprintf(__('I consent to %s collecting and storing the data I submit in this form' , 'comment-reply-email-notification'), get_option('blogname'))
    );

    $privacyPolicyUrl = cren_get_privacy_policy_url();
    $privacyPolicy    = "<a target='_blank' href='{$privacyPolicyUrl}'>(" . __('Privacy Policy', 'comment-reply-email-notification') . ")</a>";

    return '<p class="comment-form-comment-subscribe">'.
      '<label for="cren_gdpr"><input id="cren_gdpr" name="cren_gdpr" type="checkbox" value="yes" required="required">' . $label . ' ' . $privacyPolicy . ' <span class="required">*</span></label></p>';
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
    return update_comment_meta($commentId, 'cren_subscribe_to_comment', 'off');
}

/**
 * Verifies if the comment contains all the required meta data (e.g. GDPR checkbox
 * if applicable).
 *
 * @param  array $comment
 * @return array
 */
function cren_verify_comment_meta_data($comment) {
    if (cren_display_gdpr_notice() && !is_admin()) {
        if (!isset($_POST['cren_gdpr'])) {
            wp_die(__('Error: you must agree with the terms to send a comment. Hit the back button on your web browser and resubmit your comment if you agree with the terms.'));
        }
    }

    return $comment;
}