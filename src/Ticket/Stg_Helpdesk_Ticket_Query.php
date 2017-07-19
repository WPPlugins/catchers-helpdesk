<?php

namespace StgHelpdesk\Ticket;

class Stg_Helpdesk_Ticket_Query
{

    /**
     * @param $query
     * @return bool
     */
    public static function limitAny($query)
    {
        if (!self::checks($query)) {
            return false;
        }

        if (!isset($_GET['post_status'])) {
            $query->set('post_status', array('stgh_new', 'stgh_answered', 'stgh_notanswered'));
        }
    }

    /**
     * @param $query
     * @return bool
     */
    public static function checks($query)
    {
        if (!$query->is_main_query()) {
            return false;
        }

        if (!is_admin()) {
            return false;
        }

        if (!isset($_GET['post_type']) || !stgh_is_our_post_type($_GET['post_type'])) {
            return false;
        }

        return true;
    }

    /**
     * @param $query WP_Query
     * @return bool
     */
    public static function ordering($query)
    {
        if (!self::checks($query)) {
            return false;
        }

        $orderby = $query->get('orderby');
        if( 'Type' == $orderby ) {
            $query->set('meta_key','custom_field_type_meta_type');
            $query->set('orderby','meta_value');
        }elseif ('assignedTo' == $orderby) {
            $query->set('meta_key', '_stgh_assignee');
            $query->set('orderby', 'meta_value_num');
        } elseif ('contact' == $orderby) {
            $query->set('meta_key', 'first_name');
            $query->set('orderby', 'meta_value_num');
        }
    }

    /**
     * @param $query
     * @return bool
     */
    public static function assignedTo($query)
    {
        if (!self::checks($query)) {
            return false;
        }

        $id = filter_input(INPUT_GET, 'assignedTo', FILTER_SANITIZE_NUMBER_INT);

        if (0 != $id) {
            $query->set('meta_key', '_stgh_assignee');
            $query->set('meta_value', $id);
        } else {
            unset($_GET['assignedTo']);
        }
    }

    /**
     * Adding condition of having a category to post list
     *
     * @param $query
     * @return bool
     */
    public static function ticketHasCategory($query)
    {
        if (!self::checks($query)) {
            return false;
        }
        $id = filter_input(INPUT_GET, 'stgh_category', FILTER_SANITIZE_NUMBER_INT);

        if (0 != $id) {
            $taxquery = array(
                //'relation' => 'OR',
                array(
                    'taxonomy' => 'ticket_category',
                    'field' => 'term_id',
                    'terms' => array($id),
                ),
            );
            $query->set('tax_query', $taxquery);
        } else {
            unset($_GET['stgh_category']);
        }
    }

    /**
     * @param $query
     * @return bool
     */
    public static function authorId($query)
    {
        if (!self::checks($query)) {
            return false;
        }

        $id = filter_input(INPUT_GET, 'authorId', FILTER_SANITIZE_NUMBER_INT);

        if (0 != $id) {
            $query->set('author', $id);
        } else {
            unset($_GET['authorId']);
        }
    }

    /**
     * @param $query
     * @return bool
     */
    public static function filter($query)
    {
        if (!self::checks($query)) {
            return false;
        }

        if (!empty($_GET['crmCompany'])) {
            $crmCompany = html_entity_decode(filter_input(INPUT_GET, 'crmCompany', FILTER_SANITIZE_STRING));
            if (!empty($crmCompany)) {
                $ids = self::getUserIdsByCrmCompany($crmCompany);
                $query->set('author', $ids);
            } else {
                unset($_GET['crmCompany']);
            }
        }

        return $query;
    }

    /**
     * Get users id by company name
     *
     * @param $company
     * @return string
     */
    protected static function getUserIdsByCrmCompany($company)
    {
        global $wpdb;
        $users = $wpdb->get_results(
            $wpdb->prepare("SELECT DISTINCT user_id
                    FROM $wpdb->usermeta
                    WHERE meta_key = '_stgh_crm_company'
                    AND meta_value = %s", $company)
        );

        $authorIds = array();
        foreach ($users as $user) {
            $authorIds[] = $user->user_id;
        }

        return implode(',', $authorIds);
    }
}