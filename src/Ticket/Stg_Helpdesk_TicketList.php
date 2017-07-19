<?php

namespace StgHelpdesk\Ticket;

class Stg_Helpdesk_TicketList
{

    /**
     * Choose column for actionRows
     *
     * @param $default
     * @param $screen_id
     * @return string
     */
    public static function getPrimaryKey($default, $screen_id)
    {
        if ('edit-' . STG_HELPDESK_POST_TYPE === $screen_id) {
            return STG_HELPDESK_POST_TYPE . '-conversation';
        }

        return $default;
    }

    /**
     * Modify ticket's action rows
     *
     * @param $actions
     * @param $post
     * @return mixed
     */
    public static function actionRows($actions, $post)
    {
        if (STG_HELPDESK_POST_TYPE == get_post_type($post->ID)) {
            if (!isset($_GET['post_status']) || $_GET['post_status'] !== 'trash') {
                $oldActions = $actions;
                $_GET['del_id'] = $post->ID;
                $url = add_query_arg($_GET, admin_url('post.php'));
                $url = remove_query_arg('message', $url);
                $spam = stgh_link_to_action($url, 'spam_ticket'); //trash comment and contact
                if (stgh_ticket_is_closed($post->ID)) {
                    $actions = array(
                        'open' => '<a href="' . stgh_link_to_open_ticket($post->ID) . '">' . __('Open', STG_HELPDESK_TEXT_DOMAIN_NAME) . '</a>'
                    );
                } else {
                    $actions = array(
                        'answer' => '<a href="' . stgh_link_to_ticket($post->ID) . '">' . __('Answer', STG_HELPDESK_TEXT_DOMAIN_NAME) . '</a>'
                    );
                }

                if (stgh_current_user_can('delete_ticket'))
                    $actions['trash'] = $oldActions['trash'];

                return $actions;
            }
        }

        return $actions;
    }

    public static function tableColumns($columns)
    {
        $new = array(
            'cb' => $columns['cb'],
            'title' => $columns['title'],
            STG_HELPDESK_POST_TYPE . '-id' => __('ID', STG_HELPDESK_TEXT_DOMAIN_NAME),
            STG_HELPDESK_POST_TYPE . '-status' => __('Status', STG_HELPDESK_TEXT_DOMAIN_NAME),
            STG_HELPDESK_POST_TYPE . '-contact' => __('Contact', STG_HELPDESK_TEXT_DOMAIN_NAME),
            STG_HELPDESK_POST_TYPE . '-conversation' => __('Conversation', STG_HELPDESK_TEXT_DOMAIN_NAME),
            STG_HELPDESK_POST_TYPE . '-lastReply' => __('Last update', STG_HELPDESK_TEXT_DOMAIN_NAME)
            //'ticket_title' => $columns['title'],
            //'author' => __('Author', STG_HELPDESK_TEXT_DOMAIN_NAME),
            //'assignedTo' => __('Assigned To', STG_HELPDESK_TEXT_DOMAIN_NAME),
            //'date' => $columns['date']
        );

        return $new;
    }

    public static function tableColumnsExcerpt($columns)
    {
        $new = array(
            'cb' => $columns['cb'],
            STG_HELPDESK_POST_TYPE . '-subject' => __('Subject', STG_HELPDESK_TEXT_DOMAIN_NAME),
            STG_HELPDESK_POST_TYPE . '-contact' => __('Contact', STG_HELPDESK_TEXT_DOMAIN_NAME),
            STG_HELPDESK_POST_TYPE . '-assignedTo' => __('Assigned to', STG_HELPDESK_TEXT_DOMAIN_NAME),
            STG_HELPDESK_POST_TYPE . '-conversation-excerpt' => '',
            STG_HELPDESK_POST_TYPE . '-lastReply' => __('Last reply', STG_HELPDESK_TEXT_DOMAIN_NAME),
            STG_HELPDESK_POST_TYPE . '-status' => __('Status', STG_HELPDESK_TEXT_DOMAIN_NAME),

            //'ticket_title' => $columns['title'],
            //'author' => __('Author', STG_HELPDESK_TEXT_DOMAIN_NAME),
            //'date' => $columns['date']
        );

        return $new;
    }

