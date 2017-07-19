<?php
namespace StgHelpdesk\Helpers;

use StgHelpdesk\Ticket\Stg_Helpdesk_Ticket;

class Stg_Helper_Template
{

    /**
     * Get template
     * @param $name
     * @param array $args
     * @param bool $echo
     * @return bool|string
     */
    public static function getTemplate($name, $args = array(), $echo = true)
    {
        $filename = $name . '.php';
        $template = STG_HELPDESK_PUBLIC . "template/" . $filename;

        if (!file_exists($template)) {
            return false;
        }

        $template = apply_filters('stg_get_template', $template, $name, $args);

        do_action('stg_get_before_template', $name, $template, $args);

        $content = self::loadTemplate($template, $args);

        do_action('stg_get_after_template', $name, $template, $args);

        if ($echo) {
            echo $content;
        }
        return $content;
    }

    /**
     * Load content template
     * @param $template
     * @param $args
     * @return string
     * @throws Exception
     */
    public static function loadTemplate($template, $args)
    {
        extract($args);

        ob_start();
        try {
            include $template;
            return ob_get_clean();
        } catch (Exception $e) {
            ob_end_clean();
            return false;
        }
    }


    /**
     * future
     */
    public static function getTheme()
    {

    }

    /**
     * Get tickets list columns.
     *
     * Retrieve the columns to display on the list of tickets
     * in the client area. The columns include the 3 basic ones
     * (status, title and date), and also the custom fields that are
     * set to show on front-end (and that are not core CF).
     *
     * @since  3.0.0
     * @return array The list of columns with their title and callback
     */
    public static function getTicketsListColumns()
    {
        return array(
            'status' => array('title' => 'Status', 'callback' => 'status'),
            'title' => array('title' => 'Title', 'callback' => 'title'),
            'date' => array('title' => 'Date', 'callback' => 'date'),
        );
    }

    /**
     * Get tickets list columns content.
     *
     * Based on the columns displayed in the front-end tickets list,
     * this function will display the column content by using its callback.
     * The callback can be a "standard" case like the title, or a custom function
     * as used by the custom fields mostly.
     *
     * @param  string $column_id ID of the current column
     * @param  array $column Columns data
     * @return void
     */
    public static function getTicketsListColumnContent($column_id, $column)
    {

        $callback = $column['callback'];

        switch ($callback) {

            case 'id':
                echo '#' . get_the_ID();
                break;

            case 'status':
                $statusArray = stgh_get_statuses();
                echo $statusArray[get_post_status(get_the_ID())];
                break;

            case 'title':
                // If the replies are displayed from the oldest to the newest we want to link directly to the latest reply in case there are multiple reply pages
                /*if ('ASC' === Stg_Helper_Plugin::stg_get_option('replies_order', 'ASC')) {
                    $last_reply = Stg_Helpdesk_Ticket::getTicketReplies(get_the_ID(), array('read', 'unread'),
                        array('posts_per_page' => 1, 'order' => 'DESC'));
                    $link = !empty($last_reply) ? Stg_Helpdesk_Ticket::getReplyLink($last_reply[0]->ID) : get_permalink(get_the_ID());
                } else {*/
                $link = get_permalink(get_the_ID());
                //}
                ?><a href="<?php echo $link; ?>"><?php the_title(); ?></a><?php

                break;

            case 'date':
                $offset = self::getOffsetHtml5();
                ?>
                <time
                datetime="<?php echo get_the_date('Y-m-d\TH:i:s') . $offset ?>"><?php echo get_the_date(get_option('date_format')) . ' ' . get_the_date(get_option('time_format')); ?></time><?php
                break;

            case 'taxonomy':

                $terms = get_the_terms(get_the_ID(), $column_id);
                $list = array();

                if (empty($terms)) {
                    continue;
                }

                foreach ($terms as $term) {
                    array_push($list, $term->name);
                }

                echo implode(', ', $list);

                break;

            default:

                if (function_exists($callback)) {
                    call_user_func($callback, $column_id, get_the_ID());
                }

                break;
        }
    }

    /**
     * Get HTML5 offset.
     *
     * Get the time offset based on the WordPress settings
     * and convert it into a standard HTML5 format.
     *
     * @since  3.0.0
     * @return string HTML5 formatted time offset
     */
    public static function getOffsetHtml5()
    {

        $offset = get_option('gmt_offset');

        /* Transform the offset in a W3C compliant format for datetime */
        $offset = explode('.', $offset);
        $hours = $offset[0];
        $minutes = isset($offset[1]) ? $offset[1] : '00';
        $sign = ('-' === substr($hours, 0, 1)) ? '-' : '+';

        /* Remove the sign from the hours */
        if ('-' === substr($hours, 0, 1)) {
            $hours = substr($hours, 1);
        }

        if (5 == $minutes) {
            $minutes = '30';
        }

        if (1 === strlen($hours)) {
            $hours = "0$hours";
        }

        $offset = "$sign$hours:$minutes";

        return $offset;

    }

}