<?php
use StgHelpdesk\Helpers\Stg_Helper_Template;

global $post;

$user = get_user_by('id', stgh_get_current_user_id());
$disabled = false; //is_user_logged_in();

if (isset($_REQUEST['stgh_message']) && $_REQUEST['stgh_message']) {
    echo '<div id="message">' . $_REQUEST['stgh_message'] . '</div>';
}
if (isset($_REQUEST['stgh_message_success']) && $_REQUEST['stgh_message_success']) {
    echo '<div id="message">' . $_REQUEST['stgh_message_success'] . '</div>';
} else {

    ?>
    <div id="stg-ticket-form-block">
        <form enctype="multipart/form-data" id="stg-ticket-form"
              action="<?php echo(isset($post->ID) ? get_permalink($post->ID) : ''); ?>" method="post" role="form"
              class="stg-form">

            <input type="hidden" name="stg_saveTicket" value="1">

            <?php //if(!is_user_logged_in()): ?>
            <label for="stg_ticket_name"><?php _e('Your name', STG_HELPDESK_TEXT_DOMAIN_NAME); ?><span
                    class="red">*</span></label>
            <input required="required" type="text" name="stg_ticket_name" class="stgh_width100pro"
                <?php if ($disabled) echo 'disabled' ?>
                   value="<?php echo !empty($user->user_nicename) ? $user->user_nicename : ''; ?>">

            <?php
                do_action('stg_free_form_template_include');
            ?>

            <label for="stg_ticket_email"><?php _e('Email', STG_HELPDESK_TEXT_DOMAIN_NAME); ?><span class="red">*</span></label>
            <input required="required"  type="text" name="stg_ticket_email" class="stgh_width100pro"
                <?php if ($disabled) echo 'disabled' ?>
                   value="<?php echo !empty($user->user_email) ? $user->user_email : '' ?>">
            <?php //endif; ?>

            <?php  ?>

            <label for="stg_ticket_subject"><?php _e('Subject', STG_HELPDESK_TEXT_DOMAIN_NAME); ?><span
                    class="red">*</span></label>
            <input required="required"  type="text" name="stg_ticket_subject" value="" class="stgh_width100pro">

            <label for="stg_ticket_message"><?php _e('Description', STG_HELPDESK_TEXT_DOMAIN_NAME); ?><span class="red">*</span></label>
            <textarea required="required"  name="stg_ticket_message" class="stgh_width100pro"></textarea>
            <p></p>

            <?php Stg_Helper_Template::getTemplate('stg-upload-file-field'); ?>

            <button value="" name="submit" type="submit"><?php _e('Submit', STG_HELPDESK_TEXT_DOMAIN_NAME) ?></button>
        </form>
    </div>
<?php } ?>