    public static function columnsContent($columnName, $postId)
    {
        $post = get_post($postId);
        $user = get_userdata($post->post_author);
        $class = '';
        if (stgh_ticket_is_closed($postId)) {
            $class = 'stgh-ticket-closed';
        }
        switch ($columnName) {
            case STG_HELPDESK_POST_TYPE . '-id':
                echo '<a href="' . stgh_link_to_ticket($postId) . '" class="' . $class . '">#' . $post->ID . '</a>';
                break;
            case STG_HELPDESK_POST_TYPE . '-status':
                $statusArray = stgh_get_statuses();
                if (!isset($statusArray[$post->post_status])) {
                    if ('trash' == $post->post_status) {
                        $status = __('Trash', STG_HELPDESK_TEXT_DOMAIN_NAME);
                    } else {
                        $status = '';
                    }
                } else {
                    $status = $statusArray[$post->post_status];
                }

                $class = get_post_status($postId) . '_color';
                echo '<span class="stgh_colored_status  ' . $class . '">' . $status . '</span>';
                break;
            case STG_HELPDESK_POST_TYPE . '-contact':
                $ticket = Stg_Helpdesk_Ticket::getInstance($postId);
                $contact = $ticket->getContact();
                if (!is_null($contact) && false !== $contact) {
                    echo get_avatar($contact->user_email, '32') . '<div class="contact-info"><span class="user-name">' .
                        stgh_crm_get_user_full_name($contact->ID) . '</span></div>';
                } else {
                    echo '';
                }
                break;
            case STG_HELPDESK_POST_TYPE . '-conversation':
                $comments_count = '<span class="stgh-comments-count">' . stgh_ticket_count_answers($postId) . '</span>';
                $tags = stgh_ticket_get_tags($postId);
                if (!empty($tags)) {
                    $tags = array_map(function ($item) {
                        return '<span class="tag-item">' . $item . '</span>';
                    }, $tags);
                }
                $lastAnswer = stgh_ticket_get_last_answer($postId);

                if (is_null($lastAnswer)) {
                    $lastAnswer = $post;
                }
                $tags = implode('', $tags);
                $title = '<a class="title" href="' . stgh_link_to_ticket($postId) . '">' . $post->post_title . '</a>';
                //$body = '<p>'.esc_html($lastAnswer->post_content).'</p>';
                $body = '<p>' . strip_tags($lastAnswer->post_content) . '</p>';

                if(mb_strlen($body) > 100)
                {
                    $last = mb_strpos($body,' ',100);
                    $body = mb_substr($body,0,--$last)."...";
                }

                echo $comments_count . $tags . $title . $body;
                break;
            case STG_HELPDESK_POST_TYPE . '-lastReply':
                $lastReplyDate = $post->post_modified;
                echo human_time_diff(mysql2date('U', $lastReplyDate), current_time('timestamp'));
                break;
        }
    }

    public static function columnsContentExcerpt($columnName, $postId)
    {
        $post = get_post($postId);
        $class = '';
        if (stgh_ticket_is_closed($postId)) {
            $class = 'stgh-ticket-closed';
        }
        switch ($columnName) {
            case STG_HELPDESK_POST_TYPE . '-status':
                $statusArray = stgh_get_statuses();
                if (!isset($statusArray[$post->post_status])) {
                    if ('trash' == $post->post_status) {
                        $status = __('Trash', STG_HELPDESK_TEXT_DOMAIN_NAME);
                    } else {
                        $status = '';
                    }
                } else {
                    $status = $statusArray[$post->post_status];
                }

                $class = get_post_status(get_the_ID()) . '_excerpt_color';
                echo '<span class="stgh_colored_status_excerpt ' . $class . '">' . $status . '</span>';
                break;
            case STG_HELPDESK_POST_TYPE . '-contact':
                echo '<a href="' . stgh_link_to_contact($post->post_author) . '">' . stgh_crm_get_user_full_name($post->post_author) . '</a>';
                break;
            case STG_HELPDESK_POST_TYPE . '-subject':
                $title = '<b><a class="title" href="' . stgh_link_to_ticket($postId) . '">#' . $post->ID . ': ' . $post->post_title . '</a></b>';
                echo $title;
                break;
            case STG_HELPDESK_POST_TYPE . '-lastReply':
                $lastAnswer = stgh_ticket_get_last_answer($postId);
                $lastReplyDatePostID = $post->ID;
                if (!is_null($lastAnswer)) {
                    $lastReplyDatePostID = $lastAnswer->ID;
                }
                echo human_time_diff(get_the_time('U', $lastReplyDatePostID), current_time('timestamp'));
                break;
            case STG_HELPDESK_POST_TYPE . '-conversation-excerpt':
                $count = stgh_ticket_count_answers($postId);
                echo $count == 0 ? '—' : $count;
                break;
        }

        if ($columnName == STG_HELPDESK_POST_TYPE . '-assignedTo') {
            $user = Stg_Helpdesk_Ticket::getAssignedTo($postId);
            if (!is_null($user)) {
                echo '<a href="' . stgh_link_to_assigned_to($postId, $user->ID) . '">' . $user->display_name . '</a>';
            }
        }
    }

