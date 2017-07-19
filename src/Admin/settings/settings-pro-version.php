<?php
//include_once( STG_HELPDESK_PUBLIC . 'template/pro-version.php' );


add_filter('stgh_plugin_settings', 'stgh_core_pro_version', 101, 1);

/**
 * @param $def
 * @return array
 */
function stgh_core_pro_version($def)
{
    $settings = array(
        'proversion' => array(
            'name' => __('Pro-version', STG_HELPDESK_TEXT_DOMAIN_NAME),
            'stgh-no-save-button' => true,
            'options' => array(
                array(
                    'id' => 'stgh_pro_version',
                    'type' => 'custom',
                    'custom' => file_get_contents(STG_HELPDESK_PUBLIC . 'template/pro-version.php'),//include_once( STG_HELPDESK_PUBLIC . 'template/pro-version.php' ),
                    'default' => '',
                ),
            )
        ),
    );

    return array_merge($def, $settings);

}

