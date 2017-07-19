<?php

add_filter('stgh_plugin_settings', 'stgh_core_get_help', 100, 1);

/**
 * @param $def
 * @return array
 */
function stgh_core_get_help($def)
{
    $user = get_user_by('id', stgh_get_current_user_id());
    $settings = array(
        'gethelp' => array(
            'name' => __('Get help', STG_HELPDESK_TEXT_DOMAIN_NAME),
            'stgh-no-save-button' => true,
            'options' => array(
                array(
                    'id' => 'stgh_custom_style',
                    'type' => 'custom',
                    'custom' => '<form><div class="stgh-gethelp">
                        <div><span class="support-header">Have a question about setting up or using Helpdesk? Catchers team will be glad to help you.</span></div><div></div>
                        <div>
                            <input required type="text" placeholder="' . __('Name', STG_HELPDESK_TEXT_DOMAIN_NAME) . '" value="' . (!empty($user->user_nicename) ? $user->user_nicename : '') . '"  name="stgh_gethelp_form_name">
                        </div>
                        <div>
                            <input required type="email" placeholder="E-mail" value="' . (!empty($user->user_email) ? $user->user_email : '') . '" name="stgh_gethelp_form_email">
                        </div>
                        <div>
                            <input required type="text" placeholder="' . __('Subject', STG_HELPDESK_TEXT_DOMAIN_NAME) . '" name="stgh_gethelp_form_subject">
                        </div>
                        <div>
                            <textarea required placeholder="' . __('Message', STG_HELPDESK_TEXT_DOMAIN_NAME) . '" name="stgh_gethelp_form_msg"></textarea>
                        </div>
                        <div class="stgh_width100pro">
                             <button class="button button-primary" id="stgh_submit_button" type="submit">' . __('Send', STG_HELPDESK_TEXT_DOMAIN_NAME) . '</button>
                                <img hidden class="stgh_gethelploader"  id="stgh_loader" src="' . STG_HELPDESK_URL . 'images/ajax-loader.gif" >
                        </div>
                        </div>
                        <input hidden name="stgh-do" value="send_gethelp_message"></form>
                        ',
                    'default' => '',
                ),
            )
        ),
    );

    return array_merge($def, $settings);

}