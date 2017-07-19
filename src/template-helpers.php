<?php
use StgHelpdesk\Core\PostType\Stg_Helpdesk_Post_Type_Statuses;
use StgHelpdesk\Helpers\Stg_Helper_Template;
use StgHelpdesk\Ticket\Stg_Helpdesk_Ticket;
use StgHelpdesk\Ticket\Stg_Helpdesk_TicketComments;
use StgHelpdesk\Admin\Stg_Helpdesk_Help_Catcher;
use StgHelpdesk\Helpers\Stg_Helper_UploadFiles;

if (!function_exists('stgh_link_to_tickets')) {
    function stgh_link_to_tickets()
    {
        return esc_url(add_query_arg(array('post_type' => STG_HELPDESK_POST_TYPE), admin_url('edit.php')));
    }
}

if (!function_exists('stgh_link_to_ticket')) {
    function stgh_link_to_ticket($ticket_id)
    {
        $args = $_GET;
        $args['post'] = intval($ticket_id);
        $args['action'] = 'edit';

        return add_query_arg($args, admin_url('post.php'));
    }
}
if (!function_exists('stgh_link_to_settings')) {
    function stgh_link_to_settings($settings_page = '')
    {
        $args['post_type'] = STG_HELPDESK_POST_TYPE;
        $args['page'] = 'settings';
        if ($settings_page)
            $args['tab'] = $settings_page;

        return add_query_arg($args, admin_url('edit.php'));
    }
}
if (!function_exists('stgh_link_to_welcome_page')) {
    function stgh_link_to_welcome_page()
    {
        return add_query_arg(array('post_type' => STG_HELPDESK_POST_TYPE, 'page' => 'stgh-welcome'),
            admin_url('edit.php'));
    }
}

if (!function_exists('stgh_link_to_wizard')) {
    function stgh_link_to_wizard()
    {
        return add_query_arg(array('post_type' => STG_HELPDESK_POST_TYPE, 'page' => 'stgh-wizard'),
            admin_url('edit.php'));
    }
}

if (!function_exists('stgh_link_to_create_user_pages')) {
    function stgh_link_to_create_user_pages()
    {
        $siteUrl = get_site_url();
        return $siteUrl."/wp-admin/?stgh-do=create-user-pages";

    }
}

if (!function_exists('stgh_link_to_skip_setting')) {
    function stgh_link_to_skip_setting()
    {
        return add_query_arg(array('post_type' => STG_HELPDESK_POST_TYPE),admin_url('edit.php'));
    }
}


if (!function_exists('stgh_link_to_assigned_to')) {
    function stgh_link_to_assigned_to($ticket_id, $assignedToId)
    {
        $args = $_GET;
        $args['post'] = intval($ticket_id);
        $args['assignedTo'] = intval($assignedToId);

        return add_query_arg($args, admin_url('edit.php'));
    }
}

if (!function_exists('stgh_link_to_contact')) {
    function stgh_link_to_contact($authorId)
    {
        $args = $_GET;
        $args['author'] = intval($authorId);

        return add_query_arg($args, admin_url('edit.php'));
    }
}

if (!function_exists('stgh_link_to_close_ticket')) {
    function stgh_link_to_close_ticket($ticket_id)
    {
        $args = $_GET;
        $args['post'] = intval($ticket_id);

        return stgh_link_to_action(add_query_arg($args, admin_url('post.php')), 'close');
    }
}

if (!function_exists('stgh_link_to_open_ticket')) {
    function stgh_link_to_open_ticket($ticket_id)
    {
        $args = $_GET;
        $args['post'] = intval($ticket_id);

        return stgh_link_to_action(add_query_arg($args, admin_url('post.php')), 'open');
    }
}

if (!function_exists('stgh_link_to_action')) {
    /**
     * Add custom action and nonce to URL.
     *
     * @param $url
     * @param $action
     * @return string
     */
    function stgh_link_to_action($url, $action)
    {
        return stgh_nonce_url(add_query_arg(array('stgh-do' => sanitize_text_field($action)), $url));
    }
}

