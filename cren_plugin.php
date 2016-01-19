<?php
/**
 * Plugin Name:   Comment Reply Email Notification
 * Plugin URI:    https://github.com/guhemama/worpdress-comment-reply-email-notification
 * Description:   Sends an email notification to the comment author when someone replies to his comment.
 * Version:       1.0.0
 * Developer:     Gustavo H. Mascarenhas Machado
 * Developer URI: https://guh.me
 * License:       BSD-3
 *
 * Copyright (c) 2016, Gustavo H. Mascarenhas Machado
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

load_plugin_textdomain('cren_plugin', false, basename(dirname( __FILE__ )) . '/i18n');

add_action('wp_insert_comment',    'cren_comment_notification',  99, 2);
add_action('wp_set_comment_status','cren_comment_status_update', 99, 2);

add_filter('wp_mail_content_type', function($contentType ) { return 'text/html'; });

/**
 * Sends an email notification when a comment receives a reply
 * @param  int    $commentId The comment ID
 * @param  object $comment   The comment object
 * @return boolean
 */
function cren_comment_notification($commentId, $comment) {
    if ($comment->comment_approved == 1 && $comment->comment_parent > 0) {
        $parent = get_comment($comment->comment_parent);

        $body  = 'Hi ' . $parent->comment_author . ',';
        $body .= '<br><br>' . $comment->comment_author . ' has replied to your comment on ';
        $body .= '<a href="' . get_permalink($parent->comment_post_ID) . '">' . get_the_title($parent->comment_post_ID) . '</a>';
        $body .= '<br><br><em>"' . esc_html($comment->comment_content) . '"</em>';
        $body .= '<br><br><a href="' . get_comment_link($parent->comment_ID) . '">' . __('Click here to reply', 'cren_plugin') . '</a>';

        $email = $parent->comment_author_email;
        $title = get_option('blogname') . ' - ' . __('New reply to your comment', 'cren_plugin', $body);

        wp_mail($email, $title, $body);
    }
}

/**
 * Sends a notification if a comment is approved
 * @param  int    $commentId     The comment ID
 * @param  string $commentStatus The new comment status
 * @return boolean
 */
function cren_status_update($commentId, $commentStatus) {
    $comment = get_comment($commentId);

    if ($commentStatus === 'approve') {
        cren_comment_notification($comment->comment_id, $comment);
    }
}