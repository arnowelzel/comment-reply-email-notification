<?php
namespace CommentReplyEmailNotification;

class CommentReplyEmailNotification
{
    const CREN_VERSION = '1.34.0';

    /**
     * Constructor
     */
    public function __construct()
    {
        /* Initialize backend stuff */
        add_action('admin_menu', [$this, 'addAdminMenu']);
        add_action('admin_init', [$this, 'settingsInit']);

        /* Initialize frontend stuff */
        add_action('wp_insert_comment', [$this, 'commentNotification'],  99, 2);
        add_action('wp_set_comment_status', [$this, 'commentStatusUpdate'], 99, 2);
        add_filter('preprocess_comment', [$this, 'verifyCommentMetaData']);
        add_filter('comment_form_default_fields', [$this, 'commentFields']);
        add_filter('comment_form_submit_field', [$this, 'commentFieldsLoggedIn']);
        add_action('comment_post', [$this, 'persistSubscriptionOptIn']);
        add_action('init', [$this, 'init']);
    }

    /**
     * Add admin menus
     *
     * @return void
     */
    function addAdminMenu()
    {
        add_options_page(
            __('Comment Reply Email Notification', 'comment-reply-email-notification'),
            __('Comment Reply Email Notification', 'comment-reply-email-notification'),
            'manage_options',
            'comment_reply_email_notification',
            [$this, 'outputAdminSettingsPage']
        );
        add_comments_page(
            __('Comment Reply Email Notification', 'comment-reply-email-notification'),
            __('Comment subscriptions', 'comment-reply-email-notification'),
            'manage_options',
            'comment_reply_email_notification_subscriptions',
            [$this, 'outputAdminSubscriptionsPage']
        );
    }

