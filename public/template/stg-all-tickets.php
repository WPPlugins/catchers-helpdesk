<?php
use StgHelpdesk\Helpers;
use StgHelpdesk\Ticket;

?>

<div id="stg-all-tickets-block">
    <h2><?php _e('Your tickets', STG_HELPDESK_TEXT_DOMAIN_NAME); ?></h2>

    <?php
    $user_tickets = Ticket\Stg_Helpdesk_Ticket::userTickets();

    if ($user_tickets->have_posts()):
        $columns = Helpers\Stg_Helper_Template::getTicketsListColumns();
        ?>
        <div class="stgh stgh-ticket-list">
            <table id="stgh_ticketlist" class="stgh-table stgh-table-hover">
                <thead>
                <tr>
                    <?php foreach ($columns as $column_id => $column) {
                        echo "<th id='stg-ticket-$column_id'>" . $column['title'] . "</th>";
                    } ?>
                </tr>
                </thead>
                <tbody>
                <?php
                while ($user_tickets->have_posts()):

                    $user_tickets->the_post();

                    echo '<tr';
                    echo ' class=\'' . get_post_status(get_the_ID()) . '_color_row\'';
                    echo '>';

                    foreach ($columns as $column_id => $column) {

                        echo '<td';

                        if ('date' === $column_id) {
                            echo ' data-order="' . strtotime(get_the_time()) . '"';
                        }
                        if ('status' === $column_id) {
                            echo ' class="' . get_post_status(get_the_ID()) . '_color"';
                        }
                        echo '>';

                        /* Display the content for this column */
                        Helpers\Stg_Helper_Template::getTicketsListColumnContent($column_id, $column);

                        echo '</td>';

                    }

                    echo '</tr>';

                endwhile;

                wp_reset_query(); ?>
                </tbody>
            </table>
        </div>
        <?php
    else:
        echo getNotificationMarkup('info', __('Not found', STG_HELPDESK_TEXT_DOMAIN_NAME)); ?>
    <?php endif;

    $url = stgh_get_submit_page_url();
    if (!empty($url)) :
        ?>
        <a href="<?php echo esc_url($url); ?>"><?php _e('Open new ticket', STG_HELPDESK_TEXT_DOMAIN_NAME); ?></a>
    <?php endif; ?>

</div>