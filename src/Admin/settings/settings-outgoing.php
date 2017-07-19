<?php

add_filter('stgh_plugin_settings', 'stgh_core_outgoing', 97, 1);
/**
 * @param $def
 * @return array
 */
function stgh_core_outgoing($def)
{
    $settings = array(
        'outgoing' => array(
            'name' => __('Outgoing mail', STG_HELPDESK_TEXT_DOMAIN_NAME),
            'options' => array(
                array(
                    'type' => 'heading',
                    'name' => __('From', STG_HELPDESK_TEXT_DOMAIN_NAME)
                ),
                array(
                    'name' => __('From  e-mail', STG_HELPDESK_TEXT_DOMAIN_NAME),
                    'id' => 'sender_email',
                    'type' => 'note',
                    'desc' => stgh_get_option('stg_mail_login', 'no-reply@example.com'),
                ),
                array(
                    'name' => __('From name*', STG_HELPDESK_TEXT_DOMAIN_NAME),
                    'id' => 'sender_name',
                    'type' => 'text',
                    'default' => get_bloginfo('name')
                ),
                array(
                    'type' => 'heading',
                    'name' => __('Advanced settings', STG_HELPDESK_TEXT_DOMAIN_NAME)
                ),
                array(
                    'name' => __('Set smtp settings', STG_HELPDESK_TEXT_DOMAIN_NAME),
                    'id' => 'smtp_settings_enabled',
                    'type' => 'checkbox',
                    'default' => stgh_get_option('smtp_settings_enabled', false),
                ),
                array(
                    'name' => __('From  e-mail', STG_HELPDESK_TEXT_DOMAIN_NAME),
                    'id' => 'new_sender_email',
                    'type' => 'text',
                    'hidden' => true,
                    'default' => stgh_get_option('stg_mail_login', 'no-reply@example.com'),
                ),
                array(
                    'name' => __('Host*', STG_HELPDESK_TEXT_DOMAIN_NAME),
                    'id' => 'mail_sender_server',
                    'hidden' => true,
                    'type' => 'text',
                    'desc' => '',
                ),
                array(
                    'name' => __('Username*', STG_HELPDESK_TEXT_DOMAIN_NAME),
                    'type' => 'text',
                    'hidden' => true,
                    'id' => 'mail_sender_login',
                ),
                array(
                    'name' => __('Password*', STG_HELPDESK_TEXT_DOMAIN_NAME),
                    'id' => 'mail_sender_pwd',
                    'type' => 'text',
                    'hidden' => true,
                    'is_password' => true,
                ),
                array(
                    'name' => __('Encryption*', STG_HELPDESK_TEXT_DOMAIN_NAME),
                    'id' => 'mail_sender_encryption',
                    'type' => 'select',
                    'hidden' => true,
                    'options' => array("" => "------", "SSL" => "SSL", "TLS" => "TLS"),
                    'default' => 'SSL',
                ),
                array(
                    'name' => __('Port*', STG_HELPDESK_TEXT_DOMAIN_NAME),
                    'id' => 'mail_sender_port',
                    'type' => 'text',
                    'hidden' => true,
                    'desc' => __('Default ports',
                            STG_HELPDESK_TEXT_DOMAIN_NAME) . ':<br>25 - TCP, 465 - SSL/TLS, 587 - STARTTLS',
                ),
                array(
                    'type' => 'heading',
                    'name' => __('Send a test e-mail', STG_HELPDESK_TEXT_DOMAIN_NAME)
                ),
                array(
                    'name' => __('E-mail', STG_HELPDESK_TEXT_DOMAIN_NAME),
                    'id' => 'stgh_test_to_email',
                    'type' => 'text',
                    'default' => '',
                    'desc' => __('Enter e-mail address to send the test letter (save first)', STG_HELPDESK_TEXT_DOMAIN_NAME)
                ),
                array(
                    'name' => __('Send test e-mail', STG_HELPDESK_TEXT_DOMAIN_NAME),
                    'action' => 'send_test_email',
                    'type' => 'ajax-button',
                    'label' => __('Send', STG_HELPDESK_TEXT_DOMAIN_NAME),
                    'desc' => __('Save changes first', STG_HELPDESK_TEXT_DOMAIN_NAME),
                    'success_label' => __('Message sent successfully', STG_HELPDESK_TEXT_DOMAIN_NAME),
                    'error_label' => __('Error. Message not sent', STG_HELPDESK_TEXT_DOMAIN_NAME),
                    'sucess_callback' => 'successSendMail',
                    'error_callback' => 'errorSendMail'
                )
            )
        ),
    );

    return array_merge($def, $settings);
}
