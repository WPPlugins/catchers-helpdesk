<?php
stgh_is_called_directly();

use StgHelpdesk\Helpers\Stg_Helper_UploadFiles;
use StgHelpdesk\Ticket\Stg_Helpdesk_Ticket;

global $post;
$comments = stgh_ticket_get_comments(array('extra_params' => $_GET));

// ticket
$userAvatarTicket = get_avatar($post->post_author, '50', get_option('avatar_default'));
$dateTicket = human_time_diff(get_the_time('U', $post->ID), current_time('timestamp'));

// author
$user_meta = Stg_Helpdesk_Ticket::getTicketUserMeta($post->ID);
$user_email = $user_meta['email'];
$ticket_fromName = stgh_crm_get_user_full_name($post->post_author);
if (!$ticket_fromName)
    $ticket_fromName = $user_meta['name'];
?>
<a name="stgh-comment-anchor"></a>
<div class="stgh-comments-block">
    <?php
    if (!empty($comments)) :
        foreach ($comments as $comment) :
            $userAvatar = get_avatar($comment->user_id, '50', get_option('avatar_default'));
            $date = human_time_diff(get_the_time('U', $comment->ID), current_time('timestamp'));
            $postType = $comment->post_type;
            $colorType = get_post_meta($comment->ID, '_stgh_type_color', true);
            $userInfo = get_userdata($comment->post_author);

            $contact = get_post_meta($post->ID, '_stgh_contact', true);
            $user_id = $contact ? $contact : $post->post_author;
            $user = get_user_by('id', $user_id);
            $to = get_post_meta($comment->ID, '_stgh_reply_to', true);

            //Change logic
            if($to && $to != $user->user_email) {
                $colorType = "agent-to-other-person";
            }else{
                if($colorType == "agent-to-other-person")
                    $colorType = "agent";
            }

                        ?>
            <div class="<?php echo str_replace('_', '-', $postType);            ?>">
                <div
                    class="stgh-comment-block <?php echo !empty($colorType) ? 'stgh-reply-' . $colorType : '' ?>  stgh-<?php echo str_replace('_', '-', $comment->post_status); ?>"
                    id="stgh-post-<?php echo $comment->ID; ?>"
                >
                    <?php
                    if ('trash' != $comment->post_status) {
                        require(STG_HELPDESK_PATH . 'Admin/metaboxes/comments-published.php');
                    }
                    ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
    <?php
    $agentTicket = get_post_meta( $post->ID, '_stgh_ticket_author_agent', true);
    if($agentTicket == 1){
        $filterType = 'agent';
    }else{
        $filterType = 'user';
    }

    ?>
    <div class="stgh-comment-block filter-type-all filter-type-<?=$filterType;?>  filter-type-replies
        <?php echo str_replace('_', '-', $post->post_type); ?>">
        <div class="stgh-col-left">
            <?php echo $userAvatarTicket; ?>
        </div>
        <div class="stgh-col-right">
            <div class="stgh-comment-meta">
                <div class="stgh-comment-user">
                    <strong class="stgh-comment-profilename"><?= $ticket_fromName; ?></strong>

                    <span class="stgh-ticket-source">
                        <?php echo htmlspecialchars('<' . $user_email . '>'); ?>
                    </span>

                    <span class="stgh-comment-human-date">
                        <?php echo date(get_option('date_format'), strtotime($post->post_date)); ?>|
                    </span>
                    <?php printf(__('%s ago', STG_HELPDESK_TEXT_DOMAIN_NAME), $dateTicket); ?>
                </div>
            </div>

            <?php
            // filter content
            $content = apply_filters('the_content', $post->post_content);

            echo '<div class="stgh-ticket-content" id="stgh-comment-' . $post->ID . '">';

            do_action('stgh_admin_before_content', $post->ID);

            echo $content ? wp_kses($content, wp_kses_allowed_html('post')) : '<p>&nbsp;</p>';

            do_action('stgh_admin_after_content', $post->ID);

            echo Stg_Helper_UploadFiles::getAttachmentsBlock($post->ID);

            echo '</div>';
            ?>

            <div class="stgh-ticket-controls">
                <?php if (stgh_ticket_is_opened()): ?>
                    <?php
                    $ticketId = filter_input(INPUT_GET, 'post', FILTER_SANITIZE_NUMBER_INT);
                    // ticket comment controls
                    $controls = apply_filters('stgh_ticket_actions', array(), $ticketId);
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

        </div>
    </div>
</div>