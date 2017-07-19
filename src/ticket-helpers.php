<?php
use StgHelpdesk\Ticket\Stg_Helpdesk_MetaBoxes;
use StgHelpdesk\Ticket\Stg_Helpdesk_Ticket;
use StgHelpdesk\Ticket\Stg_Helpdesk_TicketComments;
use StgHelpdesk\Helpers\Stg_Helper_UploadFiles;
use StgHelpdesk\Helpers\Stg_Helper_Email;
use StgHelpdesk\Admin\Stg_Helpdesk_Help_Catcher;

if (!function_exists('stgh_get_statuses')) {
    function stgh_get_statuses()
    {
        return \StgHelpdesk\Core\PostType\Stg_Helpdesk_Post_Type_Statuses::get();
    }
}

if (!function_exists('stgh_get_priorities')) {
    function stgh_get_priorities()
    {
        return \StgHelpdesk\Core\PostType\Stg_Helpdesk_Post_Type_Priority::get();
    }
}

if (!function_exists('stgh_ticket_get_priority')) {
    function stgh_ticket_get_priority()
    {
        return Stg_Helpdesk_Ticket::getInstance()->getPriority();
    }
}

if (!function_exists('stgh_ticket_get_status')) {
    function stgh_ticket_get_status($postId = null)
    {
        return Stg_Helpdesk_Ticket::getInstance($postId)->getStatus();
    }
}

if (!function_exists('stgh_ticket_get_user')) {
    function stgh_ticket_get_user($postId = null)
    {
        return Stg_Helpdesk_Ticket::getInstance($postId)->getUser();
    }
}

if (!function_exists('stgh_ticket_get_human_creation_time')) {
    function stgh_ticket_get_human_creation_time()
    {
        return human_time_diff(get_the_time('U'), current_time('timestamp'));
    }
}

if (!function_exists('stgh_ticket_get_comments')) {
    function stgh_ticket_get_comments($params = array())
    {
        return Stg_Helpdesk_TicketComments::instance()->get($params);
    }
}

if (!function_exists('stgh_ticket_get_comments_count')) {
    function stgh_ticket_get_comments_count($params = array())
    {
        return Stg_Helpdesk_TicketComments::instance()->getCount($params);
    }
}

if (!function_exists('stgh_ticket_is_opened')) {
    function stgh_ticket_is_opened($postId = null)
    {
        return Stg_Helpdesk_Ticket::getInstance($postId)->isOpened();
    }
}

if (!function_exists('stgh_ticket_is_closed')) {
    function stgh_ticket_is_closed($postId = null)
    {
        return Stg_Helpdesk_Ticket::getInstance($postId)->isClosed();
    }
}

if (!function_exists('stgh_is_called_directly')) {
    function stgh_is_called_directly()
    {
        if (!defined('WPINC')) {
            die;
        }
    }
}

if (!function_exists('stgh_save_custom_fields')) {
    function stgh_save_custom_fields($postId)
    {
        Stg_Helpdesk_MetaBoxes::saveMetaBoxFields($postId);
    }
}

if (!function_exists('stgh_save_custom_fields_3_6')) {
    function stgh_save_custom_fields_3_6($postId, $post)
    {
        remove_action('save_post', 'stgh_save_custom_fields_3_6');
        if ($post->post_type == STG_HELPDESK_POST_TYPE) {
            Stg_Helpdesk_MetaBoxes::saveMetaBoxFields($postId);
        }
    }
}

if (!function_exists('stgh_ticket_get_named_status')) {
    function stgh_ticket_get_named_status($postId = null)
    {
        $status = stgh_ticket_get_status($postId);
        $statuses = stgh_get_statuses();

        if (array_key_exists($status, $statuses)) {
            return $statuses[$status];
        }

        return '';
    }
}

if (!function_exists('stgh_ticket_assigned_to')) {
    function stgh_ticket_assigned_to($postId)
    {
        return Stg_Helpdesk_Ticket::getAssignedTo($postId);
    }
}

if (!function_exists('stgh_ticket_admin_link')) {
    function stgh_ticket_admin_link($id)
    {
        return admin_url('post.php?post=' . $id) . '&action=edit&post_type=' . STG_HELPDESK_POST_TYPE;
    }
}

