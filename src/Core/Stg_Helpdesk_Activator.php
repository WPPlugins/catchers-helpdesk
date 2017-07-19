<?php

namespace StgHelpdesk\Core;

use StgHelpdesk\Helpers\Stg_Helper_Custom_Forms;
/**
 * Fired during plugin activation && deactivation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @package Stg_Helpdesk
 * @subpackage Stg_Helpdesk/includes
 */
class Stg_Helpdesk_Activator
{

    private static $full_cap = array(
        'create_ticket',
        'view_ticket',
        'edit_ticket',
        'close_ticket',
        'edit_other_ticket',
        'reply_ticket',
        'delete_ticket',
        'delete_reply',
        'delete_other_ticket',
        'settings_tickets',
        'assign_ticket'
    );

    private static $manager_cap = array(
        'create_ticket',
        'view_ticket',
        'edit_ticket',
        'close_ticket',
        'edit_other_ticket',
        'reply_ticket',
        'delete_ticket',
        'delete_other_ticket',
        'assign_ticket'
    );

    private static $client_cap = array(
        'create_ticket',
        'view_ticket',
        'reply_ticket',
    );

    /**
     * Activate plugin
     *
     */
    public static function activate()
    {
        self::addTestUserAndTicket();

        self::addRolesAndCapabilities();
        //self::insertUserPages();
        self::setCronEmailTasks();

        self::setAgentNotification();

        
        self::activated();

    }

    
    private static function setAgentNotification()
    {


        if(stgh_get_option('agent_notifications') === false)
        {
            $defNotifications = array("enable_reply_client", "enable_ticket_assign");

            stgh_set_option('agent_notifications', $defNotifications);
            stgh_set_option("enable_reply_client", true);
            stgh_set_option("enable_ticket_assign", true);
        }
    }


    private static function addTestUserAndTicket()
    {

        $posts = get_posts(array('post_type' => STG_HELPDESK_POST_TYPE, 'post_status' => 'any'));

        if (count($posts) == 0) {

            $user = get_user_by('email', 'user2@mycatchers.com');

            if (!$user) {
                $userdata = array(
                    'user_login' => 'user2',
                    'user_pass' => 'user2',
                    'user_email' => 'user2@mycatchers.com',
                    'first_name' => 'Daria',
                    'last_name' => 'Domracheva',
                    'nickname' => 'user2'
                );

                $user_id = wp_insert_user($userdata);
                if (is_wp_error($user_id))
                    return;
                $user = new \WP_User($user_id);
                $user->set_role('stgh_client');

            } else
                $user_id = $user->ID;


            add_user_meta($user_id, 'first_name', 'Daria', true);
            add_user_meta($user_id, 'last_name', 'Domracheva', true);
            add_user_meta($user_id, 'email', 'user2@mycatchers.com', true);
            add_user_meta($user_id, '_stgh_crm_name', 'Daria Domracheva', true);
            add_user_meta($user_id, '_stgh_crm_company', 'Lukky', true);
            add_user_meta($user_id, '_stgh_crm_phone', '+3 893 232 23 32', true);
            add_user_meta($user_id, '_stgh_crm_position', 'consultant', true);

            $testTicket = array(
                'post_title' => 'Hello, lets see an example of the first request from your site',
                'post_content' => 'Hello, lets see an example of the first request from your site',
                'post_status' => 'stgh_new',
                'post_type' => STG_HELPDESK_POST_TYPE,
                'post_author' => $user_id
            );


            $post_id = wp_insert_post($testTicket);

            update_post_meta($post_id, '_stgh_contact', $user_id);

            add_post_meta($post_id, 'post_title_history', $testTicket['post_title'], true);
            add_post_meta($post_id, 'post_status_override_history', $testTicket['post_status'], true);
            add_post_meta($post_id, 'stgh_crm_contact_value_history', $testTicket['post_author'], true);


            $name = stgh_crm_get_user_full_name($user_id);

            if (empty($name))
                $name = $user->user_nicename;

            add_post_meta($post_id, '_stgh_ticket_author_name', $name, true);
            add_post_meta($post_id, '_stgh_ticket_author_email', $user->user_email, true);
            add_post_meta($post_id, '_stgh_ticket_author_roles', implode(' ', $user->roles), true);

        }

        wp_reset_postdata();
    }

