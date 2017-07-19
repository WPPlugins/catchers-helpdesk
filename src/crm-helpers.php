<?php

if (!function_exists('stgh_crm_get_companies')) {
    function stgh_crm_get_companies()
    {
        global $wpdb;
        $values = $wpdb->get_col("SELECT meta_value
	      FROM $wpdb->usermeta WHERE meta_key = '_stgh_crm_company'");

        $values = array_filter($values, function ($v) {
            return !empty($v);
        });

        return array_values($values);
    }
}

if (!function_exists('stgh_crm_get_user_company')) {
    function stgh_crm_get_user_company($userId)
    {
        global $wpdb;
        $values = $wpdb->get_col("SELECT meta_value
	      FROM $wpdb->usermeta WHERE meta_key = '_stgh_crm_company' AND user_id='$userId'");

        $values = array_filter($values, function ($v) {
            return !empty($v);
        });

        $company = reset($values);
        if (empty($company)) {
            return false;
        }

        return $company;
    }
}

if (!function_exists('stgh_crm_get_users_by_company')) {
    function stgh_crm_get_users_by_company($company)
    {
        global $wpdb;
        $values = $wpdb->get_col("SELECT user_id
	      FROM $wpdb->usermeta WHERE meta_key = '_stgh_crm_company' AND meta_value = '$company'");

        $values = array_filter($values, function ($v) {
            return !empty($v);
        });

        return array_values($values);
    }
}


if (!function_exists('stgh_crm_get_user_full_name')) {
    function stgh_crm_get_user_full_name($user_id)
    {
        $full_name = get_the_author_meta('first_name', $user_id) . ' ' . get_the_author_meta('last_name', $user_id);
        if ($full_name == ' ') {
            $user = get_userdata($user_id);
            if (!$user)
                return $full_name;
            return $user->display_name;
        }
        return $full_name;
    }
}
