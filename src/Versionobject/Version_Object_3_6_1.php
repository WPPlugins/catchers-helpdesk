<?php

namespace StgHelpdesk\Versionobject;


use StgHelpdesk\Ticket\Stg_Helpdesk_MetaBoxes;
use StgHelpdesk\Admin\Stg_Helpdesk_Help_Catcher;

class Version_Object_3_6_1 extends Version_Object
{

    public function registerSaveTicketFields()
    {
        add_action('save_post', 'stgh_save_custom_fields_3_6', 10, 2);
    }

    /**
     * Plugins thumbnail in adminbar
     */
    public function customEnqueueStyles()
    {
        $path = $this->getPluginIcon();
        echo '<style type="text/css">#wp-admin-bar-stgh-helpdesk>.ab-item .ab-icon {
				background-image: url("' . $path . '");
				margin-right: 5px;
				}
			</style>';
    }

    public function getPluginIcon()
    {
        return STG_HELPDESK_URL . 'images/stgh-ticket-icon.png';
    }

    public function getProVerMenuStyle()
    {
        return 'margin-top:-6px;';
    }

    public function getTitleSelector()
    {
        return '".wrap > h2"';
    }

    public function enqueueAdminScripts()
    {
        wp_enqueue_script('stgh-admin-autocomplete-script', STG_HELPDESK_URL . 'js/admin/admin_autocomplete_3_6.js',
            array('jquery', 'stgh-select2', 'jquery-ui-autocomplete'),
            STG_HELPDESK_VERSION);
    }

    public function register_new_user($user_login, $user_email)
    {
        /**
         * Handles registering a new user.
         *
         * @param string $user_login User's username for logging in
         * @param string $user_email User's email address to send password and add
         * @return int|WP_Error Either user's ID or error on failure.
         */
        $errors = new \WP_Error();

        $sanitized_user_login = sanitize_user($user_login);
        $user_email = apply_filters('user_registration_email', $user_email);

        // Check the username
        if ($sanitized_user_login == '') {
            $errors->add('empty_username', __('<strong>ERROR</strong>: Please enter a username.'));
        } elseif (!validate_username($user_login)) {
            $errors->add('invalid_username',
                __('<strong>ERROR</strong>: This username is invalid because it uses illegal characters. Please enter a valid username.'));
            $sanitized_user_login = '';
        } elseif (username_exists($sanitized_user_login)) {
            $errors->add('username_exists',
                __('<strong>ERROR</strong>: This username is already registered. Please choose another one.'));
        }

        // Check the e-mail address
        if ($user_email == '') {
            $errors->add('empty_email', __('<strong>ERROR</strong>: Please type your e-mail address.'));
        } elseif (!is_email($user_email)) {
            $errors->add('invalid_email', __('<strong>ERROR</strong>: The email address isn&#8217;t correct.'));
            $user_email = '';
        } elseif (email_exists($user_email)) {
            $errors->add('email_exists',
                __('<strong>ERROR</strong>: This email is already registered, please choose another one.'));
        }

        do_action('register_post', $sanitized_user_login, $user_email, $errors);

        $errors = apply_filters('registration_errors', $errors, $sanitized_user_login, $user_email);

        if ($errors->get_error_code()) {
            return $errors;
        }

        $user_pass = wp_generate_password(12, false);
        $user_id = wp_create_user($sanitized_user_login, $user_pass, $user_email);
        if (!$user_id) {
            $errors->add('registerfail',
                sprintf(__('<strong>ERROR</strong>: Couldn&#8217;t register you&hellip; please contact the <a href="mailto:%s">webmaster</a> !'),
                    get_option('admin_email')));

            return $errors;
        }

        update_user_option($user_id, 'default_password_nag', true, true); //Set up the Password change nag.

        //wp_new_user_notification($user_id, $user_pass);

        return $user_id;
    }


    public function enqueueHelpcatcherScript()
    {

        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('stgh-helpcatcher-admin-script', STG_HELPDESK_URL . 'js/admin/helpcatcher3.6.js', array('wp-color-picker'), false, true);

        wp_localize_script('stgh-helpcatcher-admin-script', 'stghHcLocal', array(
            'widgetCodeDefault' => Stg_Helpdesk_Help_Catcher::getCode(),
        ));
    }


    public function getHelpcatcherSettings()
    {
        $settings = array(
            'helpcatcher' => array(
                'name' => __('Help Catcher', STG_HELPDESK_TEXT_DOMAIN_NAME),
                'options' => array(
                    array(
                        'name' => __('Enabled Help Catcher', STG_HELPDESK_TEXT_DOMAIN_NAME),
                        'id' => 'helpcatcher_enable',
                        'type' => 'checkbox',
                        'default' => false,
                    ),

                    array(
                        'name' => __('File Upload Configuration', STG_HELPDESK_TEXT_DOMAIN_NAME),
                        'id' => 'helpcatcher_enable_attachment',
                        'type' => 'checkbox',
                        'default' => false,
                        'desc' => '<p class="description">' . __('Do you want to allow your users to upload attachments in Help Catcher?', STG_HELPDESK_TEXT_DOMAIN_NAME) . "</p>"
                    ),

                    array(
                        'name' => __('Start conversation', STG_HELPDESK_TEXT_DOMAIN_NAME),
                        'id' => 'helpcatcher_letter_start',
                        'type' => 'text',
                        'desc' => 'What we can help you with?',
                        'default' => __('What we can help you with?', STG_HELPDESK_TEXT_DOMAIN_NAME)
                    ),


                    array(
                        'name' => __('Result message', STG_HELPDESK_TEXT_DOMAIN_NAME),
                        'id' => 'helpcatcher_result_msg',
                        'type' => 'editor',
                        'default' => __('<p><b>Message sent!</b><br /> We just got your request! And do our best to answer emails as soon as possible</p>', STG_HELPDESK_TEXT_DOMAIN_NAME),
                        'editor_settings' => array('quicktags' => true, 'textarea_rows' => 10, 'wpautop' => false, 'teeny' => true, 'media_buttons' => false),
                    ),


                    array(
                        'name' => __('Button color', STG_HELPDESK_TEXT_DOMAIN_NAME),
                        'id' => 'helpcatcher_button_color',
                        'type' => 'text',
                        'default' => '#f9c3a7',
                    ),

                    array(
                        'name' => __('Position', STG_HELPDESK_TEXT_DOMAIN_NAME),
                        'id' => 'helpcatcher_position',
                        'type' => 'select',
                        'options' => array(
                            "right" => __('Bottom right', STG_HELPDESK_TEXT_DOMAIN_NAME),
                            "left" => __('Bottom left', STG_HELPDESK_TEXT_DOMAIN_NAME),
                        ),
                        'default' => 'right',
                    ),

                    array(
                        'name' => __('Widget code', STG_HELPDESK_TEXT_DOMAIN_NAME),
                        'id' => 'helpcatcher_embed_code',
                        'type' => 'textarea',
                        'desc' => __('Below code needs to add on page for widget activation (save changes first)', STG_HELPDESK_TEXT_DOMAIN_NAME),
                        'default' => Stg_Helpdesk_Help_Catcher::getCode(),
                        'is_code' => true
                    ),
                )
            ),
        );

        return $settings;
    }

}