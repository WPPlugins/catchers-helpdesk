<?php
add_filter('stgh_plugin_settings', 'stgh_core_settings', 95, 1);

function stgh_core_settings($def)
{

    $settings = array(
        'core' => array(
            'name' => __('General', STG_HELPDESK_TEXT_DOMAIN_NAME),
            'options' => array(
                array(
                    'name' => __('Default assignee', STG_HELPDESK_TEXT_DOMAIN_NAME),
                    'id' => 'assignee_default',
                    'type' => 'select',
                    'desc' => __('New tickets will be automatically assigned to that person', STG_HELPDESK_TEXT_DOMAIN_NAME),
                    'options' => stgh_list_users('edit_ticket'),
                    'default' => ''
                ),
                array(
                    'name' => __('File Upload Configuration', STG_HELPDESK_TEXT_DOMAIN_NAME),
                    'id' => 'stgh_enable_attachment',
                    'type' => 'checkbox',
                    'default' => stgh_get_option('stgh_enable_attachment', false),
                    'desc' => __('Do you want to allow your users to upload attachments? <br> (Option is also used for file send through the mail server)', STG_HELPDESK_TEXT_DOMAIN_NAME)
                ),
                array(
                    'name' => __('Tracking', STG_HELPDESK_TEXT_DOMAIN_NAME),
                    'type' => 'heading',
                ),
                array(
                    'name' => __('Open tracking', STG_HELPDESK_TEXT_DOMAIN_NAME),
                    'id' => 'open_tracking',
                    'type' => 'checkbox',
                    'default' => false,
                    'desc' => __('Enable open tracking', STG_HELPDESK_TEXT_DOMAIN_NAME)
                ),
                array(
                    'name' => __('Auto reply', STG_HELPDESK_TEXT_DOMAIN_NAME),
                    'type' => 'heading',
                ),
                array(
                    'name' => __('Status', STG_HELPDESK_TEXT_DOMAIN_NAME),
                    'id' => 'enable_open',
                    'type' => 'checkbox',
                    'default' => stgh_get_option('enable_auto_reply', false),
                    'desc' => __('Enable auto reply', STG_HELPDESK_TEXT_DOMAIN_NAME)
                ),

                array(
                    'name' => __('Message', STG_HELPDESK_TEXT_DOMAIN_NAME),
                    'hidden' => true,
                    'id' => 'content_auto_reply',
                    'type' => 'editor',
                    'default' => __('<br /><p>Hello, <br /> We just got your help request! And do our best to answer emails as soon as possible, with most inquiries receiving a response within about a day.</p>', STG_HELPDESK_TEXT_DOMAIN_NAME),
                    'editor_settings' => array('quicktags' => true, 'textarea_rows' => 10, 'wpautop' => false, 'teeny' => true),
                ),
                                array(
                    'name' => __('Plugin pages', STG_HELPDESK_TEXT_DOMAIN_NAME),
                    'type' => 'heading',
                ),
                array(
                    'name' => __('Create plugin pages', STG_HELPDESK_TEXT_DOMAIN_NAME),
                    'type' => 'custom',
                    'custom' => '<a id="stgh_chk_conf" class="button" href="' .  stgh_link_to_create_user_pages() . '">' . __('Create',
                            STG_HELPDESK_TEXT_DOMAIN_NAME) . '</a> <img class="stgh_loader_image" hidden id="stgh_loader" src="' . STG_HELPDESK_URL . 'images/ajax-loader.gif" >',
                ),
                /*array(
                    'name' => __('Plugin pages', STG_HELPDESK_TEXT_DOMAIN_NAME),
                    'type' => 'heading',
                ),
                array(
                    'name' => __('Ticket submission', STG_HELPDESK_TEXT_DOMAIN_NAME),
                    'id' => 'ticket_submit_page',
                    'type' => 'select',
                    'desc' => sprintf(__('The page used for ticket submission. This page should contain the shortcode %s',
                        STG_HELPDESK_TEXT_DOMAIN_NAME), '<code>[ticket-submit]</code>'),
                    'options' => stgh_get_pages(),
                    'default' => ''
                )*/
            )
        ),
    );

    return array_merge($def, $settings);

}