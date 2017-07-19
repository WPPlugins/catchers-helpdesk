<?php

namespace StgHelpdesk\Core\PostType;

class Stg_Helpdesk_Post_Type_Priority
{
    public static function get()
    {
        $status = array(
            'low' => _x('Low', 'Ticket priority', STG_HELPDESK_TEXT_DOMAIN_NAME),
            'normal' => _x('Normal', 'Ticket priority', STG_HELPDESK_TEXT_DOMAIN_NAME),
            'high' => _x('High', 'Ticket priority', STG_HELPDESK_TEXT_DOMAIN_NAME),
            'urgent' => _x('Urgent', 'Ticket priority', STG_HELPDESK_TEXT_DOMAIN_NAME),
            'immediately' => _x('Immediately', 'Ticket priority', STG_HELPDESK_TEXT_DOMAIN_NAME)
        );

        return apply_filters('stgh_ticket_priority', $status);
    }
}