if (!function_exists('stgh_link_to_admin_panel')) {
    function stgh_link_to_admin_panel()
    {
        return admin_url('edit.php?post_type=' . STG_HELPDESK_POST_TYPE);
    }
}

if (!function_exists('stgh_link_to_all_user_tickets')) {
    function stgh_link_to_all_user_tickets($data, $byCompany = false)
    {
        $path = 'edit.php?post_type=' . STG_HELPDESK_POST_TYPE;
        if ($byCompany) {
            $path .= '&crmCompany=' . urlencode($data);
        } else {
            $path .= '&authorId=' . $data;
        }
        return admin_url($path);
    }
}

if (!function_exists('stgh_ticket_get_categories')) {
    function stgh_ticket_get_categories()
    {
        return get_terms(STG_HELPDESK_POST_TYPE_CATEGORY, array('hide_empty' => false));
    }
}


if (!function_exists('stgh_ticket_get_category')) {
    function stgh_ticket_get_category($postId)
    {
        $res = wp_get_post_terms($postId, STG_HELPDESK_POST_TYPE_CATEGORY, array());

        if (!empty($res)) {
            return $res[0];
        }

        return null;
    }
}

if (!function_exists('stgh_ticket_get_related_tickets')) {
    function stgh_ticket_get_related_tickets($userId, $postId, $page = 1)
    {
        $userCompany = stgh_crm_get_user_company($userId);

        if (false !== $userCompany) {
            $userIds = stgh_crm_get_users_by_company($userCompany);
            $metaQuery = array('relation' => 'OR');
            foreach($userIds as $userId)
            {
                $metaQuery[] = array('key' => '_stgh_contact','value' => $userId);
            }
        }else{
            $metaQuery = array(
                array(
                    'key' => '_stgh_contact',
                    'value' => $userId
                )
            );
        }

        return new \WP_Query(
            array(
                'post_type' => STG_HELPDESK_POST_TYPE,
                'post_status' => 'any',
                'paged' => $page,
                'posts_per_page' => 5,
                'post__not_in' => array($postId),
                'orderby' => 'post_date',
                'order' => 'DESC',
                'meta_query' => $metaQuery
            )
        );
    }
}

if (!function_exists('stgh_ticket_get_tags_all')) {
    function stgh_ticket_get_tags_all()
    {
        return get_terms(STG_HELPDESK_POST_TYPE_TAG, array('hide_empty' => false));
    }
}


if (!function_exists('stgh_ticket_get_tags')) {
    function stgh_ticket_get_tags($postId, $count = 3)
    {
        //$tags = wp_get_post_tags($postId, array('fields' => 'names'));

        $args = array(
            "fields" => 'names'
        );

        $tags = wp_get_object_terms($postId, STG_HELPDESK_POST_TYPE_TAG, $args);

        return array_slice($tags, 0, $count);
    }
}

if (!function_exists('stgh_ticket_count_answers')) {
    function stgh_ticket_count_answers($postId)
    {
        global $wpdb;

        $query = 'SELECT COUNT(`ID`) FROM `' . $wpdb->posts . '`
                      WHERE `post_parent`= ' . $postId . '
                      AND `post_status` NOT IN ("trash")
                      AND `post_type`="' . STG_HELPDESK_COMMENTS_POST_TYPE . '"';

        return intval($wpdb->get_var($query));
    }
}

if (!function_exists('stgh_ticket_get_last_answer')) {
    /**
     * @param int $postId
     * @return \WP_Post
     */
    function stgh_ticket_get_last_answer($postId)
    {
        $args = array(
            'post_parent' => $postId,
            'post_type' => STG_HELPDESK_COMMENTS_POST_TYPE,
            'orderby' => 'date',
            'order' => 'DESC',
            'posts_per_page' => 1

        );
        $my_query = new \WP_Query($args);
        if ($my_query->have_posts()) {
            return reset($my_query->posts);
        }

        return null;
    }
}

if (!function_exists('stgh_ticket_get_first_answer')) {
    /**
     * @param int $postId
     * @return \WP_Post
     */
    function stgh_ticket_get_first_answer($postId)
    {
        $args = array(
            'post_parent' => $postId,
            'post_type' => STG_HELPDESK_COMMENTS_POST_TYPE,
            'orderby' => 'date',
            'order' => 'ASC',
            'posts_per_page' => 1

        );
        $my_query = new \WP_Query($args);
        if ($my_query->have_posts()) {
            return reset($my_query->posts);
        }

        return null;
    }
}

