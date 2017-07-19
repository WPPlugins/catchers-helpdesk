<?php
//add_filter('stgh_plugin_settings', 'stgh_core_fileupload', -1, 1);

function stgh_core_fileupload($def)
{
    $settings = array(
        'file_upload' => array(
            'name' => __('File upload', STG_HELPDESK_TEXT_DOMAIN_NAME),
            'options' => array(
                /* array(
                     'name' => __('Enable File Upload', STG_HELPDESK_TEXT_DOMAIN_NAME),
                     'id' => 'stgh_enable_attachment',
                     'type' => 'checkbox',
                     'default' => stgh_get_option('stgh_enable_attachment', false),
                     'desc' => __('Do you want to allow your users to upload attachments? <br> (Option is also used for file send through the mail server)', STG_HELPDESK_TEXT_DOMAIN_NAME)
                 ),*/
                array(
                    'name' => __('Maximum files', STG_HELPDESK_TEXT_DOMAIN_NAME),
                    'id' => 'stgh_attachment_max',
                    'type' => 'text',
                    'default' => 2,
                    'desc' => __('How many files can a user attach to a ticket?', STG_HELPDESK_TEXT_DOMAIN_NAME)
                ),
                array(
                    'name' => __('Allowed types', STG_HELPDESK_TEXT_DOMAIN_NAME),
                    'id' => 'stgh_attachment_allow_ext',
                    'type' => 'textarea',
                    'default' => 'jpg,jpeg,png,gif,pdf,doc,docx,ppt,pptx,pps,ppsx,odt,xls,xlsx,mp3,m4a,ogg,wav,mp4,m4v,mov,wmv,avi,mpg,ogv,3gp,3g2,zip',
                    'desc' => __('Which file types do you allow your users to attach uncompressed? Other files will be converted zip. <br> (Option is also used for file through the mail server)', STG_HELPDESK_TEXT_DOMAIN_NAME)
                ),
            )
        ),
    );

    return array_merge($def, $settings);

}