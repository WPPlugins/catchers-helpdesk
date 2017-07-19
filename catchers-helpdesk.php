<?php
/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://mycatchers.com/
 * @since             1.0.0
 * @package           StudioTG Helpdesk
 *
 * @wordpress-plugin
 * Plugin Name:       Catchers Helpdesk and Ticket system for Support
 * Plugin URI:        https://mycatchers.com/
 * Description:       Helpdesk and ticket system plugin for supporting your clients right from admin area. Your customers can reach you via email or by using the contact form
 * Version:           2.3.1
 * Author:            mycatchers
 * Author URI:        https://mycatchers.com/
 * License:           license here
 * License URI:       license url
 * Text Domain:       catchers-helpdesk
 * Domain Path:       /languages
 */
// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}
/**
 * Bootstrap file
 */
$loader = require_once __DIR__ . '/vendor/autoload.php';
foreach ($loader->getClassMap() as $file) {
    require_once $file;
}

/**
 * Shortcodes page
 */
require_once(STG_HELPDESK_PUBLIC . 'shortcodes/stg-shortcode-tickets.php');
require_once(STG_HELPDESK_PUBLIC . 'shortcodes/stg-shortcode-form.php');

define('STG_HELPDESK_ROOT_PLUGIN_FILENAME_AND_PATH', __FILE__);
define('STG_PLUGIN_BASENAME', plugin_basename(__FILE__));

run_stg_helpdesk();