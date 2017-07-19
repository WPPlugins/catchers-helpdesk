<?php

use StgHelpdesk\Helpers\Stg_Helper_UploadFiles;

?>

<?php if (!empty($attachments)) : ?>
    <div class="stgh-attachements-block">
        <div class="stgh-attachements-title"><b><?php _e('Attachments', STG_HELPDESK_TEXT_DOMAIN_NAME) ?>:</b></div>
        <ul>
            <?php foreach ($attachments as $attachment_id => $attachment) {
                $filename = explode('/', $attachment['url']);
                $filename = htmlspecialchars($filename[count($filename) - 1]);

                //type ticket-attachment
                $upload_dir = wp_upload_dir();
                $filepath = trailingslashit($upload_dir['basedir']) . 'stg-helpdesk/ticket_' . get_the_ID() . '/' . $filename;
                $filesize = file_exists($filepath) ? Stg_Helper_UploadFiles::getHumanFilesize(filesize($filepath), 1) : '';
                $link = add_query_arg(array('ticket-attachment' => $attachment['id']), home_url());

                //type base
                //$link = $attachment['url'];

                echo "<li><a href=\"{$link}\" target=\"_blank\">{$filename}</a> {$filesize}</li>";
            }
            ?>
        </ul>
    </div>
<?php endif; ?>


