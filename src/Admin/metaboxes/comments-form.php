<?php

use StgHelpdesk\Ticket\Stg_Helpdesk_TicketComments;
use StgHelpdesk\Helpers\Stg_Helper_Template;
use StgHelpdesk\Ticket\Stg_Helpdesk_Ticket;

stgh_is_called_directly();

global $post;

$globalStatus = 'draft';
$ticketStatus = '' == stgh_ticket_get_status() ? 'new' : stgh_ticket_get_status();
$statuses = stgh_get_statuses();
$assignedTo = stgh_ticket_assigned_to($post->ID);

$userIdContact = get_post_meta($post->ID, '_stgh_contact', true);

/*if($post->post_type == STG_HELPDESK_POST_TYPE){
    $user_meta = Stg_Helpdesk_Ticket::getTicketUserMeta($post->ID);
} else {
    $user_meta = Stg_Helpdesk_TicketComments::getCommentUserMeta($post->ID);
}*/

$user_email = get_userdata($userIdContact) ? get_userdata($userIdContact)->user_email : '';

?>
<?php if (stgh_current_user_can('reply_ticket')) : ?>
<?php if (stgh_ticket_is_opened()) : ?>
    <!--  -->
    <div id="stgh-add-note-advert">
        <label><?php _e('This function is available in a PRO version', STG_HELPDESK_TEXT_DOMAIN_NAME); ?></label>
    </div>
    <!--  -->
<ul class="stgh-ntabs">
    <li class="stgh-ntab stgh-ntab-active" data-show="stgh-form-agent"><?php _e('Reply', STG_HELPDESK_TEXT_DOMAIN_NAME); ?>
    <li class="stgh-ntab stgh-private-note" data-show="stgh-form-private"><?php _e('Private note', STG_HELPDESK_TEXT_DOMAIN_NAME); ?>
</ul>

<?php
$agentTicket = get_post_meta( $post->ID, '_stgh_ticket_author_agent', true);
if($agentTicket == 1):
?>
<input type="hidden" value="1" id="stgh_agent_ticket" name="stgh_agent_ticket"/>
<?php endif; ?>

<div class="postbox " id="stgh-comments-form-in">
<div class="inside">  
        <div>

           <div id="stgh-to-cc-bcc">
                <p class="stgh-to-cc-bcc">
                    <label class="stgh-label-to-cc-bcc"><strong><?php _e('Reply To:', STG_HELPDESK_TEXT_DOMAIN_NAME); ?></strong></label>
                    <label class="stgh-label-to-cc-bcc-input"><?php echo $user_email; ?></label>
                    
                    <input type="text" value="<?php echo $user_email; ?>" id="stgh_reply_to" name="stgh_reply_to" class="reply-to-field">
                    
                    <!--  -->
                </p>



<p class="stgh-cc-bcc">
<label><strong><?php _e('Cc:', STG_HELPDESK_TEXT_DOMAIN_NAME); ?></strong></label>
<input type="text" value=""  name="stgh-cc" id="stgh-cc">
</p>

<p class="stgh-cc-bcc">
<label><strong><?php _e('Bcc:', STG_HELPDESK_TEXT_DOMAIN_NAME); ?></strong></label>
<input type="text" value=""  name="stgh-bcc" id="stgh-bcc">
</p>

</div>
 
            
          <div id="stgh-form-agent">
             <?php

                wp_editor(
                    '',
                    'stgh_comment',
                    array(
                        'media_buttons' => false,
                        'teeny' => true,
                        'quicktags' => true,
                        'wpautop' => false,
                        'tinymce' => array(
                            'height' => 300
                        )
                    )
                );

                ?>
            </div>
            <?php
            do_action('stgh_admin_block_after_wysiwyg');

            wp_nonce_field(Stg_Helpdesk_TicketComments::$nonceAction, Stg_Helpdesk_TicketComments::$nonceName, false, true);
            ?>
            <div id="stgh-form-private">
          
                    <?php       
                    wp_editor(
                        '',
                        'stgh_comment_private',
                        array(
                            'media_buttons' => false,
                            'teeny' => true,
                            'wpautop' => false,
                            'quicktags' => true,
                            'tinymce' => array(
                                'height' => 300
                            )
                        )
                    );
                    ?>
          </div>
</div>

        <div id="stg_ticket_files_block">
            <?php Stg_Helper_Template::getTemplate('stg-upload-file-field')?>
	   </div>

    <div class="major-publishing-actions">
        <span class="stgh-ticket-property-block">
            <label for="post_status_override_main"><strong><?php _e('Status:', STG_HELPDESK_TEXT_DOMAIN_NAME); ?></strong></label>
            <select class="stgh-ticket-property" name="post_status_override_main" id="post_status_override_main">
                <?php foreach ($statuses as $status => $label): ?>
                    <option value="<?php echo $status; ?>"><?php echo $label; ?></option>
                <?php endforeach; ?>
            </select>
        </span>
        <span class="stgh-ticket-property-block">
            <label for="stgh_assignee"><strong><?php _e('Assigned:', STG_HELPDESK_TEXT_DOMAIN_NAME); ?></strong></label>
            <?php
            if (stgh_current_user_can('assign_ticket')) {
                echo stgh_display_assign_to_select(array('class' => 'stgh-ticket-property'), ! is_null($assignedTo) ? $assignedTo->ID : 0, 'stgh_assignee_main');
            } else {
                echo !is_null($assignedTo) ? $assignedTo->data->display_name : '';
            }
            ?>
        </span>

        <div class="float-right">
            <button type="submit" name="stgh-do" id="stgh-do" class="button-primary" value="reply"><?php _e('Send reply', STG_HELPDESK_TEXT_DOMAIN_NAME); ?></button>
            <?php
            if (isset($comment)) {
                do_action('stgh_admin_after_comments_buttons', $comment->ID);
            }
            ?>
        </div>

    </div>
<?php else: ?>
    <div class="updated below-h2 stgh_margintop_2em">
        <h2><?php _e('Ticket is closed', STG_HELPDESK_TEXT_DOMAIN_NAME); ?></h2>

        <p>
            <?php _e('This ticket has been closed. If you want to write a new comment to this ticket, you need to re-open it first.',
                STG_HELPDESK_TEXT_DOMAIN_NAME) ?>
        </p>
    </div>
<?php endif; ?>

<?php else : ?>
    <p><?php _e('Sorry, you don\'t have sufficient permissions to add comments.', STG_HELPDESK_TEXT_DOMAIN_NAME); ?></p>
<?php endif; ?>