    /**
     * Initialize settings
     *
     * @return void
     */
    function settingsInit()
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
    }

    /**
     * Output settings page in backend
     *
     * @return void
     */
    function outputAdminSettingsPage()
    {
        ?>
        <style>
            .cren_text {
                font-size:14px;
            }
            .cren_text:first-child {
                padding-top:15px;
            }
        </style>
        <div class="wrap">
            <form action="options.php" method="post"><h1><?php echo __('Comment Reply Email Notification', 'comment-reply-email-notification'); ?></h1>
                <?php settings_fields('cren_admin'); ?>
                <script>
                    function crenSwitchTab(tab)
                    {
                        let num=1;
                        while (num < 3) {
                            if (tab == num) {
                                document.getElementById('cren-switch-'+num).classList.add('nav-tab-active');
                                document.getElementById('cren-tab-'+num).style.display = 'block';
                            } else {
                                document.getElementById('cren-switch-'+num).classList.remove('nav-tab-active');
                                document.getElementById('cren-tab-'+num).style.display = 'none';
                            }
                            num++;
                        }
                        document.getElementById('cren-switch-'+tab).blur();
                        if (tab == 1 && ("pushState" in history)) {
                            history.pushState("", document.title, window.location.pathname+window.location.search);
                        } else {
                            location.hash = 'tab-' + tab;
                        }
                        let referrer = document.getElementsByName('_wp_http_referer');
                        if (referrer[0]) {
                            let parts = referrer[0].value.split('#');
                            if (tab>1) {
                                referrer[0].value = parts[0] + '#tab-' + tab;
                            } else {
                                referrer[0].value = parts[0];
                            }
                        }
                    }

                    function crenUpdateCurrentTab()
                    {
                        if(location.hash == '') {
                            crenSwitchTab(1);
                        } else {
                            let num = 1;
                            while (num < 3) {
                                if (location.hash == '#tab-' + num) crenSwitchTab(num);
                                num++;
                            }
                        }
                    }
                </script>
                <nav class="nav-tab-wrapper" aria-label="<?php echo __('Secondary menu'); ?>">
                    <a href="#" id="cren-switch-1" class="nav-tab nav-tab-active" onclick="crenSwitchTab(1);return false;"><?php echo __('General', 'comment-reply-email-notification'); ?></a>
                    <a href="#" id="cren-switch-2" class="nav-tab" onclick="crenSwitchTab(2);return false;"><?php echo __('Info', 'comment-reply-email-notification'); ?></a>
                </nav>

                <table id="cren-tab-1" class="form-table">
                    <tr>
                        <th scope="row"><?php echo __('Check the subscription checkbox by default', 'comment-reply-email-notification'); ?></th>
                        <td><input type="checkbox" name="cren_settings[cren_subscription_check_by_default]" value="1"<?php if($this->getDefaultChecked()) echo ' checked="checked"'; ?>/></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php echo __('Display the GDPR checkbox', 'comment-reply-email-notification'); ?></th>
                        <td><input type="checkbox" name="cren_settings[cren_display_gdpr_notice]" value="1" <?php if($this->getDisplayGdprNotice()) echo ' checked="checked"'; ?>/></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php echo __('Privacy Policy URL', 'comment-reply-email-notification'); ?></th>
                        <td><input type="text" class="regular-text" name="cren_settings[cren_privacy_policy_url]" value="<?php echo esc_html($this->getSetting('cren_privacy_policy_url', '')); ?>"></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php echo __('From address for e-mails', 'comment-reply-email-notification'); ?></th>
                        <td>
                            <input type="text" class="regular-text" name="cren_settings[cren_from]" value="<?php echo esc_html($this->getSetting('cren_from', '')); ?>">
                            <p class="description"><?php echo __('leave empty for default sender', 'comment-reply-email-notification') ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php echo __('Subject for e-mails', 'comment-reply-email-notification'); ?></th>
                        <td>
                            <label><input type="radio" name="cren_settings[cren_subject_type]" value="1"<?php if($this->getSubjectType() === 1) echo ' checked="checked"'; ?>/> <?php echo sprintf('[%s] - %s', __('Name of the website', 'comment-reply-email-notification'), __('New reply to your comment', 'comment-reply-email-notification')); ?></label><br>
                            <label><input type="radio" name="cren_settings[cren_subject_type]" value="2"<?php if($this->getSubjectType() === 2) echo ' checked="checked"'; ?>/> <?php echo __('Custom text', 'comment-reply-email-notification'); ?></label><br>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php echo __('Custom text for e-mail subject', 'comment-reply-email-notification'); ?></th>
                        <td><input type="text" class="regular-text" name="cren_settings[cren_custom_subject_text]" value="<?php echo esc_html($this->getSetting('cren_custom_subject_text', '')); ?>" placeholder="<?php echo __('New reply to your comment', 'comment-reply-email-notification') ?>"></td>
                    </tr>
                </table>

                <div id="cren-tab-2" style="display:none;">
                    <p class="cren_text"><?php echo __('Plugin version', 'comment-reply-email-notification') ?>: <?php echo self::CREN_VERSION; ?></p>
                    <p class="cren_text"><?php echo __('This plugin allows visitors to subscribe to get answers to their comments via e-mail.', 'comment-reply-email-notification'); ?></p>
                    <p class="cren_text"><?php echo __('For documentation about hooks, styling etc. please see the description', 'comment-reply-email-notification'); ?>: <a href="https://wordpress.org/plugins/comment-reply-email-notification/#description" target="_blank">https://wordpress.org/plugins/comment-reply-email-notification/#description</a>.</p>
                    <p class="cren_text"><b><?php echo __('If you like my WordPress plugins and want to support my work I would be very happy about a donation via PayPal.', 'comment-reply-email-notification'); ?></b></p>
                    <p class="cren_text"><b><a href="https://paypal.me/ArnoWelzel">https://paypal.me/ArnoWelzel</a></b></p>
                    <p class="cren_text"><b><?php echo __('Thank you :-)', 'comment-reply-email-notification'); ?></b></p>
                </div>
                <?php submit_button(); ?>
            </form>
        </div>
        <script>
            crenUpdateCurrentTab()
            window.addEventListener('popstate', (event) => {
                crenUpdateCurrentTab();
            });
        </script>
        <?php
    }

    /**
     * Output subscriptions page in backend
     *
     * @return void
     */
    function outputAdminSubscriptionsPage()
    {
        $subscriptionsTable = new SubscriptionsTable();
        $subscriptionsTable->prepare_items();
        ?>
        <div class="wrap">
            <h1><?php echo __('Comment subscriptions', 'comment-reply-email-notification'); ?></h1>
            <?php $subscriptionsTable->display(); ?>
        </div>
        <?php
    }

    /**
     * Sends an e-mail notification when a comment receives a reply
     *
     * @param  int    $commentId The comment ID
     * @param  object $comment   The comment object
     * @return boolean
     */
    function commentNotification($commentId, $comment)
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
            require $this->getNotificationTemplatePath();
            $body = ob_get_clean();

            if ($this->getSubjectType() === 1) {
				$title = html_entity_decode(get_option('blogname'), ENT_QUOTES) . ' - ' . __('New reply to your comment', 'comment-reply-email-notification');
			} else {
                $title = $this->getSetting('cren_custom_subject_text', __('New reply to your comment', 'comment-reply-email-notification'));
			}

            add_filter('wp_mail_content_type', [$this, 'mailContentTypeFilter']);
            $from = $this->getSetting('cren_from', '');
            if ('' !== $from) {
                add_filter('wp_mail_from', [$this, 'mailFromFilter']);
            }
            wp_mail($email, $title, $body);
            remove_filter('wp_mail_content_type', [$this, 'mailContentTypeFilter']);
            if ('' !== $from) {
                remove_filter('wp_mail_from', [$this, 'mailFromFilter']);
            }
        }
    }

    /**
     * Returns the notification template path. It's either a custom one, located at
     * wp-content/themes/[THEME]/templates/notification.php or the default one, located
     * at wp-content/plugins/comment-reply-email-notification/templates/notification.php.
     *
     * @return string
     */
    function getNotificationTemplatePath()
    {
        $customTemplate = locate_template('templates/cren/notification.php');

        if ($customTemplate) {
            return $customTemplate;
        }

        return __DIR__ . '/../templates/cren/notification.php';
    }

    /**
     * Filter that changes the email content type when the notification is sent.
     *
     * @param  string $contentType The content type
     * @return string
     */
    function mailContentTypeFilter($contentType)
    {
        return 'text/html';
    }

    /**
     * Filter that changes the from address when sending e-mails.
     *
     * @param  string $contentType The content type
     * @return string
     */
    function mailFromFilter($contentType)
    {
        return $this->getSetting('cren_from', '');
    }

    /**
     * Generates the unsubscribe link for a comment/user.
     *
     * @param  StdClass $comment The comment object
     * @return string
     */
    function getUnsubscribeLink($comment)
    {
        $key = $this->secretKey($comment->comment_ID);

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
    function secretKey($commentId)
    {
        return hash_hmac('sha512', $commentId, wp_salt(), false);
    }

    /**
     * Intialize plugin and process unsubscribe if requested.
     *
     * @return void
     */
    function init()
    {
        load_plugin_textdomain('comment-reply-email-notification', false, 'comment-reply-email-notification/languages/');

        $requestUri = $_SERVER['REQUEST_URI'];
        if (preg_match('/cren\/unsubscribe/', $requestUri)) {
            $commentId = filter_input(INPUT_GET, 'comment', FILTER_SANITIZE_NUMBER_INT);
            $comment   = get_comment($commentId);

            if (!$comment) {
                echo 'Invalid request.';
                exit;
            }

            $userKey = filter_input(INPUT_GET, 'key', FILTER_SANITIZE_STRING);
            $realKey = $this->secretKey($commentId);

            if ($userKey != $realKey) {
                echo 'Invalid request.';
                exit;
            }

            $uri = get_permalink($comment->comment_post_ID);

            $this->persistSubscriptionOptOut($commentId);

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
    function commentStatusUpdate($commentId, $commentStatus)
    {
        $comment = get_comment($commentId);

        if ($commentStatus == 'approve') {
            $this->commentNotification($comment->comment_ID, $comment);
        }
    }

    /**
     * Adds a checkbox to the comment form which allows the user to not receive
     * new replies.
     *
     * @param  array $fields The default form fields
     * @return array
     */
    function commentFields($fields)
    {
        $label = apply_filters('cren_comment_checkbox_label', __('Notify me via e-mail if anyone answers my comment.' , 'comment-reply-email-notification'));
        $checked = $this->getDefaultChecked() ? 'checked' : '';

        $subscribeToCommentsHtml = '<p class="comment-form-comment-subscribe"><label for="cren_subscribe_to_comment"><input id="cren_subscribe_to_comment" name="cren_subscribe_to_comment" type="checkbox" value="on" ' . $checked . '>' . $label . '</label></p>';

        $fields['cren_subscribe_to_comment'] = apply_filters( 'cren_comment_subscribe_html', $subscribeToCommentsHtml, $label, $this->getDefaultChecked() );

        if ($this->getDisplayGdprNotice()) {
            $fields['cren_gdpr'] = $this->renderGdprNotice();
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
    function commentFieldsLoggedIn($submitField)
    {
        $checkbox = '';

        if (is_user_logged_in()) {
            $label   = apply_filters('cren_comment_checkbox_label', __('Notify me via e-mail if anyone answers my comment.' , 'comment-reply-email-notification'));
            $checked = $this->getDefaultChecked() ? 'checked' : '';

            $subscribeToCommentsHtml = '<p class="comment-form-comment-subscribe"><label for="cren_subscribe_to_comment"><input id="cren_subscribe_to_comment" name="cren_subscribe_to_comment" type="checkbox" value="on" ' . $checked . '>' . $label . '</label></p>';

            $checkbox = apply_filters( 'cren_comment_subscribe_html', $subscribeToCommentsHtml, $label, $this->getDefaultChecked() );

            if ($this->getDisplayGdprNotice()) {
                $checkbox .= $this->renderGdprNotice();
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
    function getSetting($option, $default)
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
    function getDefaultChecked()
    {
        return $this->getSetting('cren_subscription_check_by_default', false);
    }

    /**
     * Returns whether the GDPR checkbox should be shown or not.
     *
     * @return bool
     */
    function getDisplayGdprNotice()
    {
        return $this->getSetting('cren_display_gdpr_notice', false);
    }

    /**
     * Gets the privacy policy URL.
     *
     * @return string
     */
    function getPrivacyPolicyUrl()
    {
        return $this->getSetting('cren_privacy_policy_url', '');
    }

	/**
	 * Returns the type of subject text to be used for the e-mail
     *
     * @return int
	 */
    function getSubjectType()
	{
		return (int)$this->getSetting('cren_subject_type', 1);
	}

    /**
     * Renders the GDPR checkbox.
     *
     * @return string
     */
    function renderGdprNotice()
    {
        $label = apply_filters(
            'cren_gdpr_checkbox_label',
            sprintf(__('I consent to %s collecting and storing the data I submit in this form.' , 'comment-reply-email-notification'), get_option('blogname'))
        );

        $privacyPolicyUrl = $this->getPrivacyPolicyUrl();
        $privacyPolicy    = "<a target='_blank' href='{$privacyPolicyUrl}'>(" . __('Privacy Policy', 'comment-reply-email-notification') . ")</a>";

        $finalGdprHtml = '<p class="comment-form-comment-subscribe"><label for="cren_gdpr"><input id="cren_gdpr" name="cren_gdpr" type="checkbox" value="yes" required="required">' . $label . ' ' . $privacyPolicy . ' <span class="required">*</span></label></p>';

        return apply_filters(
            'cren_gdpr_checkbox_html',
            $finalGdprHtml,
            $label,
            $privacyPolicyUrl
        );
    }

    /**
     * Persists the user choice.
     *
     * @param  int     $commentId The comment ID
     * @return boolean
     */
    function persistSubscriptionOptIn($commentId)
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
    function persistSubscriptionOptOut($commentId)
    {
        return update_comment_meta($commentId, 'cren_subscribe_to_comment', 'off');
    }

    /**
     * Verifies if the comment contains all the required meta data (e.g. GDPR checkbox if applicable).
     *
     * @param  array $comment
     * @return array
     */
    function verifyCommentMetaData($comment)
    {
        if ($this->getDisplayGdprNotice() && !is_admin()) {
            if (!isset($_POST['cren_gdpr'])) {
                wp_die(__('Error: you must agree with the terms to send a comment. Hit the back button on your web browser and resubmit your comment if you agree with the terms.', 'comment-reply-email-notification'));
            }
        }

        return $comment;
    }
}