if (!function_exists('stgh_show_status')) {
    /**
     * Show status
     * @param $postId
     *
     */
    function stgh_show_status($postId)
    {
        $defaults = array(
            'closed' => '#dd3333',
            'new' => '#1e73be',
            'answered' => '#a01497',
            'notanswered' => '#b56629',
            'custom' => '#169baa'
        );
        $statuses = Stg_Helpdesk_Post_Type_Statuses::get();
        $status = Stg_Helpdesk_Ticket::getInstance()->getStatus();
        if (!array_key_exists($status, $statuses)) {
            $label = __('Open', STG_HELPDESK_TEXT_DOMAIN_NAME);
            $defaultColor = $defaults['custom'];
        } else {
            $label = $statuses[$status];
            $defaultColor = $defaults[$status];
        }

        $color = stgh_get_option("{$status}_color", $defaultColor);


        echo "<span class='stgh-label'>$label</span>";
    }
}

if (!function_exists('stgh_display_assign_to_select')) {
    function stgh_display_assign_to_select($args = array(), $assignedToId, $nameField = 'stgh_assignee')
    {
        $current_user = stgh_get_current_user();
        $defaults = array(
            'name' => $nameField,
            'id' => $nameField,
            'class' => '',
            'exclude' => array(),
            'cap' => 'edit_ticket',
            'please_select' => false,
            'select2' => true,
            'cap_exclude' => '',
            'disabled' => !stgh_current_user_can('assign_ticket') ? true : false
        );

        $args = wp_parse_args($args, $defaults);

        $selected_attr = 0 == $assignedToId ? 'selected="selected"' : '';
        $options = "<option value='0' $selected_attr>&nbsp;</option>";
        $options .= "<option value='{$current_user->ID}' " . ($current_user->ID == $assignedToId ? "selected=\"selected\"" : "") . ">" . __('&lt;&lt;Me&gt;&gt;', STG_HELPDESK_TEXT_DOMAIN_NAME) . "</option>";

        foreach (stgh_get_users($args) as $user) {
            $user_id = $user->ID;
            if ($current_user->ID == $user_id) {
                continue;
            }
            $user_name = $user->data->display_name;
            $selected_attr = $user_id == $assignedToId ? 'selected="selected"' : '';

            /* Output the option */
            $options .= "<option value='$user_id' $selected_attr>$user_name</option>";
        }

        $contents = stgh_dropdown($args, $options);

        return $contents;
    }
}

if (!function_exists('stgh_dropdown')) {
    function stgh_dropdown($args, $options)
    {
        $defaults = array(
            'name' => 'stgh_dropdown',
            'id' => 'stgh_dropdown',
            'class' => '',
            'please_select' => false,
            'select2' => false,
            'disabled' => false
        );

        $args = wp_parse_args($args, $defaults);

        $class = (array)$args['class'];

        if (true === $args['select2']) {
            array_push($class, 'stgh-select2');
        }

        /* Start the buffer */
        ob_start(); ?>

        <select name="<?php echo $args['name']; ?>" <?php if (!empty($class)) {
            echo 'class="' . implode(' ', $class) . '"';
        }
        if (true === $args['disabled']) {
            echo 'disabled';
        } ?>>
            <?php
            if ($args['please_select']) {
                echo '<option value="">' . __('Please select', STG_HELPDESK_TEXT_DOMAIN_NAME) . '</option>';
            }

            echo $options;
            ?>
        </select>

        <?php
        /* Get the buffer contents */
        $contents = ob_get_contents();

        /* Clean the buffer */
        ob_end_clean();

        return $contents;

    }
}