    public static function columnsOrdering($columns)
    {
        $columns[STG_HELPDESK_POST_TYPE . '-status'] = 'post_status';
        $columns[STG_HELPDESK_POST_TYPE . '-id'] = 'ID';
        $columns[STG_HELPDESK_POST_TYPE . '-lastReply'] = 'post_modified';

        return $columns;
    }

    /**
     * Add a filter on the field Assigned
     */
    public static function assignToFilter()
    {
        global $typenow;

        if (STG_HELPDESK_POST_TYPE != $typenow) {
            return;
        }

        $users = self::getUsers();
        $dropdown = self::getUsersDropdown($users);

        echo '<span class="stgh-ticketlist-filter">' . __('Assigned to: ') . '</span>' . $dropdown;
    }

    /**
     * Filter fo company
     */
    public static function crmCompanyFilter()
    {
        global $typenow;

        //CRM removed
        //return;

        if (STG_HELPDESK_POST_TYPE != $typenow) {
            return;
        }

        $crmCompany = stgh_crm_get_companies();
        $dropdown = self::getCrmCompaniesDropdown($crmCompany);

        echo $dropdown;
    }

    
    /**
     * Forming select companies for filtration
     *
     * @param array $companies
     * @return string
     */
    protected static function getCrmCompaniesDropdown($companies)
    {
        $dropdown = '<select id="crmCompany" name="crmCompany">';
        $default = isset($_GET['crmCompany']) ? html_entity_decode(filter_input(INPUT_GET, 'crmCompany',
            FILTER_SANITIZE_STRING)) : '';
        array_unshift($companies, "&nbsp;");
        foreach ($companies as $key => $company) {
            $selected = $company == $default ? 'selected="selected"' : '';
            if (0 == $key) {
                $dropdown .= "<option value='' $selected>" . __('Show all companies', STG_HELPDESK_TEXT_DOMAIN_NAME) . "</option>";
            } else {
                $dropdown .= "<option value='$company' $selected>$company</option>";
            }
        }
        $dropdown .= '</select>';

        return $dropdown;
    }

    /**
     * Forming select users for filtration
     *
     * @param array $users
     * @return string
     */
    protected static function getUsersDropdown($users)
    {
        $dropdown = '<select id="assignedTo" name="assignedTo">';
        $defaultAssign = isset($_GET['assignedTo']) ? filter_input(INPUT_GET, 'assignedTo', FILTER_SANITIZE_STRING) : 0;

        foreach ($users as $id => $name) {
            $selected = $id == $defaultAssign ? 'selected="selected"' : '';
            $dropdown .= "<option value='$id' $selected>$name</option>";
        }
        $dropdown .= '</select>';

        return $dropdown;
    }

    /**
     * Get users
     *
     * @return array
     */
    protected static function getUsers()
    {
        $current_user = stgh_get_current_user();

        $users = stgh_get_users(array('role' => 'stgh_manager'));
        $userList = array();
        $userList[0] = "&nbsp;";
        $userList[$current_user->ID] = __('&lt;&lt;Me&gt;&gt;', STG_HELPDESK_TEXT_DOMAIN_NAME);
        foreach ($users as $user) {
            if ($user->ID != $current_user->ID) {
                $userList[$user->ID] = $user->data->display_name;
            }
        }

        return $userList;
    }
}