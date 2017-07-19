<?php

namespace StgHelpdesk\Core\PostType;

class Stg_Helpdesk_Post_Type
{

    public static $instance;

    public static function instance()
    {
        if (is_null(static::$instance)) {
            static::$instance = new static;
        }

        return static::$instance;
    }

    private function __construct()
    {
        add_action('init', array($this, 'postType'), 5, 0);
        add_action('init', array($this, 'additionalPostTypes'), 5, 0);
        add_action('init', array($this, 'postStatuses'), 5, 0);
        add_action('post_updated_messages', array($this, 'updateMessages'), 5, 1);
        add_action('init', array($this, 'ticketCategoriesTaxonomy'));
        add_action('init', array($this, 'ticketTagsTaxonomy'));
    }

    /**
     * Register the ticket post type.
     *
     * @since 1.0.2
     */
    public function postType()
    {
        $slug = STG_HELPDESK_SLUG;

        /* Supported components */
        $supports = array('title');

        /* If the post is being created we add the editor */
        if (!isset($_GET['post'])) {
            array_push($supports, 'editor');
        }

        /* Post type menu icon */
        $version_object = stgh_get_current_version_object();
        $icon = $version_object->getPluginIcon();

        /* Post type labels */
        $labels = apply_filters('stgh_ticket_type_labels', array(
            'name' => _x('Tickets', 'post type general name', STG_HELPDESK_TEXT_DOMAIN_NAME),
            'singular_name' => _x('Ticket', 'post type singular name', STG_HELPDESK_TEXT_DOMAIN_NAME),
            'menu_name' => _x('Tickets', 'admin menu', STG_HELPDESK_TEXT_DOMAIN_NAME),
            'name_admin_bar' => _x('Ticket', 'add new on admin bar', STG_HELPDESK_TEXT_DOMAIN_NAME),
                        'new_item' => __('New ticket', STG_HELPDESK_TEXT_DOMAIN_NAME),
            'edit_item' => __('Edit ticket', STG_HELPDESK_TEXT_DOMAIN_NAME),
            'view_item' => __('View ticket', STG_HELPDESK_TEXT_DOMAIN_NAME),
            'all_items' => __('All tickets', STG_HELPDESK_TEXT_DOMAIN_NAME),
            'search_items' => __('Search tickets', STG_HELPDESK_TEXT_DOMAIN_NAME),
            'not_found' => __('No tickets found.', STG_HELPDESK_TEXT_DOMAIN_NAME)
        ));

        /* Post type capabilities */
        $cap = apply_filters('stgh_ticket_type_cap', array(
            'read' => 'view_ticket',
            'read_post' => 'view_ticket',
            'edit_post' => 'edit_ticket',
            'edit_posts' => 'edit_ticket',
            'edit_others_posts' => 'edit_other_ticket',
            'edit_published_posts' => 'edit_ticket',
            'publish_posts' => 'create_ticket',
            'delete_post' => 'delete_ticket',
            'delete_posts' => 'delete_ticket',
            'delete_published_posts' => 'delete_ticket',
            'delete_others_posts' => 'delete_other_ticket',
                        //'create_posts' => 'do_not_allow',
            'create_posts' => false,
            
        ));

        /* Post type arguments */
        $args = apply_filters('stgh_ticket_type_args', array(
            'labels' => $labels,
            'public' => true,
            'exclude_from_search' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'query_var' => true,
            'rewrite' => array('slug' => apply_filters('stgh_rewrite_slug', $slug), 'with_front' => false),
            'capability_type' => 'view_ticket',
            'capabilities' => $cap,
            'has_archive' => false,
            'hierarchical' => false,
            'menu_position' => null,
            'menu_icon' => $icon,
            'supports' => $supports,
            'delete_with_user' => true,
            'taxonomies' => array(STG_HELPDESK_POST_TYPE_CATEGORY, STG_HELPDESK_POST_TYPE_TAG)
        ));

        register_post_type(STG_HELPDESK_POST_TYPE, $args);
    }

