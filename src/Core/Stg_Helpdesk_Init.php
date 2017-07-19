<?php

namespace StgHelpdesk\Core;

use StgHelpdesk\Helpers\Stg_Helper_Custom_Forms;
use StgHelpdesk\Ticket\Stg_Helpdesk_Ticket;
use StgHelpdesk\Ticket\Stg_Helpdesk_TicketComments;
use StgHelpdesk\Helpers\Stg_Helper_UploadFiles;
use StgHelpdesk\Helpers\Stg_Helper_Template;
use StgHelpdesk\Helpers\Stg_Helper_Email;
use StgHelpdesk\Admin\Stg_Helpdesk_Help_Catcher;
/**
 * Class Stg_Helpdesk_Init
 * @package StgHelpdesk\Core
 */
class Stg_Helpdesk_Init
{
    /**
     * Instance of this class.
     */
    protected static $instance = null;

    public static $isCron = false;

    /**
     * Initialize the plugin
     */
    private function __construct()
    {

        //cron tasks
        add_action('stgh_load_email_hook', array($this, 'cronTask'));
        add_filter('cron_schedules', 'stgh_cron_add_schedules');

        //form submitted
        add_action('init', array($this, 'init'), 11, 0);

        // Load public-facing style sheets
        add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'), 10, 0);
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'), 10, 0);
        //add_action('wp_enqueue_scripts', array($this, 'init_inline_style'), 9, 0);

        //content ticket
        add_filter('the_content', 'get_page_single_ticket', 10, 1);

        //query type attachment file
        add_action('template_redirect', array($this, 'page_attachment'), 10, 0);
        add_action('pre_get_posts', array($this, 'attachment_query'), 10, 1);
        add_action('init', array($this, 'attachment_endpoint'), 10, 1);

        //user profile
        add_action('show_user_profile', array($this, 'add_custom_user_profile_fields'), 10, 1);
        add_action('edit_user_profile', array($this, 'add_custom_user_profile_fields'), 10, 1);
        add_action('personal_options_update', array($this, 'save_custom_user_profile_fields'));
        add_action('edit_user_profile_update', array($this, 'save_custom_user_profile_fields'));

        add_action('init', array($this, 'run_updater'));

        
        add_action('admin_bar_menu', array($this, 'registerMenuToolbar'), 49);
        add_action( 'wp_before_admin_bar_render', array($this,'removeNodes') );

        $this->registerCustomAction();

        add_filter( 'posts_search' , array($this,'searchByEmail'),1,2);
        add_filter( 'posts_join' , array($this,'joinByEmail'),1,2);
        add_filter('posts_distinct', array($this,'searchByEmailDistinct'));
    }

    public function searchByEmail($search,$query){
        global $wpdb;

        if($query->is_search && $query->is_admin && $query->query['post_type'] == STG_HELPDESK_POST_TYPE)
        {
            $add = $wpdb->prepare("({$wpdb->users}.user_email like '%%%s%%' AND pm.meta_key = '_stgh_contact' AND pm.meta_value = {$wpdb->users}.ID)",$query->get('s'));

            $pat = '|\(\((.+)\)\)|';
            $search = preg_replace($pat,'(($1 OR '.$add.'))',$search);
        }

        return $search;
    }

    public function joinByEmail($joins,$query){
        global $wpdb;
        if($query->is_search && $query->is_admin && $query->query['post_type'] == STG_HELPDESK_POST_TYPE)
        {
            $joins = $joins . " INNER JOIN {$wpdb->postmeta} as pm ON ({$wpdb->posts}.ID = pm.post_id)";
            $joins = $joins . " INNER JOIN {$wpdb->users} ON (pm.meta_value = {$wpdb->users}.ID)";
        }
        return $joins;
    }

    public function searchByEmailDistinct($query){
        global $wp_query;
        if($wp_query->is_search && $wp_query->is_admin && $wp_query->query['post_type'] == STG_HELPDESK_POST_TYPE)
        {
            return "DISTINCT";
        }
    }


    protected function registerCustomAction()
    {
        if (isset($_GET['stgh-do'])) {
            add_action('init', array($this, 'doCustomAction'));
        }
    }

