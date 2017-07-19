<?php

namespace StgHelpdesk\Ticket;

class Stg_Helpdesk_CommentsAjax
{

    protected static $instance = null;

    protected static $allowedEditorSettings = array(
        'wpautop',
        'media_buttons',
        'textarea_name',
        'textarea_rows',
        'tabindex',
        'editor_css',
        'editor_class',
        'teeny',
        'dfw',
        'quicktags',
        'drag_drop_upload',
    );

    protected $mceSettings;

    protected $qtSettings;

    public function __construct()
    {
        add_filter('tiny_mce_before_init', array($this, 'getTinymceSettings'), 10, 2);
        add_action('wp_ajax_stgh_edit_comment', array($this, 'htmlEditor'), 10, 0);
        add_action('wp_ajax_wp_editor_content_ajax', array($this, 'getContent'), 10, 0);
        add_action('wp_ajax_stgh_edit_comment_save', array($this, 'editComment'));
        add_action('wp_ajax_stgh_get_related_tickets', array($this, 'getRelatedPosts'));
        add_action('wp_ajax_stgh_create_user', array($this, 'createUser'));
    }

    public static function instance()
    {
        if (null == static::$instance) {
            static::$instance = new static;
        }

        return static::$instance;
    }

    public function getTinymceSettings($mceInit, $editorId)
    {
        $this->mceSettings = $mceInit;

        return $mceInit;
    }

    public function htmlEditor()
    {
        $postId = filter_input(INPUT_POST, 'post_id', FILTER_SANITIZE_NUMBER_INT);
        $editorId = filter_input(INPUT_POST, 'editor_id', FILTER_SANITIZE_STRING);
        $name = filter_input(INPUT_POST, 'textarea_name', FILTER_SANITIZE_STRING);
        $settings = (array)filter_input(INPUT_POST, 'editor_settings', FILTER_UNSAFE_RAW);

        if (empty($editorId)) {
            die;
        }

        if (!empty($postId)) {
            $post = get_post($postId);
        }

        /**
         * Get the content and filter it.
         */
        $content = (isset($post) && !empty($post)) ? $post->post_content : filter_input(INPUT_POST, 'editor_content',
            FILTER_SANITIZE_STRING);
        $content = apply_filters('the_content', $content);

        /**
         * Filter the user settings for the editor
         */
        $settings = $this->getEditorSetting($settings);

        $settings['quicktags'] = false;
        $settings['media_buttons'] = false;
        $settings['editor_class'] = 'stgh-edittextarea';
        $settings['textarea_rows'] = 5;

        /**
         * Make sure we have a textarea name
         */
        if (!isset($settings['textarea_name']) || empty($settings['textarea_name'])) {
            $settings['textarea_name'] = !empty($name) ? $name : $editorId;
        }

        /**
         * Load a new instance of TinyMCE.
         */
        wp_editor($content, $editorId, $settings);

        $mceInit = $this->getMceInit($editorId); ?>

        <script type="text/javascript">
            tinyMCEPreInit.mceInit = jQuery.extend(tinyMCEPreInit.mceInit, <?php echo $mceInit ?>);
        </script>

        <?php die();
    }

    private function getMceInit($editor_id)
    {
        if (!empty($this->mceSettings)) {
            $options = $this->parseSetting($this->mceSettings);
            $mceInit = "'$editor_id':{$options},";
            $mceInit = '{' . trim($mceInit, ',') . '}';
        } else {
            $mceInit = '{}';
        }

        return $mceInit;
    }

    private function parseSetting($init)
    {
        $options = '';
        foreach ($init as $k => $v) {
            if (is_bool($v)) {
                $val = $v ? 'true' : 'false';
                $options .= $k . ':' . $val . ',';
                continue;
            } elseif (!empty($v) && is_string($v) && (('{' == $v{0} && '}' == $v{strlen($v) - 1}) || ('[' == $v{0} && ']' == $v{strlen($v) - 1}) || preg_match('/^\(?function ?\(/',
                        $v))
            ) {
                $options .= $k . ':' . $v . ',';
                continue;
            }
            $options .= $k . ':"' . $v . '",';
        }

        return '{' . trim($options, ' ,') . '}';
    }

