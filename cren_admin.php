<?php
/**
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

add_action('admin_menu', 'cren_add_admin_menu');
add_action('admin_init', 'cren_settings_init');

function cren_add_admin_menu(  ) {
    add_options_page(
        'Comment Reply Email Notification',
        'Comment Reply Email Notification',
        'manage_options',
        'comment_reply_email_notification',
        'cren_options_page'
    );
}


function cren_settings_init() {
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
        'cren_settings_section_callback',
        'cren_admin'
    );

    add_settings_field(
        'cren_subscription_check_by_default',
        __('Check the subscription checkbox by default', 'cren-plugin'),
        'cren_subscription_check_by_default_render',
        'cren_admin',
        'cren_admin_section'
    );

    add_settings_field(
        'cren_display_gdpr_notice',
        __('Display the GDPR checkbox', 'cren-plugin'),
        'cren_display_gdpr_notice_render',
        'cren_admin',
        'cren_admin_section'
    );

    add_settings_field(
        'cren_privacy_policy_url',
        __('Privacy Policy URL', 'cren-plugin'),
        'cren_privacy_policy_url_render',
        'cren_admin',
        'cren_admin_section'
    );
}


function cren_subscription_check_by_default_render(  ) {
    $options = get_option('cren_settings');
?>
    <input type='checkbox' name='cren_settings[cren_subscription_check_by_default]' <?php checked( $options['cren_subscription_check_by_default'], 1 ); ?> value='1'>
<?php
}


function cren_display_gdpr_notice_render() {
    $options = get_option('cren_settings');
?>
    <input type='checkbox' name='cren_settings[cren_display_gdpr_notice]' <?php checked( $options['cren_display_gdpr_notice'], 1 ); ?> value='1'>
<?php
}


function cren_privacy_policy_url_render() {
    $options = get_option('cren_settings');
?>
    <input type='text' name='cren_settings[cren_privacy_policy_url]' value='<?php echo $options['cren_privacy_policy_url']; ?>'>
<?php

}


function cren_settings_section_callback() {
}


function cren_options_page() {
    ?>
    <form action='options.php' method='post'>
        <h2>Comment Reply Email Notification</h2>
        <?php
            settings_fields('cren_admin');
            do_settings_sections('cren_admin');
            submit_button();
        ?>
    </form>
<?php
}