<div id="stgh-ticket-message" class="stgh-ticket-content">
    <?php

    do_action('stgh_backend_ticket_content_before', $post);

    echo apply_filters('the_content', $post->post_content);

    do_action('stgh_backend_ticket_content_after', $post);
    ?>
</div>