    public function ticketCategoriesTaxonomy()
    {

        $labels = array(
            'name' => _x('Categories', 'Taxonomy general name', STG_HELPDESK_TEXT_DOMAIN_NAME),
            'singular_name' => _x('Category', 'Taxonomy singular name', STG_HELPDESK_TEXT_DOMAIN_NAME),
            'menu_name' => __('Categories', STG_HELPDESK_TEXT_DOMAIN_NAME),
            'all_items' => __('All items', STG_HELPDESK_TEXT_DOMAIN_NAME),
            'parent_item' => __('Parent item', STG_HELPDESK_TEXT_DOMAIN_NAME),
            'parent_item_colon' => __('Parent item:', STG_HELPDESK_TEXT_DOMAIN_NAME),
            'new_item_name' => __('New category name', STG_HELPDESK_TEXT_DOMAIN_NAME),
            'add_new_item' => __('Add new category', STG_HELPDESK_TEXT_DOMAIN_NAME),
            'edit_item' => __('Edit category', STG_HELPDESK_TEXT_DOMAIN_NAME),
            'update_item' => __('Update category', STG_HELPDESK_TEXT_DOMAIN_NAME),
            'view_item' => __('View category', STG_HELPDESK_TEXT_DOMAIN_NAME),
            'separate_items_with_commas' => __('Separate items with commas', STG_HELPDESK_TEXT_DOMAIN_NAME),
            'add_or_remove_items' => __('Add or remove items', STG_HELPDESK_TEXT_DOMAIN_NAME),
            'choose_from_most_used' => __('Choose from the most used', STG_HELPDESK_TEXT_DOMAIN_NAME),
            'popular_items' => __('Popular items', STG_HELPDESK_TEXT_DOMAIN_NAME),
            'search_items' => __('Search items', STG_HELPDESK_TEXT_DOMAIN_NAME),
            'not_found' => __('Not found', STG_HELPDESK_TEXT_DOMAIN_NAME),
            'no_terms' => __('No items', STG_HELPDESK_TEXT_DOMAIN_NAME),
            'items_list' => __('Items list', STG_HELPDESK_TEXT_DOMAIN_NAME),
            'items_list_navigation' => __('Items list navigation', STG_HELPDESK_TEXT_DOMAIN_NAME),
        );
        $args = array(
            'labels' => $labels,
            'hierarchical' => false,
            'public' => true,
            'show_ui' => true,
            'show_admin_column' => true,
            'show_in_nav_menus' => true,
            'show_tagcloud' => true,
            'update_count_callback' => 'stgh_update_taxonomy_count',
        );
        register_taxonomy(STG_HELPDESK_POST_TYPE_CATEGORY, array(STG_HELPDESK_POST_TYPE), $args);

    }

    public function ticketTagsTaxonomy()
    {

        $labels = array(
            'name' => _x('Tags', 'Taxonomy general name', STG_HELPDESK_TEXT_DOMAIN_NAME),
            'singular_name' => _x('Tag', 'Taxonomy singular name', STG_HELPDESK_TEXT_DOMAIN_NAME),
            'menu_name' => __('Tags', STG_HELPDESK_TEXT_DOMAIN_NAME),
            'all_items' => __('All items', STG_HELPDESK_TEXT_DOMAIN_NAME),
            'parent_item' => __('Parent item', STG_HELPDESK_TEXT_DOMAIN_NAME),
            'parent_item_colon' => __('Parent item:', STG_HELPDESK_TEXT_DOMAIN_NAME),
            'new_item_name' => __('New tag name', STG_HELPDESK_TEXT_DOMAIN_NAME),
            'add_new_item' => __('Add new tag', STG_HELPDESK_TEXT_DOMAIN_NAME),
            'edit_item' => __('Edit tag', STG_HELPDESK_TEXT_DOMAIN_NAME),
            'update_item' => __('Update tag', STG_HELPDESK_TEXT_DOMAIN_NAME),
            'view_item' => __('View tag', STG_HELPDESK_TEXT_DOMAIN_NAME),
            'separate_items_with_commas' => __('Separate items with commas', STG_HELPDESK_TEXT_DOMAIN_NAME),
            'add_or_remove_items' => __('Add or remove items', STG_HELPDESK_TEXT_DOMAIN_NAME),
            'choose_from_most_used' => __('Choose from the most used', STG_HELPDESK_TEXT_DOMAIN_NAME),
            'popular_items' => __('Popular items', STG_HELPDESK_TEXT_DOMAIN_NAME),
            'search_items' => __('Search items', STG_HELPDESK_TEXT_DOMAIN_NAME),
            'not_found' => __('Not found', STG_HELPDESK_TEXT_DOMAIN_NAME),
            'no_terms' => __('No items', STG_HELPDESK_TEXT_DOMAIN_NAME),
            'items_list' => __('Items list', STG_HELPDESK_TEXT_DOMAIN_NAME),
            'items_list_navigation' => __('Items list navigation', STG_HELPDESK_TEXT_DOMAIN_NAME),
        );
        $args = array(
            'labels' => $labels,
            'hierarchical' => false,
            'public' => true,
            'show_ui' => true,
            'show_admin_column' => true,
            'show_in_nav_menus' => true,
            'show_tagcloud' => true,
            'update_count_callback' => 'stgh_update_taxonomy_count',
        );
        register_taxonomy(STG_HELPDESK_POST_TYPE_TAG, array(STG_HELPDESK_POST_TYPE), $args);

    }

