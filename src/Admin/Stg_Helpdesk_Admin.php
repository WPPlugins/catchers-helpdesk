<?php

namespace StgHelpdesk\Admin;

use StgHelpdesk\Core\PostType\Stg_Helpdesk_Post_Type_Statuses;
use StgHelpdesk\Core\Stg_Helpdesk_Activator;
use StgHelpdesk\Helpers\Stg_Helper_Email;
use StgHelpdesk\Helpers\Stg_Helper_Logger;
use StgHelpdesk\Helpers\Stg_Helper_Saved_Replies;
use StgHelpdesk\Helpers\Stg_Helper_Custom_Forms;
use StgHelpdesk\Helpers\Stg_Helper_UploadFiles;
use StgHelpdesk\Ticket\Stg_Helpdesk_Admin_Ticket_Layout;
use StgHelpdesk\Ticket\Stg_Helpdesk_Filter;
use StgHelpdesk\Ticket\Stg_Helpdesk_MetaBoxes;
use StgHelpdesk\Ticket\Stg_Helpdesk_Ticket;
use StgHelpdesk\Ticket\Stg_Helpdesk_Ticket_Query;
use StgHelpdesk\Ticket\Stg_Helpdesk_TicketList;

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Plugin_Name
 * @subpackage Plugin_Name/admin
 */
class Stg_Helpdesk_Admin
{

    public static $nonceName = 'stgh_nonce';

    public static $customNonceAction = 'stgh_custom_action_nonce';

    public static $nonceAction = 'stgh_update_nonce';

    /**
     * Initialize the class and set its properties.
     */
    public function __construct()
    {
        if (stgh_request_is_not_ajax()) {
            add_action('admin_menu', array($this, 'registerMenu'));
            $this->manageRedirect();
            $this->registerCustomAction();
            $this->registerSettingsPage();
            $this->registerTicketLayout();
            $this->registerScripts();
            $this->registerStyles();
            $this->registerSaveTicketFields();
            $this->registerPostDataFilter();
            $this->registerFlushCacheUsersLists();
            $this->getTicketsCount();
            $this->registerBulkEditCustomBox();
            
            // Adds "Support Forum" link to the plugin action page
            add_filter('plugin_action_links_' . plugin_basename(STG_HELPDESK_ROOT_PLUGIN_FILENAME_AND_PATH), array($this, 'add_action_links'));

            if ('list' == $this->getMode()) {
                $this->registerAdminTicketListActions();
            } else {
                $this->registerAdminTicketExcerptActions();
            }

            add_filter('views_edit-' . STG_HELPDESK_POST_TYPE, array($this, 'stgh_filter_link'));

            add_filter('stgh_ticket_comment_actions', 'stgh_ticket_comment_actions', 10, 3);
            add_filter('stgh_ticket_actions', 'stgh_ticket_actions', 10, 3);

            if ($this->justActivated()) {
                add_action('admin_init', array($this, 'redirectToWelcomePage'), 12, 0);
            }
        } else {
            add_action('wp_ajax_send_test_email', array($this, 'ajaxSendTestEmail'));
        }
        $this->registerTicketQuery();

        // Show messages
        $status = get_option('stgh_mail_test_connection', null);
        if (!is_null($status)) {
            add_action('admin_notices', array($this, 'mailboxNotice'));
        }
        $status = get_option('stgh_get_mails_from_support', null);
        if (!is_null($status)) {
            add_action('admin_notices', array($this, 'getMailsFromSupportNotice'));
        }
        $status = get_option('stgh_send_gethelp_message', null);
        if (!is_null($status)) {
            add_action('admin_notices', array($this, 'getGetHelpMessageNotice'));
        }
        // End show messages

        add_action('post_edit_form_tag', array($this, 'addPostEnctype'));

        // Ajax actions
        add_action('wp_ajax_stgh-autocomplete', array($this, 'registerAutocomplete'));
        add_action('wp_ajax_stgh_save_support_email_option', array($this, 'saveSupportEmail'));

                // Rating
        add_action('wp_ajax_stgh-rating-click', array($this, 'clickRating'));
        add_action('before_delete_post', array($this, 'deleteTicket'));
        add_filter('admin_footer_text', array($this, 'stgh_admin_footer_text'), 11);

        //add id label ticket
        add_filter('stgh_ticket_type_labels', function ($labels) {
            $labels['edit_item'] = !empty($_GET['post']) ? $labels['edit_item'] . ' #' . intval($_GET['post']) : $labels['edit_item'];
            return $labels;
        });

                //add_action("load-post-new.php", array($this, 'blockTicketCreate'));
            }

        function blockTicketCreate()
    {
        if($_GET["post_type"] == STG_HELPDESK_POST_TYPE)
            wp_redirect("edit.php?post_type=".STG_HELPDESK_POST_TYPE);
    }
    
    public function deleteTicket($ticketId){
        global $post_type;

        if ( $post_type != STG_HELPDESK_POST_TYPE )
            return;

        Stg_Helpdesk_Ticket::removeTicketRepliesAndAttachs($ticketId);
    }


