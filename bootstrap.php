<?php
// If this file is called directly, abort.
if (! defined('WPINC')) {
    die;
}

/*----------------------------------------------------------------------------*
 * Shortcuts
 *----------------------------------------------------------------------------*/

define('STG_HELPDESK_VERSION', '2.3.1');
define('STG_HELPDESK_NAME', 'catchers-helpdesk');
define('STG_HELPDESK_TEXT_DOMAIN_NAME', 'catchers-helpdesk');
define('STG_HELPDESK_POST_TYPE', 'stgh_ticket');
define('STG_HELPDESK_POST_TYPE_CATEGORY', 'ticket_category');
define('STG_HELPDESK_POST_TYPE_TAG', 'ticket_tag');
define('STG_HELPDESK_COMMENTS_POST_TYPE', 'stgh_ticket_comments');
define('STG_HELPDESK_HISTORY_POST_TYPE', 'stgh_ticket_history');
define('STG_HELPDESK_SLUG', 'ticket');
define('STG_HELPDESK_URL', trailingslashit(plugin_dir_url(__FILE__)).'public/');
define('STG_HELPDESK_ROOT', trailingslashit(plugin_dir_path(__FILE__)));
define('STG_HELPDESK_PATH', STG_HELPDESK_ROOT.'src/');
define('STG_HELPDESK_PUBLIC', trailingslashit(plugin_dir_path(__FILE__)).'public/');
define('STG_HELPDESK_SALT_USER', '64Zhff54gf5gfff$FDSAeehnm5G89jfF');
define('STG_HELPDESK_SHORTCODE_TICKET_LIST', 'tickets');
define('STG_HELPDESK_SHORTCODE_TICKET_FORM', 'ticket-form');

/*----------------------------------------------------------------------------*
 * Shared functionality
 *----------------------------------------------------------------------------*/
require_once STG_HELPDESK_PATH.'helpers.php';
require_once STG_HELPDESK_PATH.'template-helpers.php';
require_once STG_HELPDESK_PATH.'ticket-helpers.php';
require_once STG_HELPDESK_PATH.'mailbox-helpers.php';
require_once STG_HELPDESK_PATH.'crm-helpers.php';

/**
 * Get an instance of the plugin mycatchers
 */
add_action( 'init', 'stgh_auth_cookie');
add_action( 'plugins_loaded', array( '\StgHelpdesk\Core\Stg_Helpdesk_Init', 'getInstance' ) );

add_action("widgets_init", function () { register_widget('StgHelpdesk\Widget\Stg_Widget_Form'); });