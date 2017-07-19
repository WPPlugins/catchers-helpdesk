<?php
stgh_is_called_directly();

use StgHelpdesk\Ticket\Stg_Helpdesk_Ticket;

global $post;

$dateTicket = human_time_diff(get_the_time('U', $post->ID), current_time('timestamp'));
$sourceTicket = get_post_meta($post->ID, '_stgh_type_source', true);
$sourceTicket = preg_replace('/(http[s]{0,1}\:\/\/\S{4,})\s{0,}/ims', '<a href="$1" target="_blank">$1</a> ', $sourceTicket);

$user_meta = Stg_Helpdesk_Ticket::getTicketUserMeta($post->ID);
$name = stgh_crm_get_user_full_name($post->post_author);
if (!$name)
    $name = $user_meta['name'];
$email = $user_meta['email'];

$agentTicket = get_post_meta( $post->ID, '_stgh_ticket_author_agent', true);
if($agentTicket == 1){
    $sourceTicket = $sourceTicket == "" ? __('agent', STG_HELPDESK_TEXT_DOMAIN_NAME):$sourceTicket;
}



?>

<div id="stgh-added-info-block">
    <strong><?php echo __('Added:', STG_HELPDESK_TEXT_DOMAIN_NAME); ?></strong>
    <span><?php printf(__('about %s ago by', STG_HELPDESK_TEXT_DOMAIN_NAME), $dateTicket); ?></span>
    <strong><?= $name ?></strong>
    <span class="stgh-ticket-source">
        <a id="added-info-mail" href="#">&lt;<span><?php echo $email; ?></span>&gt;</a>
    </span>
    <span><?php printf(__('via %s', STG_HELPDESK_TEXT_DOMAIN_NAME), $sourceTicket); ?></span>
</div>