    public function doCustomAction()
    {
        $action = sanitize_text_field($_GET['stgh-do']);

        switch ($action) {
            case 'tracking_image':
                $postId = $_REQUEST['postid'];

                if (!is_numeric($postId))
                    return false;

                $postReadHash = get_post_meta( $postId, '_stgh_post_read_hash', true );

                if(!isset($_REQUEST['sh']) || $_REQUEST['sh'] != $postReadHash)
                    return false;

                update_post_meta($postId, '_stgh_post_read', true);

                header("Content-type: image/png");
                $im = ImageCreate(1, 1);
                ImageColorAllocate($im, 255, 255, 255);
                ImagePng($im);
                break;
        }
        //exit;
    }


    public function cronTask()
    {
        Stg_Helpdesk_Init::$isCron = true;
        $result = stgh_letters_handler();
        Stg_Helpdesk_Init::$isCron = false;

        return $result;
    }

    /**
     *  Init handler form
     */
    public function init()
    {
        //new user
        $currentUser = stgh_get_current_user_id();
        $user_reg = false;

        if (!$currentUser || (!empty($_POST['stg_ticket_email']) && !get_user_by('email', trim($_POST['stg_ticket_email'])))) {
            if (isset($_POST['stg_ticket_name'])) {
                $user_reg = stgh_register_user($_POST['stg_ticket_email'], $_POST['stg_ticket_name']);

                if (!$user_reg) {
                    return false;
                }
            }
        }

        //ticket
        if (isset($_POST['stg_saveTicket']) && $_POST['stg_saveTicket'] == 1) {
            $post_id = Stg_Helpdesk_Ticket::saveTicket($_POST, $user_reg);

            if ($post_id) {
                Stg_Helper_UploadFiles::handleUploadsForm($post_id);
                add_post_meta($post_id, '_stgh_type_source', 'website', true);

                //email
                Stg_Helper_Email::sendEventTicket('ticket_open', $post_id);
                Stg_Helper_Email::sendEventTicket('ticket_assign', $post_id);

                $link = get_permalink($post_id);
                wp_redirect($link);
                exit;
            }
        }
        //comment
        if (isset($_POST['stgh_user_comment'])) {
            $comment_id = Stg_Helpdesk_TicketComments::saveCommentPublic($_POST);

            if ($comment_id) {
                $parent_id = !empty($_POST['ticket_id']) ? (int)$_POST['ticket_id'] : 0;
                Stg_Helper_UploadFiles::handleUploadsForm($comment_id, $parent_id);
                add_post_meta($comment_id, '_stgh_type_source', 'website', true);

                //email                
                $agentTicket = get_post_meta( $parent_id, '_stgh_ticket_author_agent', true);

                if($agentTicket == 1){
                    $authorComment = intval(get_post($comment_id)->post_author);
                    $ticket = Stg_Helpdesk_Ticket::getInstance($parent_id);
                    $ticketContact = $ticket->getContact();

                    if ($authorComment == $ticketContact->ID) {
                        Stg_Helper_Email::sendEventTicket('client_reply', $comment_id);
                    }
                }else{
                    $authorComment = intval(get_post($comment_id)->post_author);
                    $authorTicket = intval(get_post($parent_id)->post_author);

                    if ($authorComment == $authorTicket) {
                        Stg_Helper_Email::sendEventTicket('client_reply', $comment_id);
                    }
                }

                wp_redirect(get_permalink($parent_id), 301);
                exit;
            }
        }
    }

    /**
     * Register and enqueue public-facing style sheet.
     */
    public function enqueue_styles()
    {
        wp_register_style('stgh-plugin-styles', STG_HELPDESK_URL . 'css/stg-helpdesk-public.css');
        wp_enqueue_style('stgh-plugin-styles');
    }

    public function enqueue_scripts()
    {
        global $post;


        if( is_a( $post, 'WP_Post' )) {


            $hasForm = strpos($post->post_content,'stg-ticket-form');
            $hasTag =  has_shortcode( $post->post_content, STG_HELPDESK_SHORTCODE_TICKET_FORM);

            if($hasTag or $hasForm !== false){
                wp_enqueue_script('jquery-ui-datepicker');
                wp_enqueue_style('jqueryui', '//ajax.googleapis.com/ajax/libs/jqueryui/1.11.4/themes/smoothness/jquery-ui.css', false, null );


                wp_enqueue_script('stgh-custom-form-script', STG_HELPDESK_URL . 'js/public_custom_form.js',
                    array('jquery'), STG_HELPDESK_VERSION);
            }
        }
    }

