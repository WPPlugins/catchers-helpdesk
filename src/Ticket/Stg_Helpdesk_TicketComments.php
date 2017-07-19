<?php

namespace StgHelpdesk\Ticket;

use StgHelpdesk\Core\Stg_Helpdesk;
use StgHelpdesk\Core\Stg_Helpdesk_Init;
use StgHelpdesk\Helpers\Stg_Helper_Email;

class Stg_Helpdesk_TicketComments
{

    public static $nonceName = 'stgh_comment_ticket';

    public static $nonceAction = 'comment_on_ticket';

    protected $post;

    /**
     * Instance of this class.
     */
    protected static $instance = null;

    /**
     * Initialize the plugin
     */
    private function __construct()
    {
        $this->post = get_post();
    }

    /**
     * @return static
     */
    public static function instance()
    {
        if (null == static::$instance) {
            static::$instance = new static;
        }

        return static::$instance;
    }

    /**
     * Get comment list
     *
     * @param array $params
     * @return \WP_Query
     */
    public function get($params = array())
    {
        $default = array(
            'posts_per_page' => -1,
            'orderby' => 'post_date',
            'order' => 'DESC',
            'post_type' => apply_filters('stgh_comments_post_type', array(
                STG_HELPDESK_COMMENTS_POST_TYPE
            )),
            'post_parent' => $this->post->ID,
            'post_status' => array(
                'publish',
                'inherit'
            )
        );

        
        $params = wp_parse_args($params, $default);

        $comments = new \WP_Query($params);

        if (!empty($comments->posts)) {
            return $this->addUserToComments($comments->posts);
        }

        return array();
    }

    /**
     * Get number of comments
     * @param array $params
     * @return int
     */
    public function getCount($params = array())
    {
        $default = array(
            'posts_per_page' => -1,
            'orderby' => 'post_date',
            'order' => 'DESC',
            'post_type' => apply_filters('stgh_comments_post_type', array(
                STG_HELPDESK_COMMENTS_POST_TYPE
            )),
            'post_parent' => $this->post->ID,
            'post_status' => array(
                'publish',
                'inherit'
            )
        );

        
        $params = wp_parse_args($params, $default);

        $comments = new \WP_Query($params);

        return $comments->found_posts;
    }

    /**
     * Adding user to comments
     *
     * @param $posts
     * @return mixed
     */
    protected function addUserToComments($posts)
    {
        foreach ($posts as &$post) {
            if (0 != $post->post_author) {
                $user = get_userdata($post->post_author);
                if (!$user) {
                    $post->user_name = get_post_meta($post->ID, '_stgh_comment_author_nickname', true);
                    continue;
                }
                $post->user_id = $user->data->ID;
                $post->user_name = $user->data->display_name;
            } else {
                $post->user_name = __('Anonymous', STG_HELPDESK_TEXT_DOMAIN_NAME);
                $post->user_id = 0;
            }
        }

        return $posts;
    }