if (!function_exists('stgh_ticket_comment_actions')) {
    function stgh_ticket_comment_actions($controls, $ticketId, $comment)
    {
        //d($ticketId, $comment->post_author);
        if (0 !== $ticketId) {

            $_GET['del_id'] = $comment->ID;
            $url = add_query_arg($_GET, admin_url('post.php'));
            $url = remove_query_arg('message', $url);
            $delete = stgh_link_to_action($url, 'trash_comment'); //trash_reply
            //$spam = stgh_link_to_action($url, 'spam_comment'); //trash comment and contact
            $edit = wp_nonce_url(
                add_query_arg(
                    array(
                        'post' => $ticketId,
                        'rid' => $comment->ID,
                        'action' => 'edit_comment' // edit_reply
                    ),
                    admin_url('post.php')
                ),
                'delete_reply_' . $comment->ID //delete_reply_
            );
            // User data
            $user_meta = Stg_Helpdesk_TicketComments::getCommentUserMeta($comment->ID);
            $user_roles = $user_meta['roles'] ? explode(' ', $user_meta['roles']) : array();
            $user_email = $user_meta['email'];

            if (in_array('stgh_client', $user_roles) || in_array('subscriber', $user_roles)) {
                $controls['quote_reply'] = sprintf('<div id="textareadiv"></div><a class="%1$s" href="%2$s" title="%3$s" data-replyid="%4$d" data-mail="%5$s">%3$s</a>', 'stgh-quote',
                    '#', esc_html_x('Quote', 'Link to quote a ticket comment', STG_HELPDESK_TEXT_DOMAIN_NAME), $comment->ID, $user_email);
            }

            /* $controls['edit_reply'] = sprintf('<a class="%1$s" href="%2$s" data-origin="%3$s" data-replyid="%4$d" data-reply="%5$s" data-wysiwygid="%6$s" title="%7$s">%7$s</a>',
                'stgh-edit', '#', "#stgh-comment-$comment->ID", $comment->ID, "stgh-editwrap-$comment->ID",
                "stgh-editcomment-$comment->ID", esc_html_x('Edit', 'Link ot edit a ticket comment', STG_HELPDESK_TEXT_DOMAIN_NAME));*/


            if (stgh_current_user_can('delete_reply')) {
                $controls['delete_reply'] = sprintf('<a class="%1$s" href="%2$s" title="%3$s">%3$s</a>', 'stgh-delete color-red',
                    esc_url($delete), esc_html_x('Delete', 'Link to delete a ticket comment', STG_HELPDESK_TEXT_DOMAIN_NAME));
            }
        }

        return $controls;
    }
}

if (!function_exists('stgh_ticket_actions')) {
    function stgh_ticket_actions($controls, $ticketId)
    {
        if (0 !== $ticketId) {

            $_GET['del_id'] = $ticketId;
            $url = add_query_arg($_GET, admin_url('post.php'));
            $url = remove_query_arg('message', $url);
            $spam = stgh_link_to_action($url, 'spam_ticket'); //trash comment and contact

            $controls['quote_reply'] = sprintf('<div id="textareadiv"></div><a class="%1$s" href="%2$s" title="%3$s" data-replyid="%4$d" data-mail="">%3$s</a>', 'stgh-quote',
                '#', esc_html_x('Quote', 'Link to quote a ticket comment', STG_HELPDESK_TEXT_DOMAIN_NAME), $ticketId);


        }

        return $controls;
    }
}

if (!function_exists('stgh_can_view_ticket')) {
    function stgh_can_view_ticket($post_id)
    {
        $can = false;

        $post = get_post($post_id);
        $author_id = intval($post->post_author);

        if (is_user_logged_in()) {
            if ((stgh_current_user_can('view_ticket') || stgh_current_user_can('edit_ticket')) && get_current_user_id() === $author_id) {
                $can = true;
            }
        }

        return apply_filters('stgh_can_view_ticket', $can, $post_id, $author_id);
    }
}

