<?php

namespace StgHelpdesk\Widget;

use StgHelpdesk\Helpers\Stg_Helper_Template;

class Stg_Widget_Form extends \WP_Widget
{
    /**
     * Construct
     */
    public function __construct()
    {
        parent::__construct("Stg_Widget_Form", __('Widget form support', STG_HELPDESK_TEXT_DOMAIN_NAME),
            array("description" => __("A widget form support", STG_HELPDESK_TEXT_DOMAIN_NAME)));
    }

    /**
     * Widget form creation
     * @param array $instance
     */
    function form($instance)
    {
        $instance = wp_parse_args((array)$instance, array('title' => ''));
        $title = $instance['title'];
        ?>

        <p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?> <input class="widefat"
                                                                                                  id="<?php echo $this->get_field_id('title'); ?>"
                                                                                                  name="<?php echo $this->get_field_name('title'); ?>"
                                                                                                  type="text"
                                                                                                  value="<?php echo esc_attr($title); ?>"/></label>
        </p>

        <?php
    }

    /**
     * Widget update
     * @param array $new_instance
     * @param array $old_instance
     * @return array
     */
    function update($new_instance, $old_instance)
    {
        $instance = $old_instance;
        $new_instance = wp_parse_args((array)$new_instance, array('title' => ''));
        $instance['title'] = strip_tags($new_instance['title']);
        return $instance;
    }

    /**
     * Widget display
     * @param array $args
     * @param array $instance
     */
    function widget($args, $instance)
    {
        $title = apply_filters('widget_title', empty($instance['title']) ? '' : $instance['title'], $instance, $this->id_base);

        echo $args['before_widget'];
        if ($title) {
            echo $args['before_title'] . $title . $args['after_title'];
        }

        Stg_Helper_Template::getTemplate('stg-ticket-form');

        echo $args['after_widget'];
    }
}