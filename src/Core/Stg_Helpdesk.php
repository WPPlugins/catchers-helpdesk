<?php

namespace StgHelpdesk\Core;

use StgHelpdesk\Admin\Stg_Helpdesk_Admin;
use StgHelpdesk\Core\PostType\Stg_Helpdesk_Post_Type;
use StgHelpdesk\Helpers\Stg_Helpdesk_Content;
use StgHelpdesk\Ticket\Stg_Helpdesk_CommentsAjax;

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @package Stg_Helpdesk
 * @subpackage Stg_Helpdesk/includes
 */
class Stg_Helpdesk
{

    /**
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     *
     * @access   protected
     * @var      Stg_Helpdesk_Loader $loader Maintains and registers all hooks for the plugin.
     */
    protected $loader;

    /**
     * The unique identifier of this plugin.
     *
     * @var      string $name The string used to uniquely identify this plugin.
     */
    protected $name;

    /**
     * The current version of the plugin.
     *
     * @var string $version The current version of the plugin.
     */
    protected $version;

    /**
     * Define the core functionality of the plugin.
     *
     * Set the plugin name and the plugin version that can be used throughout the plugin.
     * Load the dependencies, define the locale, and set the hooks for the admin area and
     * the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function __construct()
    {
        $this->name = STG_HELPDESK_NAME;
        $this->version = STG_HELPDESK_VERSION;
        $this->loader = new Stg_Helpdesk_Loader();
        $this->setLocale();
        $this->defineActivationHook();
        $this->defineDeactivationHook();
        $this->defineAdminHooks();
        $this->definePublicHooks();
        $this->definePostType();
        $this->defineHelperHooks();
        $this->defineCommentsAjax();
    }

    protected function defineCommentsAjax()
    {
        add_action('plugins_loaded', function () {
            return Stg_Helpdesk_CommentsAjax::instance();
        }, 11, 0);
    }

    protected function defineHelperHooks()
    {
        add_filter('the_content', function ($content) {
            return Stg_Helpdesk_Content::makeLinksClickable($content);
        }, 10, 1);
    }

    /**
     * Define the locale for this plugin for internationalization.
     *
     * Uses the Stg_Helpdesk_i18n class in order to set the domain and to register the hook
     * with WordPress.
     */
    private function setLocale()
    {
        $plugin_i18n = new Stg_Helpdesk_i18n();
        $plugin_i18n->set_domain($this->getName());

        $this->loader->addAction('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
    }

    /**
     * Activation hook
     */
    private function defineActivationHook()
    {
        register_activation_hook(STG_HELPDESK_NAME.'/'.STG_HELPDESK_NAME.'.php', function () {
            Stg_Helpdesk_Activator::activate();
        });
    }

    /**
     * Deactivation hook
     */
    private function defineDeactivationHook()
    {
        register_deactivation_hook(STG_HELPDESK_NAME.'/'.STG_HELPDESK_NAME.'.php', function () {
            Stg_Helpdesk_Activator::deactivate();
        });
    }

    /**
     * Register all of the hooks related to the admin area functionality
     * of the plugin.
     */
    private function defineAdminHooks()
    {
        if (is_admin()) {
            add_action('plugins_loaded', function () {
                return new Stg_Helpdesk_Admin();
            });
        }
    }

    /**
     * Register all of the hooks related to the public-facing functionality
     * of the plugin.
     */
    private function definePublicHooks()
    {
        //
    }

    private function definePostType()
    {
        add_action('plugins_loaded', function () {
            Stg_Helpdesk_Post_Type::instance();
        }, 1, 0);
    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     *
     */
    public function run()
    {
        $this->loader->run();
    }

    /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.
     *
     * @return    string    The name of the plugin.
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * The reference to the class that orchestrates the hooks with the plugin.
     *
     * @return Stg_Helpdesk_Loader  Orchestrates the hooks of the plugin.
     */
    public function getLoader()
    {
        return $this->loader;
    }

    /**
     * Retrieve the version number of the plugin.
     *
     * @return string The version number of the plugin.
     */
    public function getVersion()
    {
        return $this->version;
    }

}
