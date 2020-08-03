<?php
/*
Plugin Name: Comment Reply Email Notification
Plugin URI: https://wordpress.org/plugins/comment-reply-email-notification/
Description: Sends an email notification to the comment author when someone replies to his comment.
Version: 1.12.0
Author: Arno Welzel
Author URI: http://arnowelzel.de
Text Domain: comment-reply-email-notification
*/
defined('ABSPATH') or die();

class CommentReplyEmailNotification
{
    const CREN_VERSION = '1.12.0';

    /**
     * Constructor
     */
    public function __construct()
    {
        load_plugin_textdomain('comment-reply-email-notification', false, basename(dirname(__FILE__)) . '/languages/');

        /* Initialize backend stuff */
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'settings_init']);

        /* Initialize frontend stuff */
        add_action('wp_insert_comment', [$this, 'comment_notification'],  99, 2);
        add_action('wp_set_comment_status', [$this, 'comment_status_update'], 99, 2);
        add_filter('preprocess_comment', [$this, 'verify_comment_meta_data']);
        add_filter('comment_form_default_fields', [$this, 'comment_fields']);
        add_filter('comment_form_submit_field', [$this, 'comment_fields_logged_in']);
        add_action('comment_post', [$this, 'persist_subscription_opt_in']);
        add_action('init', [$this, 'unsubscribe_route']);
    }

    /**
     * Add admin menus
     *
     * @return void
     */
    function add_admin_menu()
    {
        add_options_page(
            'Comment Reply Email Notification',
            'Comment Reply Email Notification',
            'manage_options',
            'comment_reply_email_notification',
            [$this, 'options_page']
        );
    }

    /**
     * Initialize settings
     *
     * @return void
     */
    function settings_init()
    {
        $defaults = [
            'cren_subscription_check_by_default' => [
                'type'    => 'boolean',
                'default' => true
            ],
            'cren_display_gdpr_notice' => [
                'type'    => 'boolean',
                'default' => false
            ],
            'cren_privacy_policy_url' => [
                'type'    => 'string',
                'default' => ''
            ]
        ];

        register_setting('cren_admin', 'cren_settings', $defaults);

        add_settings_section(
            'cren_admin_section',
            '',
            [$this, 'settings_section_callback'],
            'cren_admin'
        );

        add_settings_field(
            'cren_subscription_check_by_default',
            __('Check the subscription checkbox by default', 'comment-reply-email-notification'),
            [$this, 'subscription_check_by_default_render'],
            'cren_admin',
            'cren_admin_section'
        );

        add_settings_field(
            'cren_display_gdpr_notice',
            __('Display the GDPR checkbox', 'comment-reply-email-notification'),
            [$this, 'display_gdpr_notice_render'],
            'cren_admin',
            'cren_admin_section'
        );

        add_settings_field(
            'cren_privacy_policy_url',
            __('Privacy Policy URL', 'comment-reply-email-notification'),
            [$this, 'privacy_policy_url_render'],
            'cren_admin',
            'cren_admin_section'
        );
    }

    /**
     * Callback for check by default
     *
     * @return void
     */
    function subscription_check_by_default_render()
    {
        $option = $this->get_cren_option('cren_subscription_check_by_default', 0);
        echo sprintf(
            '<input type="checkbox" name="cren_settings[cren_subscription_check_by_default]" value="1" %s />',
            checked($option, 1, false)
        );
    }

    /**
     * Callback for GDPR checkbox
     *
     * @return void
     */
    function display_gdpr_notice_render()
    {
        $option = $this->get_cren_option('cren_display_gdpr_notice', 0);
        echo sprintf(
            '<input type="checkbox" name="cren_settings[cren_display_gdpr_notice]" value="1" %s />',
            checked($option, 1, false)
        );
    }

    /**
     * Callback for privacy URL input
     *
     * @return void
     */
    function privacy_policy_url_render()
    {
        $options = get_option('cren_settings');
        echo sprintf(
            '<input type="text" name="cren_settings[cren_privacy_policy_url]" value="%s" />',
             $options['cren_privacy_policy_url']
        );
    }

    /**
     * Callback for settings section
     *
     * @return void
     */
    function settings_section_callback()
    {
    }

    /**
     * Render options page
     *
     * @return void
     */
    function options_page()
    {
        echo '<form action="options.php" method="post"><h2>Comment Reply Email Notification</h2>';
        settings_fields('cren_admin');
        do_settings_sections('cren_admin');
        submit_button();
        echo '</form>';
    }

    /**
     * Sends an email notification when a comment receives a reply
     *
     * @param  int    $commentId The comment ID
     * @param  object $comment   The comment object
     * @return boolean
     */
    function comment_notification($commentId, $comment)
    {
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
            require $this->notification_template_path();
            $body = ob_get_clean();

            $title = html_entity_decode(get_option('blogname'), ENT_QUOTES) . ' - ' . __('New reply to your comment', 'comment-reply-email-notification', $body);

            add_filter('wp_mail_content_type', [$this, 'wp_mail_content_type_filter']);
            wp_mail($email, $title, $body);
            remove_filter('wp_mail_content_type', [$this, 'wp_mail_content_type_filter']);
        }
    }

    /**
     * Returns the notification template path. It's either a custom one, located at
     * wp-content/themes/[THEME]/templates/notification.php or the default one, located
     * at wp-content/plugins/comment-reply-email-notification/templates/notification.php.
     *
     * @return string
     */
    function notification_template_path()
    {
        $customTemplate = locate_template('templates/cren/notification.php');

        if ($customTemplate) {
            return $customTemplate;
        }

        return __DIR__ . '/templates/cren/notification.php';
    }

    /**
     * Filter that changes the email content type when the notification is sent.
     *
     * @param  string $contentType The content type
     * @return string
     */
    function wp_mail_content_type_filter($contentType)
    {
        return 'text/html';
    }

    /**
     * Generates the unsubscribe link for a comment/user.
     *
     * @param  StdClass $comment The comment object
     * @return string
     */
    function get_unsubscribe_link($comment)
    {
        $key = $this->secret_key($comment->comment_ID);

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
    function secret_key($commentId)
    {
        return hash_hmac('sha512', $commentId, wp_salt(), false);
    }

    /**
     * Processes the unsubscribe request.
     *
     * @return void
     */
    function unsubscribe_route()
    {
        $requestUri = $_SERVER['REQUEST_URI'];

        if (preg_match('/cren\/unsubscribe/', $requestUri)) {
            $commentId = filter_input(INPUT_GET, 'comment', FILTER_SANITIZE_NUMBER_INT);
            $comment   = get_comment($commentId);

            if (!$comment) {
                echo 'Invalid request.';
                exit;
            }

            $userKey = filter_input(INPUT_GET, 'key', FILTER_SANITIZE_STRING);
            $realKey = $this->secret_key($commentId);

            if ($userKey != $realKey) {
                echo 'Invalid request.';
                exit;
            }

            $uri = get_permalink($comment->comment_post_ID);

            $this->persist_subscription_opt_out($commentId);

            echo '<!doctype html><html><head><meta charset="utf-8"><title>' . get_bloginfo('name') . '</title></head><body>';
            echo '<p>' . __('Your subscription for this comment has been cancelled.' , 'comment-reply-email-notification') . '</p>';
            echo '<script type="text/javascript">setTimeout(function() { window.location.href="' . $uri . '"; }, 3000);</script>';
            echo '</body></html>';
            exit;
        }
    }

    /**
     * Sends a notification if a comment is approved
     *
     * @param  int    $commentId     The comment ID
     * @param  string $commentStatus The new comment status
     * @return void
     */
    function comment_status_update($commentId, $commentStatus)
    {
        $comment = get_comment($commentId);

        if ($commentStatus == 'approve') {
            $this->comment_notification($comment->comment_ID, $comment);
        }
    }

    /**
     * Adds a checkbox to the comment form which allows the user to not receive
     * new replies.
     *
     * @param  array $fields The default form fields
     * @return array
     */
    function comment_fields($fields)
    {
        $label = apply_filters('cren_comment_checkbox_label', __('Notify me via e-mail if anyone answers my comment.' , 'comment-reply-email-notification'));
        $checked = $this->get_default_checked() ? 'checked' : '';

        $fields['cren_subscribe_to_comment'] = '<p class="comment-form-comment-subscribe">'.
            '<label for="cren_subscribe_to_comment"><input id="cren_subscribe_to_comment" name="cren_subscribe_to_comment" type="checkbox" value="on" ' . $checked . '>' . $label . '</label></p>';

        if ($this->display_gdpr_notice()) {
            $fields['cren_gdpr'] = $this->render_gdpr_notice();
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
    function comment_fields_logged_in($submitField)
    {
        $checkbox = '';

        if (is_user_logged_in()) {
            $label   = apply_filters('cren_comment_checkbox_label', __('Notify me via e-mail if anyone answers my comment.' , 'comment-reply-email-notification'));
            $checked = $this->get_default_checked() ? 'checked' : '';

            $checkbox = '<p class="comment-form-comment-subscribe"><label for="cren_subscribe_to_comment"><input id="cren_subscribe_to_comment" name="cren_subscribe_to_comment" type="checkbox" value="on"  ' . $checked . '> ' . $label . '</label></p>';

            if ($this->display_gdpr_notice()) {
                $checkbox .= $this->render_gdpr_notice();
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
    function get_cren_option($option, $default)
    {
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
    function get_default_checked()
    {
        return $this->get_cren_option('cren_subscription_check_by_default', false);
    }

    /**
     * Returns whether the GDPR checkbox should be shown or not.
     *
     * @return bool
     */
    function display_gdpr_notice()
    {
        return $this->get_cren_option('cren_display_gdpr_notice', false);
    }

    /**
     * Gets the privacy policy URL.
     *
     * @return string
     */
    function get_privacy_policy_url()
    {
        return $this->get_cren_option('cren_privacy_policy_url', '');
    }

    /**
     * Renders the GDPR checkbox.
     *
     * @return string
     */
    function render_gdpr_notice()
    {
        $label = apply_filters(
            'cren_gdpr_checkbox_label',
            sprintf(__('I consent to %s collecting and storing the data I submit in this form.' , 'comment-reply-email-notification'), get_option('blogname'))
        );

        $privacyPolicyUrl = $this->get_privacy_policy_url();
        $privacyPolicy    = "<a target='_blank' href='{$privacyPolicyUrl}'>(" . __('Privacy Policy', 'comment-reply-email-notification') . ")</a>";

        return '<p class="comment-form-comment-subscribe"><label for="cren_gdpr"><input id="cren_gdpr" name="cren_gdpr" type="checkbox" value="yes" required="required">' . $label . ' ' . $privacyPolicy . ' <span class="required">*</span></label></p>';
    }

    /**
     * Persists the user choice.
     *
     * @param  int     $commentId The comment ID
     * @return boolean
     */
    function persist_subscription_opt_in($commentId)
    {
        $value = (isset($_POST['cren_subscribe_to_comment']) && $_POST['cren_subscribe_to_comment'] == 'on') ? 'on' : 'off';
        return add_comment_meta($commentId, 'cren_subscribe_to_comment', $value, true);
    }

    /**
     * Persists the user subscription removal.
     *
     * @param  int     $commentId The comment ID
     * @return boolean
     */
    function persist_subscription_opt_out($commentId)
    {
        return update_comment_meta($commentId, 'cren_subscribe_to_comment', 'off');
    }

    /**
     * Verifies if the comment contains all the required meta data (e.g. GDPR checkbox if applicable).
     *
     * @param  array $comment
     * @return array
     */
    function verify_comment_meta_data($comment)
    {
        if ($this->display_gdpr_notice() && !is_admin()) {
            if (!isset($_POST['cren_gdpr'])) {
                wp_die(__('Error: you must agree with the terms to send a comment. Hit the back button on your web browser and resubmit your comment if you agree with the terms.'));
            }
        }

        return $comment;
    }
}

$comment_reply_email_notification = new CommentReplyEmailNotification();

/**
 * Callback for existing email templates
 */
function cren_get_unsubscribe_link($comment)
{
    global $comment_reply_email_notification;

    return $comment_reply_email_notification->get_unsubscribe_link($comment);
}