    /**
     * Add new comment to ticket public
     * @param array $post
     * @return bool
     */
    public static function saveCommentPublic($post = array())
    {
        $current_user = stgh_get_current_user();

        $parent_id = filter_var($post['ticket_id'], FILTER_VALIDATE_INT);

        if (STG_HELPDESK_POST_TYPE !== get_post_type($parent_id)) {
            $_REQUEST['stgh_message'] = getNotificationMarkup('failure', __('Something went wrong. We couldn&#039;t identify your ticket. Please try again.', STG_HELPDESK_TEXT_DOMAIN_NAME));
            return false;
        }

        if (isset($post['stgh_userid_comment']) && !empty($post['stgh_userid_comment'])) {
            $userId = $post['stgh_userid_comment'];
        } else {
            $userId = $current_user->ID;
        }
        $content = wp_kses_post($post['stgh_user_comment']);

        $data = apply_filters('stgh_public_ticket_comment_args', array(
            'post_content' => $content,
            'post_type' => STG_HELPDESK_COMMENTS_POST_TYPE,
            'post_author' => $userId,
            'post_parent' => $parent_id,
            'post_status' => 'publish',
            'ping_status' => 'closed',
            'comment_status' => 'closed',
        ));

        // Check status current post. If is closed - reopened and set work status

        $close_ticket = true;
        $parentPost = Stg_Helpdesk_Ticket::getInstance($parent_id);
        if ($parentPost->isClosed()) {
            $close_ticket = false;
        }
        $comment_id = self::addToPost($data, $parent_id, $data['post_author']);

        if (!empty($comment_id)) {
            $userInfo = get_userdata(intval($data['post_author']));
            if (in_array('stgh_manager', (array)$userInfo->roles) || in_array('administrator', (array)$userInfo->roles)) {
                add_post_meta($comment_id, '_stgh_type_color', 'agent', true);
            } else {
                add_post_meta($comment_id, '_stgh_type_color', 'user', true);
            }

            $ticket_author = Stg_Helpdesk_Ticket::getAuthor($parent_id);
            if ($ticket_author && user_can($userId, 'edit_other_ticket') && $userId != $ticket_author->ID) {
                Stg_Helpdesk_Ticket::setInAnswered($parent_id);

                            } else {
                Stg_Helpdesk_Ticket::setInNotAnswered($parent_id);

                            }

            // set author nickname and email
            self::addCommentUserMeta($comment_id, $data['post_author']);

            if (false === $comment_id) {
                $_REQUEST['stgh_message'] = __('Your reply could not be submitted for an unknown reason.', STG_HELPDESK_TEXT_DOMAIN_NAME);
                return false;
            } else {
                if (!$close_ticket) {
                    $_REQUEST['stgh_message'] = getNotificationMarkup('success', __('Your reply has been submitted. Your agent will reply ASAP.', STG_HELPDESK_TEXT_DOMAIN_NAME));
                }

                if (isset($post['stg_messsageId']) && $post['stg_messsageId']) {
                    $last_reply_message_id = $post['stg_messsageId'];
                    Stg_Helpdesk_Ticket::setLastMessageId($parent_id, $last_reply_message_id);
                    Stg_Helpdesk_Ticket::addReference($parent_id, $last_reply_message_id);

                }
            }
        }

        return $comment_id;
    }

    /**
     * Set author nickname and email to comment meta
     * @param $comment_id
     * @param $author_id
     */
    public static function addCommentUserMeta($comment_id, $author_id)
    {
        $author = get_userdata($author_id);

        // CRM name
        $name = stgh_crm_get_user_full_name($author_id);
        if (empty($name))
            $name = $author->user_nicename;

        // Save comment meta
        add_post_meta($comment_id, '_stgh_comment_author_name', $name, true);
        add_post_meta($comment_id, '_stgh_comment_author_email', $author->user_email, true);
        add_post_meta($comment_id, '_stgh_comment_author_roles', implode(' ', $author->roles), true);
    }

    public static function getCommentUserMeta($comment_id)
    {
        return array(
            'name' => get_post_meta($comment_id, '_stgh_comment_author_name', true),
            'email' => get_post_meta($comment_id, '_stgh_comment_author_email', true),
            'roles' => get_post_meta($comment_id, '_stgh_comment_author_roles', true)
        );
    }

    /**
     * Adding comment to post
     *
     * @param $data
     * @param bool|false $postId
     * @param bool|false $authorId
     * @return bool|int|\WP_Error
     */
    public static function addToPost($data, $postId = false, $authorId = false)
    {
        if (false === $postId) {
            if (isset($data['post_parent'])) {
                /* Get the parent ID from $data if not provided in the arguments. */
                $postId = intval($data['post_parent']);
                $parent = get_post($postId);

                /* Mare sure the parent exists. */
                if (is_null($parent)) {
                    return false;
                }
            } else {
                return false;
            }
        }

        /**
         * Submit the reply.
         *
         * Now that all the verifications are passed
         * we can proceed to the actual ticket submission.
         */
        $defaults = array(
            'post_content' => '',
            'post_name' => sprintf(__('Reply to ticket %s', STG_HELPDESK_TEXT_DOMAIN_NAME), "#$postId"),
            'post_title' => sprintf(__('Reply to ticket %s', STG_HELPDESK_TEXT_DOMAIN_NAME), "#$postId"),
            'post_type' => STG_HELPDESK_COMMENTS_POST_TYPE,
            'post_status' => 'publish',
            'ping_status' => 'closed',
            'comment_status' => 'closed',
            'post_parent' => $postId,
        );

        $data = wp_parse_args($data, $defaults);

        if (false !== $authorId) {
            $data['post_author'] = $authorId;
        } else {
            $current_user = stgh_get_current_user();
            $data['post_author'] = $current_user->ID;
        }

        $insert = self::insert($data, $postId);

        return $insert;
    }

