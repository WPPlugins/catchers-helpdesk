<?php

namespace StgHelpdesk\Admin;

use TitanFramework;

class Stg_Helpdesk_Settings_Page
{
    protected static $instance = null;

    public static function instance()
    {
        if (is_null(static::$instance)) {
            static::$instance = new static;
        }

        return static::$instance;
    }

    private function __construct()
    {
        if(stgh_is_plugin_page()) {
            add_action('after_setup_theme', array($this, 'generate'), 12);
        }
        else{
            add_action('admin_menu', array($this, 'add_settings'),8);
        }
    }

    public function add_settings()
    {
        add_submenu_page('edit.php?post_type=' . STG_HELPDESK_POST_TYPE,
            __('Helpdesk settings', STG_HELPDESK_TEXT_DOMAIN_NAME),__('Settings', STG_HELPDESK_TEXT_DOMAIN_NAME), 'settings_tickets',
            'edit.php?post_type='. STG_HELPDESK_POST_TYPE.'&page=settings');

    }

    /**
     * Check Titan activated
     *
     * @return bool
     */
    protected function isTitanActivated()
    {
        // Check if the framework plugin is activated
        $activated = false;
        $activePlugins = get_option('active_plugins');
        if (is_array($activePlugins)) {
            foreach ($activePlugins as $plugin) {
                if (is_string($plugin) && false !== stripos($plugin, '/titan-framework.php')) {
                    $activated = true;
                    break;
                }
            }
        }

        return $activated || class_exists('TitanFramework');
    }

    /**
     * Generate setting page
     */
    public function generate()
    {
        if (is_activation()) {
            return;
        }

        if (!$this->isTitanActivated()) {
            require_once(STG_HELPDESK_ROOT . 'vendor/gambitph/titan-framework/titan-framework.php');
        }

        $titan = TitanFramework::getInstance('stgh');

        $this->addTabsToContainer($titan->createContainer(array(
                'name' => __('Settings', STG_HELPDESK_TEXT_DOMAIN_NAME),
                'title' => __('Helpdesk settings', STG_HELPDESK_TEXT_DOMAIN_NAME),
                'id' => 'settings',
                'parent' => 'edit.php?post_type=' . STG_HELPDESK_POST_TYPE,
                'capability' => 'settings_tickets',
                'type' => 'admin-page'
            )
        ));
    }


    /**
     * Add the settings page in the main container settings page
     *
     * @param $container
     */
    protected function addTabsToContainer($container)
    {
        foreach ($this->getSettingsPages() as $tab => $content) {
            /* Add a new tab */
            $tab = $container->createTab(array(
                    'name' => $content['name'],
                    'title' => isset($content['title']) ? $content['title'] : $content['name'],
                    'id' => $tab
                )
            );

            /* Add all options to current tab */
            foreach (array_merge($content['options'], (isset($content['stgh-no-save-button']) ? array() : array(array('type' => 'save')))) as $option) {
                $tab->createOption($option);
            }
        }
    }

    /**
     * Get settings pages
     *
     * @return mixed|void
     */
    protected function getSettingsPages()
    {
        return apply_filters('stgh_plugin_settings', array());
    }
}