<h2><?php printf(__('Hi %s', 'cren-plugin'), $parent->comment_author) ?>,</h2>

<p><?php printf(__('%s has replied to your comment on', 'cren-plugin'), $comment->comment_author) ?></p>

<p><a href="<?php echo get_permalink($parent->comment_post_ID) ?>"><?php echo get_the_title($parent->comment_post_ID) ?></a></p>

<p><em><?php echo esc_html($comment->comment_content) ?></em>

<p><a href="<?php echo get_comment_link($parent->comment_ID) ?>"><?php echo __('Click here to reply', 'cren-plugin') ?></a></p>

<p><a href="<?php echo cren_get_unsubscribe_link($parent) ?>"><?php echo __('Click here to stop receiving these messages', 'cren-plugin') ?></a></p>