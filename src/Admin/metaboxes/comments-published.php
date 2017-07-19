<?php
use StgHelpdesk\Helpers\Stg_Helper_UploadFiles;
use StgHelpdesk\Ticket\Stg_Helpdesk_TicketComments;
use StgHelpdesk\Ticket\Stg_Helpdesk_Ticket;

global $post;
stgh_is_called_directly();

$fromName = stgh_crm_get_user_full_name($userInfo->ID);
if (!$fromName)
    $fromName = $userInfo->display_name;
$userEmail = $userInfo->user_email;

$ticket_author_meta = Stg_Helpdesk_Ticket::getTicketUserMeta($comment->post_parent);
$contact = get_post_meta($post->ID, '_stgh_contact', true);
$user_id = $contact ? $contact : $post->post_author;
$user = get_user_by('id', $user_id);
?>

<div class="stgh-col-left">
    <?php echo $userAvatar; ?>
</div>
<div class="stgh-col-right">
    <div class="stgh-comment-meta">
        <div class="stgh-comment-user">
            <strong class="stgh-comment-profilename">
                <?php
                echo $fromName;
                ?>
            </strong>

            <span class="stgh-comment-source">
                <?php
                echo(!empty($colorType) && $colorType != 'user' ? "" : htmlspecialchars('<' . $userEmail . '>'));
                ?>
            </span>

            <?php
                $ticket_read = get_post_meta( $comment->ID, '_stgh_post_read', true);
            ?>

            <span class="stgh-comment-human-date <?= $ticket_read == 1? "stgh-ticket-read":""; ?>">
                <?php echo date(get_option('date_format'), strtotime($comment->post_date)); ?>|
            </span>
            <?php printf(__('%s ago', STG_HELPDESK_TEXT_DOMAIN_NAME), $date); ?>
        </div>

        <?php
        $to = get_post_meta($comment->ID, '_stgh_reply_to', true);
        //if ($to && trim($to) != trim($ticket_author_meta['email'])) {
        if ($to && trim($to) != trim($user->user_email)) {
        ?>
            <div>To:<span class="stgh_comment_to"><?php echo $to; ?></span></div>
        <?php } ?>

        <?php
        $cc = get_post_meta($comment->ID, '_stgh_reply_cc', true);
        if ($cc) { ?>
            <div>Cc:<span class="stgh_comment_cc"><?php echo $cc; ?></span></div>
        <?php } ?>

        <?php
        $bcc = get_post_meta($comment->ID, '_stgh_reply_bcc', true);
        if ($bcc) { ?>
            <div>Bcc:<span class="stgh_comment_bcc"><?php echo $bcc; ?></span></div>
        <?php } ?>
    </div>

    <?php
    // filter content
    $content = apply_filters('the_content', $comment->post_content);

    echo '<div class="stgh-comment-content" id="stgh-comment-' . $comment->ID . '">';

    do_action('stgh_admin_before_content', $comment->ID);

    echo wp_kses($content, wp_kses_allowed_html('post'));

    do_action('stgh_admin_after_content', $comment->ID);

    echo Stg_Helper_UploadFiles::getAttachmentsBlock($comment->ID);

    echo '</div>';
    ?>

    <!--  -->

    <div class="stgh-ticket-controls">
        <?php if (stgh_ticket_is_opened()): ?>
            <?php
            $ticketId = filter_input(INPUT_GET, 'post', FILTER_SANITIZE_NUMBER_INT);
            // ticket comment controls
            $controls = apply_filters('stgh_ticket_comment_actions', array(), $ticketId, $comment);

            if (!empty($controls)) {
                $output = array();
                foreach ($controls as $control_id => $control) {
                    array_push($output, $control);
                }
                echo implode(' | ', $output);
            }
            ?>
        <?php endif; ?>
    </div>

    <?php if ('trash' !== $comment->post_status): ?>
        <div class="stgh-editor stgh_display_none stgh-editwrap-<?php echo $comment->ID; ?>">
            <div>
                <div class="stgh-editor stgh_marginbottom_1em"></div>
                <input id="stgh-edited-comment-<?php echo $comment->ID; ?>" type="hidden" name="edited_reply">
                <input type="submit" id="stgh-edit-submit-<?php echo $comment->ID; ?>"
                       class="button-primary stgh-btn-save-edit"
                       value="<?php _e('Save changes', STG_HELPDESK_TEXT_DOMAIN_NAME); ?>">
                <input type="button" class="stgh-editcancel button-secondary"
                       data-origin="#stgh-comment-<?php echo $comment->ID; ?>"
                       data-replyid="<?php echo $comment->ID; ?>"
                       data-reply="stgh-editwrap-<?php echo $comment->ID; ?>"
                       data-wysiwygid="stgh-editcomment-<?php echo $comment->ID; ?>"
                       value="<?php _e('Cancel', STG_HELPDESK_TEXT_DOMAIN_NAME); ?>">
            </div>
        </div>
    <?php endif; ?>
</div>