    protected function registerFlushCacheUsersLists()
    {
        add_action('set_user_role', function ($user_id) {
            // flushCacheUsersLists
            if ($hashes = get_option('stgh_list_users_cache_hashes', false)) {
                $hashes = explode(' ', $hashes);
                if (!empty($hashes)) {
                    foreach ($hashes as $hash) {
                        delete_transient("stgh_list_users_$hash");
                    }
                    delete_option('stgh_list_users_cache_hashes');
                }
            }
        });
    }


    protected function registerBulkEditCustomBox()
    {
        add_filter('manage_' . STG_HELPDESK_POST_TYPE . '_posts_columns', function ($posts_columns) {
            return Stg_Helpdesk_MetaBoxes::addBulkEditCustomColumns($posts_columns);
        }, 11);

        add_action('bulk_edit_custom_box', function ($column_name, $post_type) {
            return Stg_Helpdesk_MetaBoxes::showBulkEditCustomColumns($column_name, $post_type);
        }, 10, 2);
    }

    
    /**
     * Save support email option from Wizard
     */
    public function saveSupportEmail()
    {
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        if ($email)
            $result = stgh_update_option('stg_mail_login', $email);
        else
            $result = stgh_set_option('stg_mail_login', $email);
        echo $result;
        exit;
    }