    /**
     * Register custom inline styles from admin config
     */
    public function init_inline_style()
    {
        wp_enqueue_style('custom-stgh-inline-style', STG_HELPDESK_URL . 'css/custom-stgh-inline-style.css');

        /**
         * Custom css
         */
        if (stgh_get_option('stgh_custom_style')) {
            wp_add_inline_style('custom-stgh-inline-style', stgh_get_option('stgh_custom_style'));
        }

        //style register_menu_toolbar
        wp_add_inline_style('custom-stgh-inline-style', '#wp-admin-bar-stgh-helpdesk .ab-icon:before {
                    content: \'\f468\';
                    top: 3px;
        }');

    }

    /**
     * Tumbnail in top toolbar
     */
    public function registerMenuToolbar()
    {
        global $wp_admin_bar;

        if ((!stgh_current_user_can('administrator') && stgh_current_user_can('edit_ticket')) || !is_admin_bar_showing()) return;

        $siteUrl = get_site_url();

        $args = array(
            'id' => 'stgh-helpdesk',
            'title' => '<span class="ab-icon"></span> ' . stgh_menu_count_tickets(),
            'href' => $siteUrl.'/wp-admin/edit.php?post_type=' . STG_HELPDESK_POST_TYPE,
            'parent' => null,
            'group' => null,
            'meta' => array(),
        );
        $wp_admin_bar->add_menu($args);
    }

    public function removeNodes(){
        global $wp_admin_bar;
        $node = $wp_admin_bar->get_node('view');
        if(!empty($node))
        {
            if($wp_admin_bar->get_node('view')->title == "View Custom Field"
            or $wp_admin_bar->get_node('view')->title == "View Contact Form"
            or $wp_admin_bar->get_node('view')->title == "View Reply")
               $wp_admin_bar->remove_menu('view');
        }
    }

    /**
     * Get params attachment
     * @param $query
     */
    public function attachment_query($query)
    {
        if ($query->is_main_query() && isset($_GET['ticket-attachment'])) {
            $query->set('ticket-attachment', filter_input(INPUT_GET, 'ticket-attachment', FILTER_SANITIZE_NUMBER_INT));
        }
    }

    /**
     * Add endpoint
     */
    public function attachment_endpoint()
    {
        add_rewrite_endpoint('ticket-attachment', EP_PERMALINK);
    }


    /**
     * Add custom fields on page user profile
     * @param $user
     */
    public function add_custom_user_profile_fields($user)
    {
        Stg_Helper_Template::getTemplate('stg-profile-user-fields', array('user' => $user));
    }

    /**
     * Save data user profile custom fields
     * @param $user_id
     * @return bool
     */
    public function save_custom_user_profile_fields($user_id)
    {
        if (!stgh_current_user_can('edit_user', $user_id)) {
            return FALSE;
        }
        update_user_meta($user_id, '_stgh_crm_company', sanitize_text_field($_POST['_stgh_crm_company']));
        update_user_meta($user_id, '_stgh_crm_skype', sanitize_text_field($_POST['_stgh_crm_skype']));
        update_user_meta($user_id, '_stgh_crm_phone', sanitize_text_field($_POST['_stgh_crm_phone']));
        update_user_meta($user_id, '_stgh_crm_position', sanitize_text_field($_POST['_stgh_crm_position']));
        update_user_meta($user_id, '_stgh_crm_site', sanitize_text_field($_POST['_stgh_crm_site']));
    }




    /**
     * Page ticket attachment
     */
    public function page_attachment()
    {
        Stg_Helper_UploadFiles::handlerPageAttachment();
    }

    
    function run_updater(){
        $pluginData = stgh_get_plugin_data();
        $pluginCurrentVersion = $pluginData['Version'];


        $adapterName = 'Mycatchers';
        $data = array(
            'Mycatchers' => array(
                'license' => 'none',
            )
        );

        
        if($adapterName != false){

            $updater = new \StgHelpdesk\Updater\AutoUpdater($adapterName, $pluginCurrentVersion, $data);
        }

    }



    /**
     * @return Stg_Helpdesk_Init
     */
    public static function getInstance()
    {
        if (null == self::$instance) {
            self::$instance = new self;
        }

        return self::$instance;
    }

}

