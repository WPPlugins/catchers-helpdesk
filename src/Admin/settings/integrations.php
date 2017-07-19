<?php

use StgHelpdesk\Core\Stg_Helpdesk_Activator;

add_filter('stgh_plugin_settings', 'stgh_core_integrations', 100, 1);



/**
 * @param $def
 * @return array
 */
function stgh_core_integrations($def)
{
        return $def;
    
    }