    /**
     * Save incoming mail settings from Wizard
     */
    public function saveIncomingMailSettings()
    {
        $res = stgh_update_option('stg_mail_login', isset($_POST['stgh_stg_mail_login']) ? $_POST['stgh_stg_mail_login'] : false);
        if (!$res)
            stgh_set_option('stg_mail_login', isset($_POST['stgh_stg_mail_login']) ? $_POST['stgh_stg_mail_login'] : false);

        $res = stgh_update_option('mail_pwd', isset($_POST['stgh_mail_pwd']) ? $_POST['stgh_mail_pwd'] : false);
        if (!$res)
            stgh_set_option('mail_pwd', isset($_POST['stgh_mail_pwd']) ? $_POST['stgh_mail_pwd'] : false);

        $res = stgh_update_option('mail_protocol', isset($_POST['stgh_mail_protocol']) ? $_POST['stgh_mail_protocol'] : false);
        if (!$res)
            stgh_set_option('mail_protocol', isset($_POST['stgh_mail_protocol']) ? $_POST['stgh_mail_protocol'] : false);

        $res = stgh_update_option('mail_protocol_visible', isset($_POST['stgh_mail_protocol_visible']) ? $_POST['stgh_mail_protocol_visible'] : false);
        if (!$res)
            stgh_set_option('mail_protocol_visible', isset($_POST['stgh_mail_protocol_visible']) ? $_POST['stgh_mail_protocol_visible'] : false);

        $res = stgh_update_option('mail_server', isset($_POST['stgh_mail_server']) ? $_POST['stgh_mail_server'] : false);
        if (!$res)
            stgh_set_option('mail_server', isset($_POST['stgh_mail_server']) ? $_POST['stgh_mail_server'] : false);

        $res = stgh_update_option('mail_port', isset($_POST['stgh_mail_port']) ? $_POST['stgh_mail_port'] : false);
        if (!$res)
            stgh_set_option('mail_port', isset($_POST['stgh_mail_port']) ? $_POST['stgh_mail_port'] : false);

        $res = stgh_update_option('mail_encryption', isset($_POST['stgh_mail_encryption']) ? $_POST['stgh_mail_encryption'] : 'SSL');
        if (!$res)
            stgh_set_option('mail_encryption', isset($_POST['stgh_mail_encryption']) ? $_POST['stgh_mail_encryption'] : 'SSL');
    }

    
    public function registerAutocomplete()
    {

        global $wpdb;

        $searchText = sanitize_text_field($_GET['term']);
        $sqlParts = array();
        foreach (explode(" ", $searchText) as $search) {
            $tmp = $wpdb->get_results($wpdb->prepare("SELECT DISTINCT u.ID, um.meta_key as meta_key, um.meta_value as meta_value
            FROM $wpdb->users as u LEFT JOIN $wpdb->usermeta as um ON (u.ID = um.user_id)
            WHERE (u.user_email like %s
            OR (um.meta_key = 'first_name' and um.meta_value like %s)
            OR (um.meta_key = 'last_name' and um.meta_value like %s)
            OR (um.meta_key = '_stgh_crm_company' and um.meta_value like %s))", '%' . $search . '%', '%' . $search . '%', '%' . $search . '%', '%' . $search . '%'));

            $usersMeta = $sqlParts[$search] = array();
            foreach ($tmp as $user) {
                $sqlParts[$search][$user->ID] = $user;
                $usersMeta[$user->ID] = get_user_meta($user->ID);
            }

        }


        $result = array_pop($sqlParts);
        foreach ($sqlParts as $sqlPart) {
            $result = array_intersect_key($result, $sqlPart);
        }

        if (count(array_keys($result)) > 0) {
            $args = array(
                'role' => 'stgh_client',
                'include' => array_keys($result));
            $users = get_users($args);
        } else
            $users = array();


        $result = array();
        foreach ($users as $user) {
            $result[] = array('label' => $user->display_name, 'id' => $user->ID, 'email' => $user->user_email, 'first_name' => $usersMeta[$user->ID]['first_name'], 'last_name' => $usersMeta[$user->ID]['last_name']);
        }

        $response = $_GET["callback"] . "(" . json_encode($result) . ")";
        echo $response;
        exit;
    }

    protected function getTicketsCount()
    {
        add_action('admin_menu', array($this, 'countTickets'));
    }

    /**
     * Add enctype to admin page ticket
     */
    function addPostEnctype()
    {
        echo ' enctype="multipart/form-data"';
    }

    public function countTickets()
    {
        global $menu;

        $count = stgh_menu_count_tickets();

        if (0 === $count) {
            return false;
        }

        foreach ($menu as $key => $value) {
            if ($menu[$key][2] == 'edit.php?post_type=' . STG_HELPDESK_POST_TYPE) {
                $menu[$key][0] .= ' <span class="awaiting-mod count-' . $count . '"><span class="pending-count">' . $count . '</span></span>';
            }
        }

        return true;

    }

    /**
     * Registering hooks to display the posts list
     */
    protected function registerTicketQuery()
    {
        add_action('pre_get_posts', function ($query) {
            Stg_Helpdesk_Ticket_Query::limitAny($query);
        }, 10, 1);

        add_action('pre_get_posts', function ($query) {
            Stg_Helpdesk_Ticket_Query::ordering($query);
        });

        if (isset($_GET['assignedTo'])) {
            add_action('pre_get_posts', function ($query) {
                Stg_Helpdesk_Ticket_Query::assignedTo($query);
            });
        }
        
        if (isset($_GET['authorId'])) {
            add_action('pre_get_posts', function ($query) {
                Stg_Helpdesk_Ticket_Query::authorId($query);
            });
        }
    }

    /**
     * Adding custom options to a table
     */
    protected function registerAdminTicketListActions()
    {
        add_filter('post_row_actions', function ($actions, $post) {
            return Stg_Helpdesk_TicketList::actionRows($actions, $post);
        }, 10, 2);
        add_filter('manage_' . STG_HELPDESK_POST_TYPE . '_posts_columns', function ($columns) {
            return Stg_Helpdesk_TicketList::tableColumns($columns);
        }, 10, 2);
        add_filter('manage_' . STG_HELPDESK_POST_TYPE . '_posts_custom_column', function ($columnName, $postId) {
            Stg_Helpdesk_TicketList::columnsContent($columnName, $postId);
        }, 10, 2);

        add_filter('manage_edit-' . STG_HELPDESK_POST_TYPE . '_sortable_columns', function ($columns) {
            return Stg_Helpdesk_TicketList::columnsOrdering($columns);
        });
        add_action('restrict_manage_posts', function () {
            Stg_Helpdesk_TicketList::assignToFilter();
        }, 9, 0);
        add_filter('parse_query', function ($query) {
            return Stg_Helpdesk_Ticket_Query::filter($query);
        });
        add_action('restrict_manage_posts', function () {
            Stg_Helpdesk_TicketList::crmCompanyFilter();
        }, 10, 0);

        
        add_filter('list_table_primary_column', function ($default, $screenId) {
            return Stg_Helpdesk_TicketList::getPrimaryKey($default, $screenId);
        }, 10, 2);
    }

    /**
     * Adding custom options to a table, Excerpt mode
     */
    protected function registerAdminTicketExcerptActions()
    {
        add_filter('post_row_actions', function ($actions, $post) {
            return Stg_Helpdesk_TicketList::actionRows($actions, $post);
        }, 10, 2);

        add_filter('manage_' . STG_HELPDESK_POST_TYPE . '_posts_columns', function ($columns) {
            return Stg_Helpdesk_TicketList::tableColumnsExcerpt($columns);
        }, 10, 2);

        add_filter('manage_' . STG_HELPDESK_POST_TYPE . '_posts_custom_column', function ($columnName, $postId) {
            Stg_Helpdesk_TicketList::columnsContentExcerpt($columnName, $postId);
        }, 10, 2);

        add_filter('manage_edit-' . STG_HELPDESK_POST_TYPE . '_sortable_columns', function ($columns) {
            return Stg_Helpdesk_TicketList::columnsOrdering($columns);
        });
        add_action('restrict_manage_posts', function () {
            Stg_Helpdesk_TicketList::assignToFilter();
        }, 9, 0);
        add_filter('parse_query', function ($query) {
            return Stg_Helpdesk_Ticket_Query::filter($query);
        });
        add_action('restrict_manage_posts', function () {
            Stg_Helpdesk_TicketList::crmCompanyFilter();
        }, 10, 0);

            }

    /**
     *    Filtering the data before inserting it into the database
     */
    protected function registerPostDataFilter()
    {
        add_filter('wp_insert_post_data', function ($data, $postarr) {
            return Stg_Helpdesk_Filter::filterData($data, $postarr);
        }, 99, 2);
    }

    /**
     * Redirect
     */
    protected function manageRedirect()
    {
        if (isset($_SESSION['stgh_redirect'])) {
            $redirect = esc_url($_SESSION['stgh_redirect']);
            unset($_SESSION['stgh_redirect']);
            wp_redirect($redirect);
            exit;
        }
    }

    protected function registerSaveTicketFields()
    {
        $version_object = stgh_get_current_version_object();
        $version_object->registerSaveTicketFields();

        // add_action('save_post_'.STG_HELPDESK_POST_TYPE, 'stgh_save_custom_fields');
    }


    /**
     * Register hook for styles
     */
    protected function registerStyles()
    {
        add_action('admin_enqueue_scripts', array($this, 'enqueueStyles'));
        add_action('admin_head', array($this, 'customEnqueueStyles'));
    }

    /**
     * Register styles
     */
    public function enqueueStyles()
    {
        global $current_screen;

        if (stgh_is_plugin_page()) {
            wp_enqueue_style('stgh-select2', STG_HELPDESK_URL . 'css/vendor/select2/select2.min.css', null, '4.0.0', 'all');
            wp_enqueue_style('stgh-admin-styles', STG_HELPDESK_URL . 'css/admin/admin.css', array('stgh-select2'),
                STG_HELPDESK_VERSION);
//            wp_enqueue_style('stgh-plugin-styles', STG_HELPDESK_URL . 'css/stg-helpdesk-public.css', array(),
//                STG_HELPDESK_VERSION);

            if($current_screen->id == 'stgh_ticket'){
                wp_enqueue_style('stgh-admin-ticket', STG_HELPDESK_URL . 'css/admin/ticket.css', array(),
                    STG_HELPDESK_VERSION);
            }
        }
    }

    /**
     * Register custom styles for pages
     */
    public function customEnqueueStyles()
    {
        $version_object = stgh_get_current_version_object();
        $version_object->customEnqueueStyles();
    }

    /**
     * Register hook fro scripts
     */
    protected function registerScripts()
    {
        add_action('admin_enqueue_scripts', array($this, 'enqueueScripts'));
        add_action( 'wp_print_scripts', array($this, 'removeSBScripts'), 100 );

    }


    /**
     * Remove SB scripts
     */
    public function removeSBScripts()
    {
        if (stgh_is_plugin_page()) {
            $scriptNames = array('select2','woocomposer-admin-script');

            foreach($scriptNames as $scriptName)
            {
                if (wp_script_is( $scriptName, 'enqueued' ) or wp_script_is( $scriptName, 'queue' )) {

                    wp_deregister_script(  $scriptName );
                    wp_dequeue_script(  $scriptName );
                }
            }

        }
    }


    /**
     * Register scripts
     */
    public function enqueueScripts()
    {
        global $current_screen;

        if (stgh_is_plugin_page()) {

            if ( wp_script_is( 'select2', 'registered' ) ) {
                wp_deregister_script( 'select2' );
            }

            wp_enqueue_script('stgh-select2', STG_HELPDESK_URL . 'js/vendor/select2.min.js', array('jquery'), '4.0.0',true);
            wp_enqueue_script('jquery-ui-autocomplete');
            wp_enqueue_script('stgh-admin-script', STG_HELPDESK_URL . 'js/admin/admin.js',
                array('jquery', 'stgh-select2', 'jquery-ui-autocomplete'),
                STG_HELPDESK_VERSION);

            $version_object = stgh_get_current_version_object();
            $version_object->enqueueAdminScripts();

            wp_enqueue_script('stgh-admin-wiz-script', STG_HELPDESK_URL . 'js/admin/wizard.js',
                array('jquery', 'stgh-select2'),
                STG_HELPDESK_VERSION);

            
            wp_dequeue_script('autosave');
            $action = filter_input(INPUT_GET, 'action', FILTER_SANITIZE_STRING);

            if (stgh_is_our_post_type()) {
                wp_enqueue_script('stgh-admin-comment', STG_HELPDESK_URL . 'js/admin/ticket-comments.js', array('jquery'),
                    STG_HELPDESK_VERSION);
                wp_localize_script('stgh-admin-comment', 'stghLocale', array(
                    'alertDelete' => __('Are you sure you want to delete this comment?', STG_HELPDESK_TEXT_DOMAIN_NAME),
                    'alertSpam' => __('This ticket spam? Comments and contact will be deleted.', STG_HELPDESK_TEXT_DOMAIN_NAME),
                    'alertNoTinyMCE' => __('No instance of TinyMCE found. Please use wp_editor on this page at least once: http://codex.wordpress.org/Function_Reference/wp_editor',
                        STG_HELPDESK_TEXT_DOMAIN_NAME),
                    'alertNoContent' => __("You can't submit an empty comment", STG_HELPDESK_TEXT_DOMAIN_NAME),
                    'alertEmptyEdit' => __("You can't save an empty comment", STG_HELPDESK_TEXT_DOMAIN_NAME),
                    'alertBademail' => __("Wrong email", STG_HELPDESK_TEXT_DOMAIN_NAME),
                    'alertEmptyName' => __("You can't add an empty field name", STG_HELPDESK_TEXT_DOMAIN_NAME),
                    'contactLabel' => __("Contact", STG_HELPDESK_TEXT_DOMAIN_NAME),
                    'selectContactLabel' => __("Select contact", STG_HELPDESK_TEXT_DOMAIN_NAME),
                    'addContactLabel' => __("Add new contact", STG_HELPDESK_TEXT_DOMAIN_NAME),
                    'contactEmpty' => __("Please select contact", STG_HELPDESK_TEXT_DOMAIN_NAME),
                    'replyToEmpty' => __("Cc and Bcc options available only if \"Reply To\" address specified", STG_HELPDESK_TEXT_DOMAIN_NAME),
                ));
            }

            if ($current_screen->id == "stgh_ticket") {
                add_action('admin_print_scripts', array($this, 'addStatusBenjiInlineScript'));
            }
        }
    }

    /**
     * Add Benji
     */
    public function addStatusBenjiInlineScript()
    {
        global $post;

        if (isset($post->ID)) {
            $statuses = Stg_Helpdesk_Post_Type_Statuses::get();
            if(isset($statuses[$post->post_status]))
                $label = $statuses[$post->post_status];
            else
                $label = false;
            $class = $post->post_status . '_color';
            $version_object = stgh_get_current_version_object();

            $selector = $version_object->getTitleSelector();
            ?>
            <script type="text/javascript">
                document.addEventListener('DOMContentLoaded', function () {
                    document.querySelector(<?php echo $selector; ?>).innerHTML += '<span class="stgh_status_header <?php echo $class; ?>"><?php echo $label; ?></span>';
                }, false);
            </script>
            <?php
        }
    }

    protected function registerCustomAction()
    {
        if (isset($_GET['stgh-do'])) {
            add_action('init', array($this, 'doCustomAction'));
        }
        if (isset($_POST['stgh-do'])) {
            add_action('init', array($this, 'doCustomPostAction'));
        }

        if((isset($_REQUEST["post_type"]) && $_REQUEST["post_type"] =="stgh_ticket") &&
            (isset($_REQUEST["page"]) && $_REQUEST["page"]=="settings") &&
            (isset($_REQUEST["tab"]) && $_REQUEST["tab"]=="email") &&
            (isset($_REQUEST["action"]) && $_REQUEST["action"]=="save")){

            stgh_set_option('enable_reply_client', false);
            stgh_set_option('enable_ticket_assign', false);

            if(isset($_REQUEST['stgh_agent_notifications']))
            {
                foreach ($_REQUEST['stgh_agent_notifications'] as $optionsName) {
                    stgh_set_option($optionsName, true);
                }
            }

        }

        //cron action tasks
        if (!empty($_POST['stgh_stg_mail_interval'])) {
            Stg_Helpdesk_Activator::setCronEmailTasks(sanitize_text_field($_POST['stgh_stg_mail_interval']));
        }
    }

    /**
     * Get current mode
     * @return string
     */
    public function getMode()
    {
        global $mode;

        if (!empty($_REQUEST['mode'])) {
            $mode = $_REQUEST['mode'] == 'excerpt' ? 'excerpt' : 'list';
            set_user_setting('posts_list_mode', $mode);
        } else {
            $mode = get_user_setting('posts_list_mode', 'list');
        }

        return $mode;
    }

    public function doCustomAction()
    {

        if (isset($_GET['stgh-do']) &&  $_GET['stgh-do'] == 'create-user-pages') {
            Stg_Helpdesk_Activator::insertUserPages();
            $siteUrl = get_site_url();
            stgh_redirect('stgh_pages_created', $siteUrl.'/wp-admin/edit.php?post_type=page');
            exit;
        }

        if (isset($_GET['stgh-do']) &&  $_GET['stgh-do'] == 'remove-trash-reply') {
            Stg_Helpdesk_Ticket::removeTrashReply();
            $siteUrl = get_site_url();
            stgh_redirect('stgh_pages_created', $siteUrl.'/wp-admin/');
            exit;
        }


        if (isset($_GET['tconnection'])) {
            if (isset($_GET['stgh_save_first'])) {
                $this->saveIncomingMailSettings();
            }
            $check_connection = stgh_mailbox_check_connection();
            add_option('stgh_mail_test_connection', $check_connection);
            if (isset($_GET['stgh_back'])) {
                wp_redirect($_SERVER['HTTP_REFERER']);
            } else {
                wp_redirect(get_mailserver_link());
            }
            exit;
        }
        if (isset($_GET['takeemails'])) {
            $result = stgh_letters_handler();
            if (isset($result['msg'])) {
                add_option('stgh_get_mails_from_support_message', $result['msg']);
            }
            if (isset($result['status'])) {
                add_option('stgh_get_mails_from_support', $result['status']);
            }
            if (isset($result['amount'])) {
                add_option('stgh_amount_mails_from_support', $result['amount']);
            }
            wp_redirect(get_mailserver_link());
            exit;
        }

        if (!isset($_GET[self::$nonceName]) || !wp_verify_nonce($_GET[self::$nonceName], self::$customNonceAction)) {
            return;
        }

        $action = sanitize_text_field($_GET['stgh-do']);

        switch ($action) {
            case 'open':
                $id = intval($_GET['post']);
                Stg_Helpdesk_Ticket::open($id);
                                $url = wp_sanitize_redirect(add_query_arg(array('post' => $_GET['post'], 'action' => 'edit'),
                    admin_url('post.php')));

                stgh_redirect('open_ticket', $url);
                exit;
                break;

            case 'spam_ticket':
                //case 'spam_comment':
                require_once(ABSPATH . 'wp-admin/includes/user.php');

                if (isset($_GET['del_id'])) {

                    $delId = intval($_GET['del_id']);
                    $post = get_post($delId);

                    if ($post && isset($post->post_author)) {
                        $user = get_userdata($post->post_author);
                        if ($user && !in_array('administrator', $user->roles) && count($user->roles) <= 1) {
                            wp_delete_user($post->post_author);
                        }

                        // Is ticket
                        if ($post->post_type == STG_HELPDESK_POST_TYPE) {
                            // For redirect
                            $post_type = STG_HELPDESK_POST_TYPE;
                        }
                    }
                }

            case 'trash_comment':
                if (isset($_GET['del_id']) && stgh_current_user_can('delete_reply')) {
                    Stg_Helpdesk_Ticket::removeTicketReply($_GET['del_id']);

                    /* Redirect with clean URL */
                    if (!isset($post_type)) {
                        $url = wp_sanitize_redirect(add_query_arg(array('post' => $_GET['post'], 'action' => 'edit'),
                            admin_url('post.php') . "#stgh-post-$delId"));
                    } else {
                        $url = wp_sanitize_redirect(add_query_arg(array('post_type' => STG_HELPDESK_POST_TYPE), admin_url('edit.php')));
                    }

                    stgh_redirect('trashed_comment', $url);
                    exit;
                }
                break;
        }

        do_action('stgh_do_custom_action', $action);

        $args = $_GET;

        /* Remove custom action and nonce */
        unset($_GET['stgh-do']);
        unset($_GET['stgh-nonce']);

        exit;
    }

    public function doCustomPostAction()
    {
        $action = sanitize_text_field($_POST['stgh-do']);
        switch ($action) {
            case 'send_gethelp_message':
                $sender['from_name'] = $sender['reply_name'] = isset($_POST['stgh_gethelp_form_name']) ? $_POST['stgh_gethelp_form_name'] : '';
                $sender['from_email'] = $sender['reply_email'] = isset($_POST['stgh_gethelp_form_email']) ? $_POST['stgh_gethelp_form_email'] : '';
                $headers = Stg_Helper_Email::getHeaders($sender);

                $result = Stg_Helper_Email::send(array(
                    'to' => 'support@mycatchers.com',
                    'subject' => isset($_POST['stgh_gethelp_form_subject']) ? $_POST['stgh_gethelp_form_subject'] : __('Get help!', STG_HELPDESK_TEXT_DOMAIN_NAME),
                    'body' => isset($_POST['stgh_gethelp_form_msg']) ? $_POST['stgh_gethelp_form_msg'] : '',
                    'headers' => $headers,
                    'attachments' => ''
                ));
                add_option('stgh_send_gethelp_message', $result['status']);
                wp_redirect($_SERVER['HTTP_REFERER']);
                exit;
        }
    }

    /**
     * Register options for tickets
     */
    protected function registerTicketLayout()
    {
        add_action('add_meta_boxes', function () {
            global $post_type;

            if ($post_type == STG_HELPDESK_POST_TYPE or $post_type == STG_HELPDESK_COMMENTS_POST_TYPE)
                Stg_Helpdesk_Admin_Ticket_Layout::instance();
        });
    }

    /**
     * Loading settings page
     */
    protected function registerSettingsPage()
    {
        require_once(STG_HELPDESK_PATH . 'Admin/settings/settings-core.php');
        require_once(STG_HELPDESK_PATH . 'Admin/settings/settings-email.php');
        require_once(STG_HELPDESK_PATH . 'Admin/settings/settings-style.php');
        // require_once(STG_HELPDESK_PATH.'Admin/settings/settings-fileupload.php');
        require_once(STG_HELPDESK_PATH . 'Admin/settings/settings-mailserver.php');
        require_once(STG_HELPDESK_PATH . 'Admin/settings/settings-outgoing.php');
                require_once(STG_HELPDESK_PATH . 'Admin/settings/integrations.php');
        require_once(STG_HELPDESK_PATH . 'Admin/settings/settings-gethelp.php');
                require_once(STG_HELPDESK_PATH . 'Admin/settings/settings-pro-version.php');
        
        add_action('plugins_loaded', function () {
            Stg_Helpdesk_Settings_Page::instance();
        }, 11, 0);
    }

    /**
     * Check activation status
     *
     * @return bool
     */
    protected function justActivated()
    {
        return true === (bool)get_option('stgh_redirect_to_welcome_page', false);
    }

    /**
     *
     */
    public function redirectToWelcomePage()
    {
        delete_option('stgh_redirect_to_welcome_page');
        wp_redirect(stgh_link_to_welcome_page());
        exit;
    }

    /**
     *    Register menu items
     */
    public function registerMenu()
    {
        global $menu, $submenu;

        $current_user = stgh_get_current_user();

        add_submenu_page('edit.php?post_type=' . STG_HELPDESK_POST_TYPE,
            __('StudioTG Helpdesk Welcome page', STG_HELPDESK_TEXT_DOMAIN_NAME), __('Welcome', STG_HELPDESK_TEXT_DOMAIN_NAME), 'view_ticket',
            'stgh-welcome', array($this, 'showWelcomePage'));
        remove_submenu_page('edit.php?post_type=' . STG_HELPDESK_POST_TYPE, 'stgh-welcome');


        foreach ($submenu['edit.php?post_type=' . STG_HELPDESK_POST_TYPE] as $key => $current) {
            if ($current[2] == "edit-tags.php?taxonomy=post_tag&amp;post_type=" . STG_HELPDESK_POST_TYPE) {
                unset($submenu['edit.php?post_type=' . STG_HELPDESK_POST_TYPE][$key]);
            }
        }


        // Wizard
        add_submenu_page('edit.php?post_type=' . STG_HELPDESK_POST_TYPE,
            __('StudioTG Helpdesk Wizard page', STG_HELPDESK_TEXT_DOMAIN_NAME), __('Wizard', STG_HELPDESK_TEXT_DOMAIN_NAME), 'view_ticket',
            'stgh-wizard', array($this, 'showWizardPage'));
        remove_submenu_page('edit.php?post_type=' . STG_HELPDESK_POST_TYPE, 'stgh-wizard');


                // Pro-version page
        add_submenu_page('edit.php?post_type=' . STG_HELPDESK_POST_TYPE, null, "<span style=\"margin-top:-12px;color:#168de2;\">" . __('Pro-version', STG_HELPDESK_TEXT_DOMAIN_NAME) . "</span>", 'edit_posts', 'stgh-pro', null);

        add_action('admin_menu', array($this, 'add_external_link_admin_submenu'), 100);
        add_action('admin_menu', array($this, 'remove_add_new_ticket_submenu'), 100);
        
        add_submenu_page('edit.php?post_type=' . STG_HELPDESK_POST_TYPE, null, __('Addons', STG_HELPDESK_TEXT_DOMAIN_NAME), 'edit_posts', 'stgh-addons', array($this, 'showAddonPage'));

        foreach ($menu as $key => $value) {
            if ($menu[$key][2] == 'edit.php?post_type=' . STG_HELPDESK_POST_TYPE) {
                $oldSubMenu = $submenu['edit.php?post_type=' . STG_HELPDESK_POST_TYPE];
                $cnt = count($oldSubMenu);
                $insertPosition = 1;
                $newSubMenu = array();
                for ($i = 0; $i < $cnt; $i++) {
                    if ($i == $insertPosition) {
                        $newSubMenu[] = array(
                            __('My tickets', STG_HELPDESK_TEXT_DOMAIN_NAME),
                            'view_ticket',
                            'edit.php?assignedTo=' . $current_user->ID . '&post_type=' . STG_HELPDESK_POST_TYPE
                        );
                    }

                    array_push($newSubMenu, array_shift($oldSubMenu));
                }
                $submenu['edit.php?post_type=' . STG_HELPDESK_POST_TYPE] = $newSubMenu;
            }
        }

    }

    public function add_external_link_admin_submenu() {
        global $submenu;

        //$permalink = 'https://mycatchers.com/pro/?source=light\' target=\'_blank;';
        $permalink = 'edit.php?post_type=stgh_ticket&page=settings&tab=proversion';

        foreach($submenu["edit.php?post_type=stgh_ticket"] as &$current)
        {
            if($current[2] == 'stgh-pro')
            {
                $current[2] = $permalink;
            }
        }
    }

    public function remove_add_new_ticket_submenu() {
        global $submenu;

        foreach($submenu["edit.php?post_type=stgh_ticket"] as $key => $current)
        {
            if($current[2] == 'post-new.php?post_type=stgh_ticket')
            {
                unset($submenu["edit.php?post_type=stgh_ticket"][$key]);
            }
        }
    }



    /**
     * Show welcome page
     */
    public function showWelcomePage()
    {
        include_once(STG_HELPDESK_PATH . 'Admin/views/welcome.php');
    }

    /**
     * Wizard page
     */
    public function showWizardPage()
    {
        include_once(STG_HELPDESK_PATH . 'Admin/views/wizard.php');
    }

    public function showAddonPage()
    {
        include_once(STG_HELPDESK_PATH . 'Admin/views/addons.php');
    }

    public function mailboxNotice()
    {
        $status = get_option('stgh_mail_test_connection', true);
        $server = stgh_get_option('mail_server', false);
        /*$enabled = stgh_get_option('stg_enabled_mail_feedback', false);
        if (! $enabled) {
            $msg = __('You are not activate feedback via email.', STG_HELPDESK_TEXT_DOMAIN_NAME);
            $p = '';
            $class = 'error';
        } else*/
        if (1 == $status) {
            $msg = __(sprintf('Connection to the server: %s established successfully.', $server), STG_HELPDESK_TEXT_DOMAIN_NAME);
            $p = '';
            $class = 'updated';
        } else {
            $msg = __(sprintf('Can not connect to the server: %s. Please, check your setting.', $server),
                STG_HELPDESK_TEXT_DOMAIN_NAME);
            $p = $status;
            $class = 'error';

            if(stgh_get_option('mail_protocol_visible') == 'Gmail'){
                $gmailHelpMsg = __('See also: <a href="https://support.google.com/accounts/answer/6009563" target="_blank">https://support.google.com/accounts/answer/6009563</a>', STG_HELPDESK_TEXT_DOMAIN_NAME);
                $p = "<p>{$p}</p><p>{$gmailHelpMsg}</p>";

            }
        }

        echo "<div id='stgh-mailbox-info-warning' class='{$class} fade'><p><strong>{$msg}</strong></p>" . $p . "</div>";

        delete_option('stgh_mail_test_connection');
    }

    public function getMailsFromSupportNotice()
    {
        $status = get_option('stgh_get_mails_from_support', false);
        $amount = get_option('stgh_amount_mails_from_support', false);
        if ($status) {
            if ($amount > 0) {
                $msg = __(sprintf('Processed %d e-mail(s)', $amount), STG_HELPDESK_TEXT_DOMAIN_NAME);
            } else {
                $msg = __('No new messages for processing', STG_HELPDESK_TEXT_DOMAIN_NAME);
            }

            $class = 'updated';
        } else {
            $message = get_option('stgh_get_mails_from_support_message', '');
            $msg = __('Can not connect to the server. Please, check your setting.', STG_HELPDESK_TEXT_DOMAIN_NAME) . $message;
            $class = 'error';
        }
        echo "<div class='$class fade'><p><strong>$msg</strong></p></div>";

        delete_option('stgh_get_mails_from_support');
        delete_option('stgh_get_mails_from_support_message');
        delete_option('stgh_amount_mails_from_support');
    }

    public function getGetHelpMessageNotice()
    {
        $status = get_option('stgh_send_gethelp_message', false);
        if ($status) {
            $msg = __('Thank you for contacting us. Your message has been successfully sent. We will contact you very soon!', STG_HELPDESK_TEXT_DOMAIN_NAME);
            $class = 'updated';
        } else {
            $msg = __('An error occurred when send message.', STG_HELPDESK_TEXT_DOMAIN_NAME);
            $class = 'error';
        }
        echo "<div class='$class fade'><p><strong>$msg</strong></p></div>";
        delete_option('stgh_send_gethelp_message');
    }

    public function ajaxSendTestEmail()
    {

        $headers = Stg_Helper_Email::getHeaders();

        $result = Stg_Helper_Email::send(array(
            'to' => stgh_get_option('stgh_test_to_email'),
            'subject' => __('Check connection from Tickets', STG_HELPDESK_TEXT_DOMAIN_NAME) . ' (' . home_url() . ')',
            'body' => __('Connection successful!', STG_HELPDESK_TEXT_DOMAIN_NAME),
            'headers' => $headers,
            'attachments' => ''
        ));
        if ($result['status'])
            wp_send_json_success();
        else
            wp_send_json_error();
    }

    public function add_action_links($links)
    {
        $stgh_links = array(
            '<a target="blank" href="' . stgh_link_to_settings('gethelp') . '">Support Forum</a>',
        );
        return array_merge($links, $stgh_links);
    }

    public function stgh_filter_link($views)
    {
        $statuses = stgh_get_statuses();
        foreach ($statuses as $key => $status) {
            if ($key == 'stgh_closed') {
                unset($statuses[$key]);
            }
        }

        $current_user = stgh_get_current_user();
        $params = array(
            'post_status' => array_keys($statuses),
            'meta_key' => '_stgh_assignee',
            'meta_value' => $current_user->ID
        );
        $count = count(Stg_Helpdesk_Ticket::get($params));
        $class = (isset($_GET['tp']) && $_GET['tp'] == 'my') ? ' class="current" ' : '';
        $views['mine'] = sprintf(__('<a href="%s" ' . $class . ' >My <span class="count">(%d)</span></a>', 'publish featured'),
            admin_url('edit.php?post_type=stgh_ticket&assignedTo=' . $current_user->ID . '&tp=my'),
            $count);


        $params = array('post_status' => array_keys($statuses));
        $count = count(Stg_Helpdesk_Ticket::get($params));
        $class = (strpos($views['all'], 'current') === false) ? '' : ' class="current" ';
        $views['all'] = sprintf(__('<a href="%s" ' . $class . ' >All <span class="count">(%d)</span></a>', 'publish featured'),
            admin_url('edit.php?post_type=stgh_ticket&all_posts=1'),
            $count);

        return $views;
    }

    /**
     * Handler rating-click event
     */
    public function clickRating()
    {
        stgh_set_option('stgh_admin_footer_text_rated', 1);
    }

    /**
     * @param $footer_text
     * @return string|void
     */
    public function stgh_admin_footer_text($footer_text)
    {
        global $post_type, $current_screen, $TitanFrameworkAdminPage;

        if ($post_type == STG_HELPDESK_POST_TYPE || $current_screen->id == 'stgh_ticket_page_settings' || $current_screen->id == 'stgh_ticket_page_stgh-addons') {
            echo "<style>#footer-left em {display:none;}</style>";
            if (!stgh_get_option('stgh_admin_footer_text_rated')) {
                $footer_text = sprintf(__('If you like <b>Catchers Helpdesk</b> please leave us a <a id="stg-rating-anchor" data-msg-click="%s" target="_blank" href="%s">&#9733;&#9733;&#9733;&#9733;&#9733;</a> rating. A huge thank you from Catchers in advance!', STG_HELPDESK_TEXT_DOMAIN_NAME), __('Thanks!', STG_HELPDESK_TEXT_DOMAIN_NAME), 'https://wordpress.org/support/view/plugin-reviews/catchers-helpdesk?filter=5#postform');
            } else {
                $footer_text = __('Thank you for using our plugin!', STG_HELPDESK_TEXT_DOMAIN_NAME);
            }
            return $footer_text;
        }
        return $footer_text;
    }

    }
