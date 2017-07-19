<?php
use StgHelpdesk\Helpers\Stg_Helper_UploadFiles;

if (Stg_Helper_UploadFiles::isEnableAttachment()) :
    ?>
    <div id="stg_ticket_files_block">
        <label for="stg_ticket_files"><b><?php _e('Attachments', STG_HELPDESK_TEXT_DOMAIN_NAME); ?></b></label>
        <input type="file" value="" id="stg_ticket_files" name="<?php echo Stg_Helper_UploadFiles::getFieldName() ?>[]"
               accept="<?php echo Stg_Helper_UploadFiles::getAllowTypeAccept() ?>" multiple="">
    </div>
<?php endif; ?>