    /**
     * Register comments and history posttypes
     */
    public function additionalPostTypes()
    {
        register_post_type(STG_HELPDESK_COMMENTS_POST_TYPE,
            array('delete_with_user' => true, 'public' => false, 'exclude_from_search' => true, 'supports' => array('editor')));
        register_post_type(STG_HELPDESK_HISTORY_POST_TYPE, array('delete_with_user' => true, 'public' => false, 'exclude_from_search' => true));
    }

    /**
     * Update messages for actions with custom posttype
     *
     * @param $messages
     * @return mixed
     */
    public function updateMessages($messages)
    {
        $post = get_post();
        $postType = get_post_type($post);
        $post_type_object = get_post_type_object($postType);

        if (STG_HELPDESK_POST_TYPE !== $postType) {
            return $messages;
        }

        $messages[$postType] = array(
            0 => '', // Unused. Messages start at index 1.
            1 => __('Ticket updated.', STG_HELPDESK_TEXT_DOMAIN_NAME),
            2 => __('Custom field updated.', STG_HELPDESK_TEXT_DOMAIN_NAME),
            3 => __('Custom field deleted.', STG_HELPDESK_TEXT_DOMAIN_NAME),
            4 => __('Ticket updated.', STG_HELPDESK_TEXT_DOMAIN_NAME),
            /* translators: %s: date and time of the revision */
            5 => isset($_GET['revision']) ? sprintf(__('Ticket restored to revision from %s', STG_HELPDESK_TEXT_DOMAIN_NAME),
                wp_post_revision_title((int)$_GET['revision'], false)) : false,
            6 => __('Ticket published.', STG_HELPDESK_TEXT_DOMAIN_NAME),
            7 => __('Ticket saved.', STG_HELPDESK_TEXT_DOMAIN_NAME),
            8 => __('Ticket submitted.', STG_HELPDESK_TEXT_DOMAIN_NAME),
            9 => sprintf(
                __('Ticket scheduled for: <strong>%1$s</strong>.', STG_HELPDESK_TEXT_DOMAIN_NAME),
                // translators: Publish box date format, see http://php.net/date
                date_i18n(__('M j, Y @ G:i', STG_HELPDESK_TEXT_DOMAIN_NAME), strtotime($post->post_date))
            ),
            10 => __('Ticket draft updated.', STG_HELPDESK_TEXT_DOMAIN_NAME)
        );

        if ($post_type_object->publicly_queryable) {
            $permalink = get_permalink($post->ID);

            $view_link = sprintf(' <a href="%s">%s</a>', esc_url($permalink), __('View ticket', STG_HELPDESK_TEXT_DOMAIN_NAME));
            $messages[$postType][1] .= $view_link;
            $messages[$postType][6] .= $view_link;
            $messages[$postType][9] .= $view_link;

            $preview_permalink = add_query_arg('preview', 'true', $permalink);
            $preview_link = sprintf(' <a target="_blank" href="%s">%s</a>', esc_url($preview_permalink),
                __('Preview ticket', STG_HELPDESK_TEXT_DOMAIN_NAME));
            $messages[$postType][8] .= $preview_link;
            $messages[$postType][10] .= $preview_link;
        }

        return $messages;
    }

    /**
     * Register post statuses
     */
    public function postStatuses()
    {
        $statuses = stgh_get_statuses();

        foreach ($statuses as $id => $status) {
            register_post_status($id, array(
                'label' => $status,
                'public' => true,
                'exclude_from_search' => false,
                'show_in_admin_all_list' => true,
                'show_in_admin_status_list' => true,
                'label_count' => _n_noop("$status <span class='count'>(%s)</span>",
                    "$status <span class='count'>(%s)</span>", STG_HELPDESK_TEXT_DOMAIN_NAME),
            ));
        }
    }
}