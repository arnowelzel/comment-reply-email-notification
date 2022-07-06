<?php
namespace CommentReplyEmailNotification;

class SubscriptionsTable extends \WP_List_Table
{
    /**
     * Constructor
     */
    function __construct() {
        parent::__construct(
            [
                'singular'=> '',
                'plural' => '',
                'ajax'   => false,
            ]
        );
    }

    /**
     * Prepare the items for the table to process
     *
     * @return Void
     */
    public function prepare_items()
    {
        global $wpdb;

        $columns = $this->get_columns();
        $hidden = $this->get_hidden_columns();
        $sortable = $this->get_sortable_columns();

        $data = $this->load_data();
        usort( $data, array( &$this, 'sort_data' ) );

        $perPage = 20;
        $currentPage = $this->get_pagenum();
        $totalItems = count($data);

        $this->set_pagination_args( array(
            'total_items' => $totalItems,
            'per_page'    => $perPage
        ) );

        $data = array_slice($data, (($currentPage-1)*$perPage), $perPage);

        $this->_column_headers = array($columns, $hidden, $sortable);
        $this->items = $data;
    }

    /**
     * Get list of columns
     *
     * @return array
     */
    function get_columns() {
        return [
            'id' => __('ID', 'comment-reply-email-notification'),
            'date' => __('Date', 'comment-reply-email-notification'),
            'name' => __('Name', 'comment-reply-email-notification'),
            'email' => __('E-Mail', 'comment-reply-email-notification'),
            'post' => __('Comment at', 'comment-reply-email-notification'),
        ];
    }

    /**
     * Get list of hidden columns
     *
     * @return array
     */
    function get_hidden_columns() {
        return [ 'id' ];
    }

    /**
     * Get list of sortable columns
     *
     * @return array
     */
    function get_sortable_columns()
    {
        return [
            'date' => ['date', 'desc'],
            'name' => ['name', 'asc'],
            'email' => ['email', 'asc'],
            'post' => ['post', 'asc'],
        ];
    }

    /**
     * Load the table data
     *
     * @return array
     */
    private function load_data()
    {
        $data = array();

        $comments = get_comments();
        foreach ($comments as $comment)
        {
            $subscription = get_comment_meta($comment->comment_ID, 'cren_subscribe_to_comment', true);
            if ($subscription && 'off' !== $subscription) {
                $data[] =
                    [
                        'id' => $comment->comment_ID,
                        'date' => $comment->comment_date,
                        'name' => $comment->comment_author,
                        'email' => $comment->comment_author_email,
                        'post' => get_the_title($comment->comment_post_ID),
                    ];
            }
        }

        return $data;
    }

    /**
     * Define what data to show on each column of the table
     *
     * @param $item
     * @param $column_name
     *
     * @return bool|mixed|string
     */
    public function column_default($item, $column_name)
    {
        $value = $item[$column_name];

        switch ($column_name) {
            case 'date':
                $date = \DateTime::createFromFormat('Y-m-d H:i:s', $value);
                return sprintf(
                    '%s %s',
                    $date->format(get_option('date_format')),
                    $date->format(get_option('time_format'))
                );
                return ;
            case 'name':
            case 'email':
                return htmlspecialchars($value);
            case 'post':
                return $value;
        }

    }

    /**
     * Allows you to sort the data by the variables set in the $_GET
     *
     * @return mixed
     */
    private function sort_data($a, $b)
    {
        $orderby = 'date';
        $order = 'desc';

        if(!empty($_GET['orderby']))
        {
            $orderby = $_GET['orderby'];
        }

        if(!empty($_GET['order']))
        {
            $order = $_GET['order'];
        }

        $result = strcmp($a[$orderby], $b[$orderby]);

        if($order === 'asc')
        {
            return $result;
        }

        return -$result;
    }
}