if (!function_exists('stgh_update_taxonomy_count')) {
    function stgh_update_taxonomy_count($terms, $taxonomy)
    {
        $object_types = (array)$taxonomy->object_type;
        foreach ($object_types as &$object_type) {
            if (0 === strpos($object_type, 'attachment:'))
                list($object_type) = explode(':', $object_type);
        }

        if ($object_types == array_filter($object_types, 'post_type_exists')) {
            // Only post types are attached to this taxonomy
            stgh_update_taxonomy_count_now($terms, $taxonomy);
        } else {
            // Default count updater
            _update_generic_term_count($terms, $taxonomy);
        }
    }
}


if (!function_exists('stgh_update_taxonomy_count_now')) {
    function stgh_update_taxonomy_count_now($terms, $taxonomy)
    {
        global $wpdb;

        $object_types = (array)$taxonomy->object_type;

        foreach ($object_types as &$object_type)
            list($object_type) = explode(':', $object_type);

        $object_types = array_unique($object_types);

        if (false !== ($check_attachments = array_search('attachment', $object_types))) {
            unset($object_types[$check_attachments]);
            $check_attachments = true;
        }

        if ($object_types)
            $object_types = esc_sql(array_filter($object_types, 'post_type_exists'));

        $stghStatuses = array_keys(stgh_get_statuses());

        foreach ((array)$terms as $term) {
            $count = 0;

            // Attachments can be 'inherit' status, we need to base count off the parent's status if so.
            if ($check_attachments)
                $count += (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $wpdb->term_relationships, $wpdb->posts p1 WHERE p1.ID = $wpdb->term_relationships.object_id AND ( post_status = 'publish' OR ( post_status = 'inherit' AND post_parent > 0 AND ( SELECT post_status FROM $wpdb->posts WHERE ID = p1.post_parent ) = 'publish' ) ) AND post_type = 'attachment' AND term_taxonomy_id = %d", $term));

            if ($object_types)
                $count += (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $wpdb->term_relationships, $wpdb->posts WHERE $wpdb->posts.ID = $wpdb->term_relationships.object_id AND post_status IN ('" . implode("', '", $stghStatuses) . "') AND post_type IN ('" . implode("', '", $object_types) . "') AND term_taxonomy_id = %d", $term));


            /** This action is documented in wp-includes/taxonomy.php */
            do_action('edit_term_taxonomy', $term, $taxonomy->name);
            $wpdb->update($wpdb->term_taxonomy, compact('count'), array('term_taxonomy_id' => $term));

            /** This action is documented in wp-includes/taxonomy.php */
            do_action('edited_term_taxonomy', $term, $taxonomy->name);
        }
    }
}


if (!function_exists('stgh_menu_count_tickets')) {
    function stgh_menu_count_tickets()
    {
        $current_user = stgh_get_current_user();
        $statuses = stgh_get_statuses();

        unset($statuses['stgh_closed']);

        $params = array(
            'post_status' => array_keys($statuses)
        );

        if (!stgh_current_user_can('administrator') && stgh_current_user_can('edit_ticket')) {
            $params['meta_query'][] = array(
                'key' => '_stgh_assignee',
                'value' => $current_user->ID,
                'compare' => '=',
            );
        }

        return count(Stg_Helpdesk_Ticket::get($params));
    }
}

if( !function_exists('apache_request_headers') ) {
    function apache_request_headers() {
        $arh = array();
        $rx_http = '/\AHTTP_/';
        foreach($_SERVER as $key => $val) {
            if( preg_match($rx_http, $key) ) {
                $arh_key = preg_replace($rx_http, '', $key);
                $rx_matches = array();

                $rx_matches = explode('_', $arh_key);
                if( count($rx_matches) > 0 and strlen($arh_key) > 2 ) {
                    foreach($rx_matches as $ak_key => $ak_val) $rx_matches[$ak_key] = ucfirst($ak_val);
                    $arh_key = implode('-', $rx_matches);
                }
                $arh[$arh_key] = $val;
            }
        }
        return( $arh );
    }
}