if (!function_exists('getNotificationMarkup')) {
    function getNotificationMarkup($type = 'info', $message = '')
    {
        if (empty($message)) {
            return '';
        }

        $classes = apply_filters('stgh_notification_classes', array(
            'success' => 'stgh-alert stgh-alert-success',
            'failure' => 'stgh-alert stgh-alert-danger',
            'info' => 'stgh-alert stgh-alert-info',
        ));

        if (!array_key_exists($type, $classes)) {
            $type = 'info';
        }

        $markup = apply_filters('stgh_notification_wrapper', '<div class="%s">%s</div>');
        $markup = apply_filters('stgh_notification_markup', sprintf($markup, $classes[$type], $message), $type);

        return $markup;
    }
}

if (!function_exists('get_page_single_ticket')) {
    function get_page_single_ticket($content)
    {
        global $post;

        if (is_admin()) {
            return $content;
        }

        if ($post && STG_HELPDESK_POST_TYPE !== $post->post_type) {
            return $content;
        }

        if (!is_main_query()) {
            return $content;
        }

        if (!in_the_loop()) {
            return $content;
        }

        remove_filter('the_content', 'get_page_single_ticket');

        if (!stgh_check_user_access($post->ID)) {
            stgh_404();
        }

        $template = explode('/', get_page_template());
        $template = $template[count($template) - 1];
        if ('single-' . STG_HELPDESK_POST_TYPE . '.php' === $template) {
            return $content;
        }

        ob_start();

        do_action('stgh_frontend_plugin_page_top', $post->ID, $post);

        Stg_Helper_Template::getTemplate('stg-single-ticket');

        $content = ob_get_clean();

        return $content;
    }
}

if (!function_exists('get_css_color_status')) {
    function get_css_color_status($status, $default = '#000000')
    {
        $color = stgh_get_option($status);
        $css = '.' . $status . ' {color:';

        $css .= ($color) ? $color : $default;
        $css .= '}';

        return $css;
    }
}
if (!function_exists('stgh_ticket_category_list')) {
    function stgh_ticket_category_list($categoryItem = null, $defOption = null, $class = null, $required = false)
    {
        $categories = stgh_ticket_get_categories();

        if (!empty($categories)) {

            if(!$required)
            {
                $requiredStr ='';
                $defValue = 0;
            }else{
                $defValue = '';
                $requiredStr = 'required="required"';
            }


            $list = '<select ' . ($class ? 'class=' . $class : '') . ' name="stgh_category" '.$requiredStr.'>';
            $list .= "<option value='{$defValue}'>" . (is_null($defOption) ? "&nbsp;" : $defOption) . "</option>";
            foreach ($categories as $category) {
                $selected = !is_null($categoryItem) && $categoryItem->term_id == $category->term_id ? 'selected="selected"' : 0;
                $list .= '<option value="' . $category->term_id . '" ' . $selected . '>' . $category->name . '</option>';
            }
            $list .= '</select>';

            return $list;
        }

        return;
    }
}

if (!function_exists('stgh_ticket_tags_list')) {
    function stgh_ticket_tags_list($tagItem = null, $defOption = null, $class = null, $name = 'stgh_tags',$required = false)
    {
        $tags = stgh_ticket_get_tags_all();

        if(!$required)
        {
            $requiredStr ='';
            $defValue = 0;
        }else{
            $defValue = '';
            $requiredStr = 'required="required"';
        }


        if (!empty($tags)) {
            $list = '<select ' . ($class ? 'class=' . $class : '') . ' name="'.$name.'" '.$requiredStr.'>';
            $list .= "<option value='{$defValue}'>" . (is_null($defOption) ? "&nbsp;" : $defOption) . "</option>";
            foreach ($tags as $tag) {
                $selected = !is_null($tagItem) && $tagItem->term_id == $tag->term_id ? 'selected="selected"' : 0;
                $list .= '<option value="' . $tag->term_id . '" ' . $selected . '>' . $tag->name . '</option>';
            }
            $list .= '</select>';

            return $list;
        }

        return;
    }
}


