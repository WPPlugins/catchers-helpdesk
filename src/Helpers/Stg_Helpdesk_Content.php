<?php

namespace StgHelpdesk\Helpers;

class Stg_Helpdesk_Content
{

    /**
     * @param string $content
     * @return string
     */
    public static function makeLinksClickable($content)
    {
        if (is_admin() && isset($_GET['post']) && STG_HELPDESK_POST_TYPE === get_post_type(filter_input(INPUT_GET, 'post',
                FILTER_SANITIZE_NUMBER_INT))
        ) {
            return make_clickable($content);
        }

        global $post;

        if (!isset($post) || empty($post)) {
            return $content;
        }

        if (in_array(get_post_type($post->ID), array(STG_HELPDESK_POST_TYPE))) {
            return make_clickable($content);
        }

        return $content;
    }
}