    /**
     * Set activation status
     */
    private static function activated()
    {
        add_option('stgh_redirect_to_welcome_page', true);
    }

    /**
     * Deactivate plugin
     *
     */
    public static function deactivate()
    {
        self::removeRolesAndCapabilities();
        self::unsetCronEmailTasks();
        //self::removeUserPages();
        flush_rewrite_rules();

        $currentUploadDir = wp_upload_dir();

        foreach (glob($currentUploadDir['path']."/catchers-helpdesk*.zip") as $filename) {
            @unlink($filename);
        }
    }

    private static function removeUserPages()
    {
        remove_shortcode(STG_HELPDESK_SHORTCODE_TICKET_LIST);
        remove_shortcode(STG_HELPDESK_SHORTCODE_TICKET_FORM);

        $options = maybe_unserialize(get_option('stg_installed_pages', array()));

        if (!empty($options)) {
            if (isset($options['ticket-list'])) {
                wp_delete_post($options['ticket-list'], true);
            }
            if (isset($options['ticket-form'])) {
                wp_delete_post($options['ticket-form'], true);
            }
        }
    }


    /**
     * Add manager and client roles and define their capabilities
     */
    private static function addRolesAndCapabilities()
    {
        /**
         * full list of capabilities.
         *
         * @var array
         */
        $full_cap = apply_filters('stgh_user_capabilities_full', self::$full_cap);

        /**
         * Manager list of capabilities.
         *
         * @var array
         */
        $manager_cap = apply_filters('stgh_user_capabilities_manager', self::$manager_cap);

        /**
         * Client list of capabilities.
         *
         * @var array
         */
        $client_cap = apply_filters('stgh_user_capabilities_client', self::$client_cap);

        /* Get roles to copy capabilities from */
        $editor = get_role('editor');
        $author = get_role('author');
        $contributor = get_role('contributor');
        $subscriber = get_role('subscriber');
        $admin = get_role('administrator');

        /* Add the new roles */
        remove_role('stgh_manager');
        $manager = add_role('stgh_manager', __('Helpdesk manager', STG_HELPDESK_TEXT_DOMAIN_NAME), $subscriber->capabilities);
        $client = add_role('stgh_client', __('Helpdesk client', STG_HELPDESK_TEXT_DOMAIN_NAME), $subscriber->capabilities);

        $manager->add_cap('edit_users');
        $manager->add_cap('list_users');
        $manager->add_cap('edit_posts');
        $manager->add_cap('attach_files');
        $manager->add_cap('upload_files');
        $manager->add_cap('manage_categories');

        /**
         * Add full capacities to admin roles
         */
        foreach ($full_cap as $cap) {
            // Add all the capacities to admin in addition to full WP capacities
            if (null != $admin) {
                $admin->add_cap($cap);
            }
        }
        /**
         * Add limited capacities to manager
         */
        foreach ($manager_cap as $cap) {
            if (null != $manager) {
                $manager->add_cap($cap);
            }

            if (null != $editor) {
                $editor->add_cap($cap);
            }
        }

        /**
         * Add limited capacities to author, subscriber and plugin client
         */
        foreach ($client_cap as $cap) {
            if (null != $author) {
                $author->add_cap($cap);
            }
            if (null != $contributor) {
                $contributor->add_cap($cap);
            }
            if (null != $client) {
                $client->add_cap($cap);
            }
            if (null != $subscriber) {
                $subscriber->add_cap($cap);
            }
        }
    }


