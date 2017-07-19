<?php

namespace StgHelpdesk\Helpers;

/**
 * Class Stg_Helper_Plugin
 * @package StgHelpdesk\Helpers
 */
class Stg_Helper_Plugin
{

    /**
     * Initialize the plugin
     */
    private function __construct()
    {

    }

    /**
     * Get plugin option.
     *
     * @param  string $option Option to look for
     * @param  bool|string $default Value to return if the requested option doesn't exist
     *
     * @return mixed           Value for the requested option
     * @since  1.0.0
     */
    public static function stg_get_option($option, $default = false)
    {

        $options = maybe_unserialize(get_option('stg_options', array()));

        /* Return option value if exists */
        $value = isset($options[$option]) ? $options[$option] : $default;

        return apply_filters('stg_option_' . $option, $value);
    }

}

