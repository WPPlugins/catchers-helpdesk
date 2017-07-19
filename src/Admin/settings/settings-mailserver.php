<?php

use StgHelpdesk\Core\Stg_Helpdesk_Activator;

add_filter('stgh_plugin_settings', 'stgh_core_mailserver', 96, 1);
/**
 * @param $def
 * @return array
 */
function stgh_core_mailserver($def)
{
    $settings = array(
        'mailserver' => array(
            'name' => __('Incoming mail', STG_HELPDESK_TEXT_DOMAIN_NAME),
            'options' => array(

                array(
                    'name' => __('Mail check interval', STG_HELPDESK_TEXT_DOMAIN_NAME),
                    'id' => 'stg_mail_interval',
                    'type' => 'select',
                    'options' => array(
                        "1min" => __('every minute', STG_HELPDESK_TEXT_DOMAIN_NAME),
                        "5min" => __('every five minutes', STG_HELPDESK_TEXT_DOMAIN_NAME),
                        "15min" => __('every fifteen minutes', STG_HELPDESK_TEXT_DOMAIN_NAME),
                        "30min" => __('every thirty minutes', STG_HELPDESK_TEXT_DOMAIN_NAME),
                        "hourly" => __('every hour', STG_HELPDESK_TEXT_DOMAIN_NAME),
                        "never" => __('never', STG_HELPDESK_TEXT_DOMAIN_NAME),
                    ),
                    'default' => stgh_get_option('stg_mail_interval', 'hourly'),
                ),
                array(
                    'name' => __('Truncate emails after this line', STG_HELPDESK_TEXT_DOMAIN_NAME),
                    'id' => 'stgh_email_cut_line',
                    'type' => 'text',
                    'default' => '---- ' . __('Please type your reply above this line', STG_HELPDESK_TEXT_DOMAIN_NAME) . ' ----',
                ),
                /**************/
                array(
                    'name' => 'Server Settings',
                    'type' => 'heading',
                ),
                array(
                    'name' => __('Username*', STG_HELPDESK_TEXT_DOMAIN_NAME),
                    'type' => 'text',
                    'id' => 'stg_mail_login',
                    'desc' => __('e-mail address for auto-reply and notification', STG_HELPDESK_TEXT_DOMAIN_NAME)

                ),
                array(
                    'name' => __('Password*', STG_HELPDESK_TEXT_DOMAIN_NAME),
                    'id' => 'mail_pwd',
                    'type' => 'text',
                    'is_password' => true,
                ),

                array(
                    'name' => __('Protocol*', STG_HELPDESK_TEXT_DOMAIN_NAME),
                    'id' => 'mail_protocol_visible',
                    'type' => 'select',
                    'options' => array("POP3" => "POP3", "IMAP" => "IMAP", "" => "-------- ", "Gmail" => "Gmail", "Yahoo" => "Yahoo", "Outlook" => "Outlook", "Yandex" => "Yandex"),
                    'default' => 'POP3',
                ),
                array(
                    'id' => 'mail_protocol',
                    'hidden' => true,
                    'type' => 'text',
                    'default' => 'POP3',
                ),
                array(
                    'name' => __('Host*', STG_HELPDESK_TEXT_DOMAIN_NAME),
                    'id' => 'mail_server',
                    'type' => 'text',
                    'desc' => '',
                ),
                array(
                    'name' => __('Encryption*', STG_HELPDESK_TEXT_DOMAIN_NAME),
                    'id' => 'mail_encryption',
                    'type' => 'select',
                    'options' => array("" => "------", "SSL" => "SSL", "TLS" => "TLS"),
                    'default' => 'SSL',
                ),
                array(
                    'name' => __('Port*', STG_HELPDESK_TEXT_DOMAIN_NAME),
                    'id' => 'mail_port',
                    'type' => 'text',
                    'desc' => __('Default ports',
                            STG_HELPDESK_TEXT_DOMAIN_NAME) . ':<br>POP3 - 110<br>POP3-SSL - 995<br>IMAP - 143<br>IMAP-SSL- 993 ',
                ),

                /**************/
                /*array(
                    'name' => '~<i>Folders</i>~',
                    'type' => 'heading',
                ),
                array(
                    'name' => __('Folder', STG_HELPDESK_TEXT_DOMAIN_NAME),
                    'id' => 'mail_folder',
                    'type' => 'select',
                    'options' => ["INBOX" => "INBOX", "OUTBOX" => "OUTBOX"],
                    'default' => 'INBOX',
                    'desc' => __('Folder from e-mails will be downloaded', STG_HELPDESK_TEXT_DOMAIN_NAME)
                ),
                array(
                    'name' => __('Archive folder', STG_HELPDESK_TEXT_DOMAIN_NAME),
                    'id' => 'mail_archive',
                    'type' => 'select',
                    'options' => ["INBOX/Trash" => "INBOX/Trash"],
                    'default' => 'INBOX/Trash',
                    'desc' => __('Folder where e-mails will be moved after download', STG_HELPDESK_TEXT_DOMAIN_NAME)
                ),*/
                array(
                    'name' => __('Check configuration', STG_HELPDESK_TEXT_DOMAIN_NAME),
                    'type' => 'custom',
                    'custom' => '<a id="stgh_chk_conf" class="button" href="' . add_query_arg(array('stgh-do' => 'check-connection', 'tconnection' => '1')) . '">' . __('Check',
                            STG_HELPDESK_TEXT_DOMAIN_NAME) . '</a> <img class="stgh_loader_image" hidden id="stgh_loader" src="' . STG_HELPDESK_URL . 'images/ajax-loader.gif" >' .
                        '<p class="description">' . __('Test your configuration (save first)',
                            STG_HELPDESK_TEXT_DOMAIN_NAME) . '</p>',
                ),
                array(
                    'name' => __('Get mails', STG_HELPDESK_TEXT_DOMAIN_NAME),
                    'type' => 'custom',
                    'custom' => '<a  id="stgh_get_mails" class="button" href="' . add_query_arg(array('stgh-do' => 'check-new-mail', 'takeemails' => '1')) . '">' . __('Get mails',
                            STG_HELPDESK_TEXT_DOMAIN_NAME) . '</a><img class="stgh_loader_image" hidden id="stgh_loader_get_mails" src="' . STG_HELPDESK_URL . 'images/ajax-loader.gif" >'
                ),
            )
        ),
    );

    return array_merge($def, $settings);
}

function get_mailserver_link()
{
    return add_query_arg(array('post_type' => STG_HELPDESK_POST_TYPE, 'page' => 'settings', 'tab' => 'mailserver'),
        admin_url('edit.php'));
}