    private static function removeRolesAndCapabilities()
    {
        // Remove plugin roles
        remove_role('stgh_client');
        remove_role('stgh_manager');

        remove_all_filters('stgh_user_capabilities_full');
        remove_all_filters('stgh_user_capabilities_manager');
        remove_all_filters('stgh_user_capabilities_edit');
        remove_all_filters('stgh_user_capabilities_client');

        /* Get roles to remove capabilities */
        $editor = get_role('editor');
        $author = get_role('author');
        $contributor = get_role('contributor');
        $admin = get_role('administrator');
        /**
         * Manager list of capabilities.
         *
         * @var array
         */
        $manager_cap = apply_filters('stgh_user_capabilities_manager', self::$manager_cap);

        // Remove plugin capabilities for WP roles
        foreach (self::$full_cap as $cap) {
            if (null != $admin) {
                $admin->remove_cap($cap);
            }
        }

        /**
         * Add limited capacities to manager
         */
        foreach ($manager_cap as $cap) {
            if (null != $editor) {
                $editor->remove_cap($cap);
            }
        }

        foreach (self::$client_cap as $cap) {
            if (null != $author) {
                $author->remove_cap($cap);
            }
            if (null != $contributor) {
                $contributor->remove_cap($cap);
            }
        }
    }

    /**
     * Create and insert pages ticket
     */
    public static function insertUserPages()
    {
        //$options = maybe_unserialize( get_option( 'stg_options', array() ) );

        $options = array();
        $update = false;

        //if ( empty( $options['ticket_list'] ) ) {

        $ticketList = array(
            'post_content' => '[' . STG_HELPDESK_SHORTCODE_TICKET_LIST . ']',
            'post_title' => 'Tickets list',
            'post_name' => sanitize_title('Tickets'),
            'post_type' => 'page',
            'post_status' => 'publish',
            'ping_status' => 'closed',
            'comment_status' => 'closed'
        );

        $list = wp_insert_post($ticketList, true);

        if (!is_wp_error($list) && is_int($list)) {
            $options['ticket-list'] = $list;
            $update = true;
        }


        //if ( empty( $options['ticket_submit'] ) ) {

        $ticketForm = array(
            'post_content' => '[' . STG_HELPDESK_SHORTCODE_TICKET_FORM . ']',
            'post_title' => 'Ticket form',
            'post_name' => sanitize_title('Ticket form'),
            'post_type' => 'page',
            'post_status' => 'publish',
            'ping_status' => 'closed',
            'comment_status' => 'closed'
        );

        $form = wp_insert_post($ticketForm, true);

        if (!is_wp_error($form) && is_int($form)) {
            $options['ticket-form'] = $form;
            $update = true;
        }

        if ($update) {
            update_option('stg_installed_pages', serialize($options));
        }
//
//        if ( !empty( $options['ticket-list'] ) && !empty( $options['ticket_form'] ) ) {
//            delete_option( 'stg_setup' );
//        }
    }

    /**
     * Activate cron load email tasks
     * get all tasks d(_get_cron_array());
     * @param bool $interval
     */
    public static function setCronEmailTasks($interval = false)
    {
        add_filter('cron_schedules', 'stgh_cron_add_schedules');


        if (!$interval) {
            $interval = stgh_get_option('stg_mail_interval', 'hourly');
        }

        if ($interval != wp_get_schedule('stgh_load_email_hook')) {
            self::unsetCronEmailTasks(); //remove existing
            //try to create the new schedule with the first run in 5 minutes
            if (false === wp_schedule_event(time() + 5 * 60, $interval, 'stgh_load_email_hook')) {
                //d(" Failed to set up cron task: $interval");
            } else {
                //d("Set up cron task: $interval");
            }
        }
    }

    /**
     * Deactivate cron load email tasks
     */
    public static function unsetCronEmailTasks()
    {
        wp_clear_scheduled_hook('stgh_load_email_hook');
    }
}
