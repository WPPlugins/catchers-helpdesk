<?php
use StgHelpdesk\Helpers\Stg_Helper_Template;

add_shortcode(STG_HELPDESK_SHORTCODE_TICKET_LIST, 'stg_sc_tickets');
/**
 * View all tickets
 */
function stg_sc_tickets()
{
    ob_start();
    ?>

    <?php
    if (!stgh_get_current_user_id()) {
        Stg_Helper_Template::getTemplate('stg-not-allowed');
    } else {
        Stg_Helper_Template::getTemplate('stg-all-tickets');
    }
    ?>

    <?php
    $content = ob_get_contents();
    ob_end_clean();
    return $content;
}