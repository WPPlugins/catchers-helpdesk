<?php

use StgHelpdesk\Admin\Stg_Helpdesk_Admin;
use StgHelpdesk\Core\Stg_Helpdesk;
use StgHelpdesk\Helpers\Stg_Helper_UploadFiles;

if (!function_exists('run_stg_helpdesk')) {
    /**
     * Begins execution of the plugin.
     */
    function run_stg_helpdesk()
    {
        $plugin = new Stg_Helpdesk();
        $plugin->run();
    }
}

/*----------------------------------------------------------------------------*
 * Debug
 *----------------------------------------------------------------------------*/
if (!function_exists('d')) {
    function d()
    {
        echo '<pre>';
        foreach (func_get_args() as $arg) {
            var_dump($arg);
        }
        exit;
    }
}

if (!function_exists('stgh_request_is_not_ajax')) {
    function stgh_request_is_not_ajax()
    {
        return !defined('DOING_AJAX') || !DOING_AJAX;
    }
}

if (!function_exists('is_activation')) {
    function is_activation()
    {
        if (!empty($_GET['action']) && !empty($_GET['plugin'])) {
            if ($_GET['action'] == 'activate') {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('stgh_nonce_url')) {
    /**
     * Add a security nonce.
     *
     * The function adds a security nonce to URLs
     * with a trigger for plugin custom action.
     *
     * @param  string $url URL to nonce
     * @return string
     */
    function stgh_nonce_url($url)
    {
        return add_query_arg(array(Stg_Helpdesk_Admin::$nonceName => wp_create_nonce(Stg_Helpdesk_Admin::$customNonceAction)),
            $url);
    }
}

if (!function_exists('stgh_get_users')) {
    function stgh_get_users($args = array())
    {
        $defaults = array(
            'exclude' => array(),
            'cap' => '',
            'cap_exclude' => '',
        );

        $list = array();

        $args = wp_parse_args($args, $defaults);

        $hash = substr(md5(serialize($args)), 0, 10);

        $cache = get_transient("stgh_list_users_$hash");

        if (false !== $cache && count($cache) > 0) {
            $list = get_users(array('include' => (array)$cache));
        } else {
            $usersIds = array();

            foreach (get_users($args) as $user) {
                if ((!empty($args['cap']) && !user_can($user, $args['cap'])) ||
                    (!empty($args['cap_exclude']) && user_can($user,
                            $args['cap_exclude'])) ||
                    (in_array($user->ID, (array)$args['exclude']))
                ) {
                    continue;
                }

                array_push($list, $user);
                array_push($usersIds, $user->ID);
            }

            // Save hash for delete_transient
            if ($option = get_option('stgh_list_users_cache_hashes', false)) {
                if (!strstr($option, $hash)) {
                    update_option('stgh_list_users_cache_hashes', $option . " $hash");
                }
            } else {
                add_option('stgh_list_users_cache_hashes', $hash);
            }
            // cache users
            set_transient("stgh_list_users_$hash", $usersIds,
                apply_filters('stgh_list_users_cache_expiration', 60 * 60 * 24));
        }


        return apply_filters('stgh_get_users', $list);
    }
}
if (!function_exists('stgh_list_users')) {
    function stgh_list_users($cap = 'all', $withDefault = true)
    {
        $list = array();

        /* List all users */
        $all_users = stgh_get_users(array('cap' => $cap));

        if ($withDefault) {
            $list[0] = "&nbsp;";
        }

        foreach ($all_users as $user) {
            $list[$user->ID] = $user->data->display_name;
        }

        return apply_filters('stgh_users_list', $list);
    }
}

if (!function_exists('stgh_is_plugin_page')) {
    function stgh_is_plugin_page($slug = null)
    {
        global $post;

        $postType = apply_filters('stgh_plugin_post_types', array(STG_HELPDESK_POST_TYPE));
        $adminPages = apply_filters('stgh_plugin_admin_pages', array());

        // Check for plugin pages in the admin
        if (is_admin()) {

            // First of all let's check if there is a specific slug given
            if (!is_null($slug) && in_array($slug, $adminPages)) {
                return true;
            }

            // If the current post if of one of our post types
            if (isset($post) && isset($post->post_type) && in_array($post->post_type, $postType)) {
                return true;
            }

            // If the page we're in relates to one of our post types
            if (isset($_GET['post_type']) && in_array($_GET['post_type'], $postType)) {
                return true;
            }

            // If the page belongs to the plugin
            if (isset($_GET['page']) && in_array($_GET['page'], $adminPages)) {
                return true;
            }

            return false;

        }

        return false;
    }
}

if (!function_exists('stgh_add_redirect_to')) {
    function stgh_add_redirect_to($url)
    {
        $_SESSION['stgh_redirect'] = $url;
    }
}

if (!function_exists('stgh_redirect')) {
    function stgh_redirect($case, $location = null, $post_id = null)
    {
        if (is_null($location)) {
            return false;
        }

        $location = apply_filters("stgh_redirect_$case", $location, $post_id);
        $location = wp_sanitize_redirect($location);

        if (!headers_sent()) {
            wp_redirect($location, 302);
        } else {
            echo "<meta http-equiv='refresh' content='0; url=$location'>";
        }

        return true;

    }
}

if (!function_exists('stgh_is_our_post_type')) {
    function stgh_is_our_post_type($type = null)
    {
        if (is_null($type)) {
            $type = get_post_type();
        }

        return STG_HELPDESK_POST_TYPE == $type;
    }
}

if (!function_exists('stgh_get_option')) {
    function stgh_get_option($key, $default = false)
    {
        $option = get_option('stgh_options', array());
        $options = is_serialized($option) ? @unserialize($option) : $option;

        $value = isset($options[$key]) ? $options[$key] : $default;

        return apply_filters('stgh_option_' . $key, $value);
    }
}

if (!function_exists('stgh_update_option')) {
    function stgh_update_option($option, $value)
    {
        $options = maybe_unserialize(get_option('stgh_options', array()));
        if (!array_key_exists($option, $options)) {
            return false;
        }

        if ($value === $options[$option]) {
            return false;
        }

        $options[$option] = $value;
        return update_option('stgh_options', serialize($options));
    }
}

if (!function_exists('stgh_set_option')) {
    function stgh_set_option($option, $value)
    {
        $options = maybe_unserialize(get_option('stgh_options', array()));
        $options[$option] = $value;
        return update_option('stgh_options', serialize($options));
    }
}


if (!function_exists('stgh_debug_log')) {
    function stgh_debug_log($log)
    {
        $prefix = 'STGH [error]: ';

        if (true === WP_DEBUG) {
            if (is_array($log) || is_object($log)) {
                error_log($prefix . print_r($log, true));
            } else {
                error_log($prefix . $log);
            }
        }
    }
}
if (!function_exists('stgh_cron_add_schedules')) {
    function stgh_cron_add_schedules($array)
    {
        $array['1min'] = array(
            'interval' => 60,
            'display' => 'Once every one minutes',
        );
        $array['5min'] = array(
            'interval' => 5 * 60,
            'display' => 'Once every five minutes',
        );
        $array['15min'] = array(
            'interval' => 15 * 60,
            'display' => 'Once every fifteen minutes',
        );
        $array['30min'] = array(
            'interval' => 30 * 60,
            'display' => 'Once every thirty minutes',
        );

        return $array;
    }
}

if (!function_exists('stgh_404')) {
    function stgh_404()
    {
        status_header(404);
        nocache_headers();
        include(get_query_template('404'));
        die();
    }
}

if (!function_exists('stgh_register_user')) {
    function stgh_register_user($email, $name, $company = '')
    {
        if (empty($email) || empty($name)) {
            $_REQUEST['stgh_message'] = getNotificationMarkup('failure',
                __('Required field(s) is empty', STG_HELPDESK_TEXT_DOMAIN_NAME));
            return false;
        }

        if (filter_var($_POST['stg_ticket_email'], FILTER_VALIDATE_EMAIL) === false) {
            $_REQUEST['stgh_message'] = getNotificationMarkup('failure',
                __('Email is not a valid', STG_HELPDESK_TEXT_DOMAIN_NAME));

            return false;
        }

        $email = sanitize_text_field($email);
        $user = get_user_by('email', $email);

        if (!$user) {
            // create user
            remove_all_filters('registration_errors');

            remove_action('register_new_user','wp_send_new_user_notifications');

            $obj = stgh_get_current_version_object();
            $userId = $obj->register_new_user($email, $email);

            wp_update_user(array('ID' => $userId,'display_name' => $name));

            if ($userId instanceof \WP_Error) {
                $log = \StgHelpdesk\Helpers\Stg_Helper_Logger::getLogger();
                $log->log("Errors while user creating " . var_export($userId->get_error_messages(), true) . ' in file ' . __FILE__ . " in line " . __LINE__);
                return false;
            }

            // set roles
            $user = new WP_User($userId);
            $user->set_role('stgh_client');

            //set meta crm
            add_user_meta($userId, '_stgh_crm_company', sanitize_text_field($company));
        }

        return $user->ID;
    }
}

if (!function_exists('stgh_check_user_access')) {
    function stgh_check_user_access($post_id)
    {
        $uid = $key = NULL;

        if (isset($_GET['uid']) || isset($_COOKIE['uid'])) {
            $uid = !empty($_GET['uid']) ? (int)$_GET['uid'] : (int)$_COOKIE['uid'];
        }
        if (isset($_GET['key']) || isset($_COOKIE['key'])) {
            $key = !empty($_GET['key']) ? sanitize_text_field($_GET['key']) : $_COOKIE['key'];
        }

        if (!empty($uid) && !empty($key)) {
            $user = get_user_by('id', $uid);
            if (!empty($user) && md5($user->user_email . STG_HELPDESK_SALT_USER) === $key) {
                if (intval($user->ID) === intval(get_post_field('post_author', $post_id))) {
                    return true;
                }
                $postContactId = get_post_meta($post_id, "_stgh_contact", true);
                if($postContactId == $uid){
                    return true;
                }
            }
        }

        $user = stgh_get_current_user();
        if (!empty($user)
            && ((in_array('stgh_manager', (array)$user->roles) || in_array('administrator', (array)$user->roles))
                || intval($user->ID) === intval(get_post_field('post_author', $post_id)))
        ) {
            return true;
        }

        return false;
    }
}

if (!function_exists('stgh_get_current_user')) {
    function stgh_get_current_user()
    {
        $user_id = stgh_get_current_user_id();

        if (!$user_id) {
            return false;
        }

        return get_user_by('id', $user_id);
    }
}

if (!function_exists('stgh_get_current_user_id')) {
    function stgh_get_current_user_id()
    {
        $uid = $key = NULL;

        if (is_user_logged_in()) {
            return get_current_user_id();
        } else {
            if (isset($_GET['uid']) || isset($_COOKIE['uid'])) {
                $uid = !empty($_GET['uid']) ? (int)$_GET['uid'] : (int)$_COOKIE['uid'];
            }
            if (isset($_GET['key']) || isset($_COOKIE['key'])) {
                $key = !empty($_GET['key']) ? sanitize_text_field($_GET['key']) : $_COOKIE['key'];
            }

            if (empty($uid) || empty($key)) {
                return false;
            }

            $user = get_user_by('id', $uid);

            if (!$user) {
                return false;
            }

            if (md5($user->user_email . STG_HELPDESK_SALT_USER) === $key) {
                return $user->ID;
            }

            return false;
        }
    }
}

if (!function_exists('stgh_current_user_can')) {
    function stgh_current_user_can($capability)
    {
        $current_user = stgh_get_current_user();

        if (empty($current_user))
            return false;

        $args = array_slice(func_get_args(), 1);
        $args = array_merge(array($capability), $args);

        return call_user_func_array(array($current_user, 'has_cap'), $args);
    }
}

if (!function_exists('stgh_auth_cookie')) {
    function stgh_auth_cookie()
    {
        if (!empty($_GET['uid']) && !empty($_GET['key'])) {
            setcookie("uid", (int)$_GET['uid'], 0, '/');
            setcookie("key", $_GET['key'], 0, '/');
        }
    }
}

if (!function_exists('stgh_get_pages')) {
    function stgh_get_pages()
    {
        $list = array('' => __('None', STG_HELPDESK_TEXT_DOMAIN_NAME));
        $pages = new WP_Query(array(
            'post_type' => 'page',
            'post_status' => 'publish',
            'order' => 'DESC',
            'orderby' => 'page_title',
            'posts_per_page' => -1,
            'no_found_rows' => false,
            'cache_results' => false,
            'update_post_term_cache' => false,
            'update_post_meta_cache' => false,

        ));

        if (!empty($pages->posts)) {
            foreach ($pages->posts as $page) {
                $list[$page->ID] = apply_filters('the_title', $page->post_title);
            }

        }

        return $list;
    }
}

if (!function_exists('stgh_filter_upload_dir')) {
    /**
     * Filter upload filter, valid php 5.3
     * @param $upload
     * @return mixed
     */
    function stgh_filter_upload_dir($upload)
    {
        return Stg_Helper_UploadFiles::filterUploadDir($upload);
    }
}

if (!function_exists('stgh_get_submit_pages')) {
    function stgh_get_submit_pages()
    {
        $page = stgh_get_option('ticket_submit_page');

        $page = !is_array($page) ? (array)$page : $page;

        return array_filter($page);
    }
}

if (!function_exists('stgh_get_submit_page_url')) {
    function stgh_get_submit_page_url()
    {
        $submission = stgh_get_submit_pages();

        if (empty($submission)) {
            return '';
        }

        $url = get_permalink((int)$submission[0]);

        return wp_sanitize_redirect($url);
    }
}

if (!function_exists('stgh_parse_phpextension')) {
    function stgh_parse_phpextension()
    {
        ob_start();
        $re = new ReflectionExtension('imap');
        $re->info();
        $s = ob_get_contents();
        ob_end_clean();

        $s = strip_tags($s, '<tr><td>');
        preg_match_all('/<tr><td[^>]*>([^<]+)<\/td><td[^>]*>([^<]+)<\/td><\/tr>/', $s, $matchs, PREG_SET_ORDER);

        $result = array();

        foreach($matchs as $match){
            $result[trim($match[1])] = trim($match[2]);
        }

        return $result;
    }
}


if (!function_exists('stgh_parse_phpinfo')) {
    function stgh_parse_phpinfo()
    {
        ob_start();
        phpinfo(INFO_MODULES);
        $s = ob_get_contents();
        ob_end_clean();
        $s = strip_tags($s, '<h2><th><td>');
        $s = preg_replace('/<th[^>]*>([^<]+)<\/th>/', '<info>\1</info>', $s);
        $s = preg_replace('/<td[^>]*>([^<]+)<\/td>/', '<info>\1</info>', $s);
        $t = preg_split('/(<h2[^>]*>[^<]+<\/h2>)/', $s, -1, PREG_SPLIT_DELIM_CAPTURE);
        $r = array();
        $count = count($t);
        $p1 = '<info>([^<]+)<\/info>';
        $p2 = '/' . $p1 . '\s*' . $p1 . '\s*' . $p1 . '/';
        $p3 = '/' . $p1 . '\s*' . $p1 . '/';
        for ($i = 1; $i < $count; $i++) {
            if (preg_match('/<h2[^>]*>([^<]+)<\/h2>/', $t[$i], $matchs)) {
                $name = trim($matchs[1]);
                $vals = explode("\n", $t[$i + 1]);
                foreach ($vals AS $val) {
                    if (preg_match($p2, $val, $matchs)) { // 3cols
                        $r[$name][trim($matchs[1])] = array(trim($matchs[2]), trim($matchs[3]));
                    } elseif (preg_match($p3, $val, $matchs)) { // 2cols
                        $r[$name][trim($matchs[1])] = trim($matchs[2]);
                    }
                }
            }
        }
        return $r;
    }
}

if (!function_exists('stgh_get_current_version_object')) {
    function stgh_get_current_version_object()
    {
        return \StgHelpdesk\Versionobject\Version_Object_Factory::getObject(get_bloginfo('version'));
    }
}

if (!function_exists('stgh_remove_update_notifications')) {
    function stgh_remove_update_notifications($value)
    {
        if (isset($value) && is_object($value)) {
            unset($value->response[STG_PLUGIN_BASENAME]);
        }

        return $value;
    }
}
if (!function_exists('custom_touch_time')) {

    function custom_touch_time($edit = 1, $for_post = 1, $tab_index = 0, $multi = 0, $value = false, $fieldName = false)
    {
        global $wp_locale;
        $post = get_post();

        if ($for_post)
            $edit = !(in_array($post->post_status, array('draft', 'pending')) && (!$post->post_date_gmt || '0000-00-00 00:00:00' == $post->post_date_gmt));

        $tab_index_attribute = '';
        if ((int)$tab_index > 0)
            $tab_index_attribute = " tabindex=\"$tab_index\"";

        $time_adj = current_time('timestamp');

        if(!$value)
            $post_date = ($for_post) ? $post->post_date : get_comment()->comment_date;
        else {
            $post_date = $value;
        }

        $jj = ($edit) ? mysql2date('d', $post_date, false) : gmdate('d', $time_adj);
        $mm = ($edit) ? mysql2date('m', $post_date, false) : gmdate('m', $time_adj);
        $aa = ($edit) ? mysql2date('Y', $post_date, false) : gmdate('Y', $time_adj);
        $hh = ($edit) ? mysql2date('H', $post_date, false) : gmdate('H', $time_adj);
        $mn = ($edit) ? mysql2date('i', $post_date, false) : gmdate('i', $time_adj);
        $ss = ($edit) ? mysql2date('s', $post_date, false) : gmdate('s', $time_adj);

        $cur_jj = gmdate('d', $time_adj);
        $cur_mm = gmdate('m', $time_adj);
        $cur_aa = gmdate('Y', $time_adj);
        $cur_hh = gmdate('H', $time_adj);
        $cur_mn = gmdate('i', $time_adj);


        if(!$fieldName)
        {
            $month = '<label><span class="screen-reader-text">' . __('Month') . '</span><select ' . ($multi ? '' : 'id="mm" ') . 'name="mm"' . $tab_index_attribute . ">\n";
            for ($i = 1; $i < 13; $i = $i + 1) {
                $monthnum = zeroise($i, 2);
                $monthtext = $wp_locale->get_month_abbrev($wp_locale->get_month($i));
                $month .= "\t\t\t" . '<option value="' . $monthnum . '" data-text="' . $monthtext . '" ' . selected($monthnum, $mm, false) . '>';
                /* translators: 1: month number (01, 02, etc.), 2: month abbreviation */
                $month .= sprintf(__('%1$s-%2$s'), $monthnum, $monthtext) . "</option>\n";
            }
            $month .= '</select></label>';

            $day = '<label><span class="screen-reader-text">' . __('Day') . '</span><input type="text" ' . ($multi ? '' : 'id="jj" ') . 'name="jj" value="' . $jj . '" size="2" maxlength="2"' . $tab_index_attribute . ' autocomplete="off" /></label>';
            $year = '<label><span class="screen-reader-text">' . __('Year') . '</span><input type="text" ' . ($multi ? '' : 'id="aa" ') . 'name="aa" value="' . $aa . '" size="4" maxlength="4"' . $tab_index_attribute . ' autocomplete="off" /></label>';
            $hour = '<label><span class="screen-reader-text">' . __('Hour') . '</span><input type="text" ' . ($multi ? '' : 'id="hh" ') . 'name="hh" value="' . $hh . '" size="2" maxlength="2"' . $tab_index_attribute . ' autocomplete="off" /></label>';
            $minute = '<label><span class="screen-reader-text">' . __('Minute') . '</span><input type="text" ' . ($multi ? '' : 'id="mn" ') . 'name="mn" value="' . $mn . '" size="2" maxlength="2"' . $tab_index_attribute . ' autocomplete="off" /></label>';
        }else{
            $month = '<label><span class="screen-reader-text">' . __('Month') . '</span><select ' . ($multi ? '' : 'id="mm" ') . 'name="'.$fieldName.'[stgh_date][mm]"' . $tab_index_attribute . ">\n";
            for ($i = 1; $i < 13; $i = $i + 1) {
                $monthnum = zeroise($i, 2);
                $monthtext = $wp_locale->get_month_abbrev($wp_locale->get_month($i));
                $month .= "\t\t\t" . '<option value="' . $monthnum . '" data-text="' . $monthtext . '" ' . selected($monthnum, $mm, false) . '>';
                /* translators: 1: month number (01, 02, etc.), 2: month abbreviation */
                $month .= sprintf(__('%1$s-%2$s'), $monthnum, $monthtext) . "</option>\n";
            }
            $month .= '</select></label>';

            $day = '<label><span class="screen-reader-text">' . __('Day') . '</span><input type="text" ' . ($multi ? '' : 'id="jj" ') . 'name="'.$fieldName.'[stgh_date][jj]" value="' . $jj . '" size="2" maxlength="2"' . $tab_index_attribute . ' autocomplete="off" /></label>';
            $year = '<label><span class="screen-reader-text">' . __('Year') . '</span><input type="text" ' . ($multi ? '' : 'id="aa" ') . 'name="'.$fieldName.'[stgh_date][aa]" value="' . $aa . '" size="4" maxlength="4"' . $tab_index_attribute . ' autocomplete="off" /></label>';
            $hour = '<label><span class="screen-reader-text">' . __('Hour') . '</span><input type="text" ' . ($multi ? '' : 'id="hh" ') . 'name="hh" value="' . $hh . '" size="2" maxlength="2"' . $tab_index_attribute . ' autocomplete="off" /></label>';
            $minute = '<label><span class="screen-reader-text">' . __('Minute') . '</span><input type="text" ' . ($multi ? '' : 'id="mn" ') . 'name="mn" value="' . $mn . '" size="2" maxlength="2"' . $tab_index_attribute . ' autocomplete="off" /></label>';

        }


        echo '<div class="timestamp-wrap">';
        /* translators: 1: month, 2: day, 3: year, 4: hour, 5: minute */
        //printf(__('%1$s %2$s, %3$s @ %4$s:%5$s'), $month, $day, $year, $hour, $minute);
         printf(__('%1$s %2$s, %3$s'), $month, $day, $year, $hour, $minute);



        echo '</div><input type="hidden" id="ss" name="ss" value="' . $ss . '" />';

        if ($multi) return;

        echo "\n\n";
        $map = array(
            'mm' => array($mm, $cur_mm),
            'jj' => array($jj, $cur_jj),
            'aa' => array($aa, $cur_aa),
            'hh' => array($hh, $cur_hh),
            'mn' => array($mn, $cur_mn),
        );
        foreach ($map as $timeunit => $value) {
            list($unit, $curr) = $value;

            echo '<input type="hidden" id="hidden_' . $timeunit . '" name="hidden_' . $timeunit . '" value="' . $unit . '" />' . "\n";
            $cur_timeunit = 'cur_' . $timeunit;
            echo '<input type="hidden" id="' . $cur_timeunit . '" name="' . $cur_timeunit . '" value="' . $curr . '" />' . "\n";
        }
        ?>

        <p>
            <a href="#edit_timestamp" class="save-timestamp hide-if-no-js button"><?php _e('OK'); ?></a>
            <a href="#edit_timestamp" class="cancel-timestamp hide-if-no-js button-cancel"><?php _e('Cancel'); ?></a>
        </p>
        <?php
    }
}

if (!function_exists('stgh_get_plugin_data')) {
    function stgh_get_plugin_data()
    {
        if( !function_exists('get_plugin_data') ){
            require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
        }


        return get_plugin_data(STG_HELPDESK_ROOT."/".STG_HELPDESK_NAME.".php");

    }
}