    /**
     * Adding comment to DB
     *
     * @param $data
     * @param $postId
     * @return bool|int|\WP_Error
     */
    public static function insert($data, $postId)
    {
        if (false === $postId) {
            return false;
        }

        if (!Stg_Helpdesk_Init::$isCron && !stgh_current_user_can('reply_ticket')) {
            return false;
        }

        $defaults = array(
            'post_name' => sprintf(__('Reply to ticket %s', STG_HELPDESK_TEXT_DOMAIN_NAME), "#$postId"),
            'post_title' => sprintf(__('Reply to ticket %s', STG_HELPDESK_TEXT_DOMAIN_NAME), "#$postId"),
            'post_content' => '',
            'post_status' => 'publish',
            'post_type' => STG_HELPDESK_COMMENTS_POST_TYPE,
            'post_author' => '',
            'post_parent' => $postId,
            'ping_status' => 'closed',
            'comment_status' => 'closed',
        );

        $data = wp_parse_args($data, $defaults);

        if (empty($data['post_author'])) {
            $current_user = stgh_get_current_user();
            $data['post_author'] = $current_user->ID;
        }

        $data = apply_filters('stgh_add_comment_data', $data, $postId);

        /* Sanitize the data */
        if (isset($data['post_title']) && !empty($data['post_title'])) {
            $data['post_title'] = wp_strip_all_tags($data['post_title']);
        }

        if (!empty($data['post_content'])) {
            $data['post_content'] = strip_shortcodes($data['post_content']);
        }

        if (isset($data['post_name']) && !empty($data['post_name'])) {
            $data['post_name'] = sanitize_title($data['post_name']);
        }

        /**
         * Fire stgh_add_reply_before before the reply is added to the database.
         * This hook is fired both on the back-end and the front-end.
         *
         * @param  array $data The data to be inserted to the database
         * @param  integer $post_id ID of the parent post
         */
        do_action('stgh_before_add_comment', $data, $postId);

        $replyId = wp_insert_post($data, true);

        if (is_wp_error($replyId)) {
            do_action('stgh_add_comment_failed', $data, $postId, $replyId);
        } else {
            do_action('stgh_after_add_comment', $replyId, $data);
        }

        return $replyId;
    }

    public static function edit($id = null, $content = '')
    {
        if (is_null($id)) {
            if (isset($_POST['comment_id'])) {
                $id = intval($_POST['comment_id']);
            } else {
                return false;
            }
        }

        if (empty($content)) {
            if (isset($_POST['comment_content'])) {
                $content = wp_kses($_POST['comment_content'], wp_kses_allowed_html('post'));
            } else {
                return false;
            }
        }

        $comment = get_post($id);

        if (is_null($comment)) {
            return false;
        }

        $data = apply_filters('stgh_edit_comment_data', array(
            'ID' => $id,
            'post_content' => $content,
            'post_status' => 'publish',
            'comment_status' => 'closed',
            'ping_status' => 'closed',
            'post_date' => $comment->post_date,
            'post_date_gmt' => $comment->post_date_gmt,
            'post_name' => $comment->post_name,
            'post_parent' => $comment->post_parent,
            'post_type' => $comment->post_type,
            'post_author' => $comment->post_author,
        ), $id
        );

        $edited = wp_insert_post($data, true);

        if (is_wp_error($edited)) {
            do_action('stgh_edit_comment_failed', $id, $content, $edited);

            return $edited;
        }

        do_action('stgh_comment_edited', $id);

        return $id;
    }
}