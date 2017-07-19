<?php
global $post;

?>
<div class="stgh-related-tickets" data-authorId="<?php echo $post->post_author ?>" data-postId="<?php echo $post->ID ?>"
     id="stgh-related-tickets">
    <div id="stgh-related-tickets-content"></div>

    <div class="stgh-tickets-loading-div" id="stgh-related-tickets-loader">
        <img class="stgh-tickets-loader" src="<?php echo STG_HELPDESK_URL ?>images/loader.gif">
    </div>
</div>