    protected function getEditorSetting($settings)
    {
        foreach ($settings as $setting => $value) {
            if (!array_key_exists($setting, self::$allowedEditorSettings)) {
                unset($settings[$setting]);
            }
        }

        return $settings;
    }

    public function getContent()
    {
        $postId = filter_input(INPUT_POST, 'post_id', FILTER_SANITIZE_NUMBER_INT);

        if (empty($postId)) {
            echo '';
            die();
        }

        $post = get_post($postId);

        if (empty($post)) {
            echo '';
            die();
        }

        echo apply_filters('the_content', $post->post_content);
        die();
    }

    public function getQuicktagsSettings($qtInit, $editorId)
    {
        $this->qtSettings = $qtInit;

        return $qtInit;
    }

    public function editComment()
    {
        $ID = Stg_Helpdesk_TicketComments::edit();

        if (false === $ID || is_wp_error($ID)) {
            $ID = $ID->get_error_message();
        }

        echo $ID;
        die();
    }

    public function createUser()
    {
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);

        remove_all_filters('registration_errors');

        $version_object = stgh_get_current_version_object();
        $newUserId = $version_object->register_new_user($email, $email);

        if ($newUserId instanceof \WP_Error) {
            if(in_array('email_exists',$newUserId->get_error_codes())) {
                $user = get_user_by('email', $email);
                update_post_meta($postId, '_stgh_contact', $user->ID);

            }else{
                $log = Stg_Helper_Logger::getLogger();
                $log->log("Errors while user creating " . var_export($newUserId->get_error_messages()) . ' in ' . __FILE__ . ' in line ' . __LINE__);
                return false;
            }
        }
        else{
            // set roles
            $user = new \WP_User($newUserId);
            $user->set_role('stgh_client');

            update_post_meta($postId, '_stgh_contact', $newUserId);
        }

        $return = array(
            'ID'		=> $user->ID,
            'display_name' => $user->display_name,
            'email' =>$user->user_email
        );

        wp_send_json($return);
        die();
    }


        /**
     * Get related posts
     */
    public function getRelatedPosts()
    {
        $userId = filter_input(INPUT_POST, 'user_id', FILTER_SANITIZE_NUMBER_INT);
        $postId = filter_input(INPUT_POST, 'post_id', FILTER_SANITIZE_NUMBER_INT);
        $page = filter_input(INPUT_POST, 'page', FILTER_SANITIZE_NUMBER_INT);
        $byCompany = false;
        $userCompany = stgh_crm_get_user_company($userId);

        if (false !== $userCompany) {
            $byCompany = true;
        }

        $ticket = Stg_Helpdesk_Ticket::getInstance($postId);
        $contact = $ticket->getContact();
        if($contact) {
            $loop = stgh_ticket_get_related_tickets($contact->ID, $postId, $page);


            if ($loop->post_count > 0) {
                $tickets = '<div class="stgh-related-tickets stgh-metabox">';
                while ($loop->have_posts()) {
                    $loop->the_post();
                    $id = get_the_ID();
                    $tickets .= '<div class="stgh-metabox-inner-item stgh-related-tickets-ticket ' . (stgh_ticket_is_closed($id) ? ' stgh-related-tickets-ticket-closed' : '') . '">';
                    $tickets .= '<label for="post-name"><a href="' . stgh_ticket_admin_link($id) . '">' . get_the_title() . '</a></label>';
                    $tickets .= '</div>';
                }
                $tickets .= '</div>';
                $tickets .= '<div class="stgh-tickets-link-to-all">';
                $tickets .= '<a href="' . stgh_link_to_all_user_tickets($byCompany ? $userCompany : $userId, $byCompany) . '" target="_blank">' . __('View all conversations', STG_HELPDESK_TEXT_DOMAIN_NAME) . '</a>';
                $tickets .= '</div>';
            } else {
                $tickets = '<div class="updated below-h2 stgh_margintop_2em">';
                $tickets .= '<h2 class="stgh_related_header">' . __('No conversations', STG_HELPDESK_TEXT_DOMAIN_NAME) . '</h2>';
                $tickets .= '</div>';
            }

            echo $tickets;
        }
        die();
    }
}