<?php

namespace StgHelpdesk\Ticket;

use StgHelpdesk\Admin\Stg_Helpdesk_Admin;
use StgHelpdesk\Helpers\Stg_Helper_Email;
use StgHelpdesk\Helpers\Stg_Helper_Logger;
use StgHelpdesk\Helpers\Stg_Helper_UploadFiles;
use StgHelpdesk\Ticket\Stg_Helpdesk_TicketComments;
use StgHelpdesk\Ticket\Stg_Helpdesk_Ticket;

class Stg_Helpdesk_MetaBoxes
{

    protected static $customFields = array('stgh_assignee');
    
    /**
     * Save metabox fields
     *
     * @param $postId
     */
    public static function saveMetaBoxFields($postId)
    {

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE || wp_is_post_revision($postId)) {
            return;
        }

        if (!stgh_request_is_not_ajax()) {
            return;
        }

        // have permissions
        if (!stgh_current_user_can('edit_ticket', $postId)) {
            return;
        }

        // Bulk edit
        self::bulkEditSavePost();

        $post = get_post($postId);
        $postMeta = get_post_meta($postId);

        if($post->post_status == "auto-draft"){
            $user = get_user_by('id', $post->post_author);

            $name = stgh_crm_get_user_full_name($user->ID);

            if (empty($name))
                $name = $user->user_nicename;


            add_post_meta($postId, '_stgh_ticket_author_agent', '1', true);
            add_post_meta($postId, '_stgh_ticket_author_name', $name, true);
            add_post_meta($postId, '_stgh_ticket_author_email', $user->user_email, true);
            add_post_meta($postId, '_stgh_ticket_author_roles', implode(' ', $user->roles), true);

            $last_reply_message_id = Stg_Helper_Email::getNewMessageId($user->user_email);
            Stg_Helpdesk_Ticket::addReference($postId, $last_reply_message_id);
        }

        // check nonce
        if (!isset($_POST[Stg_Helpdesk_Admin::$nonceName]) || !wp_verify_nonce($_POST[Stg_Helpdesk_Admin::$nonceName],
                Stg_Helpdesk_Admin::$nonceAction)
        ) {
            return;
        }

        $_POST['post_status_override'] = $_POST['post_status_override_main'];
        $_POST['stgh_assignee'] = $_POST["stgh_assignee_main"];

        if(isset($_POST["auto_draft"]) && $_POST["auto_draft"] == "1") {
            $_POST['post_content'] = $_POST['stgh_comment'];
            Stg_Helper_UploadFiles::handleUploadsForm($postId);
                    }
        else{
            self::saveTicketReplies($postId);
        }

        


        if (!empty($_POST['stgh_crm_contact_value'])) {
            update_post_meta($postId, '_stgh_contact', intval($_POST['stgh_crm_contact_value']));


            switch($_POST['stgh_notify']){
                case 'notify':
                        Stg_Helper_Email::sendEventTicket('ticket_open', $postId);
                    break;
                case 'send':
                        Stg_Helper_Email::sendEventTicket('agent_reply', $postId);
                    break;
            }
        }


        if (isset($_POST['stgh-add-contact'])) {
            if (!empty($_POST['stgh_crm_new_contact_name']) && !empty($_POST['stgh_crm_new_contact_email'])) {
                $email = sanitize_text_field($_POST['stgh_crm_new_contact_email']);

                remove_all_filters('registration_errors');

                $version_object = stgh_get_current_version_object();
                $newUserId = $version_object->register_new_user($email, $email);

                if ($newUserId instanceof \WP_Error) {
                    if(in_array('email_exists',$newUserId->get_error_codes())) {

                        $existUser = get_user_by('email', $email);
                        update_post_meta($postId, '_stgh_contact', $existUser->ID);

                        $_POST["stgh_crm_contact_value"] = $existUser->ID;
                        Stg_Helper_Email::sendEventTicket('ticket_open', $postId);

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

            }
        }

        if (isset($_POST['publish'])) {
            $_POST['stgh_assignee'] = $_POST['stgh_assignee_sub'];
            $_POST['post_status_override'] = $_POST['post_status_override_sub'];

            if (isset($_POST['stgh_category'])) {
                self::saveCategory($postId, $_POST['stgh_category']);
            }
        }

        remove_action('save_post_' . STG_HELPDESK_POST_TYPE, 'stgh_save_custom_fields');

        self::handleCustomFields($postId);
        if(isset($_POST['stgh_custom_fields']))
            self::saveCustomFields($postId, $_POST['stgh_custom_fields']);

        if (!empty($_POST['post_title'])) {
            Stg_Helpdesk_Ticket::updateTitle($postId, sanitize_text_field($_POST['post_title']));
        }

            }

    public static function saveCustomFields($ticketId,$data){
        foreach($data as $fieldName => $value){
            if(is_array($value))
            {
                $value = array_filter($value, function($value) { return $value !== ''; });
                $value = array_map( 'esc_attr', $value );
            }else
            {
                $value = esc_html($value);
            }

            update_post_meta($ticketId, $fieldName, $value);
        }
    }

    private static function handleCustomFields($postId)
    {
        foreach (self::$customFields as $field) {
            if (isset($_POST[$field])) {
                $value = sanitize_text_field($_POST[$field]);
                $field = '_' . $field;
                $originalValue = get_post_meta($postId, $field, true);

                if (strlen($originalValue) && !strlen($value)) {
                    delete_post_meta($postId, $field, $originalValue);
                } elseif (strlen($value)) {
                    update_post_meta($postId, $field, $value, $originalValue);
                }
                //email
                if ($field == '_stgh_assignee' && (int)$originalValue != $value) {
                    Stg_Helper_Email::sendEventTicket('ticket_assign', $postId);
                }
            }
        }
    }

    /**
     * Save post changes from bulk editor
     */
    private static function bulkEditSavePost()
    {
        if (isset($_GET['post'])) {
            if (is_array($_GET['post']) && count($_GET['post'])) {
                foreach ($_GET['post'] as $post_id) {
                    // change assigned
                    if (isset($_GET['stgh_bulk_edit_assignee_sub']) && (int)$_GET['stgh_bulk_edit_assignee_sub'] > 0) {
                        update_post_meta($post_id, '_stgh_assignee', $_GET['stgh_bulk_edit_assignee_sub']);
                        $_POST['stgh_assignee'] = $_GET['stgh_bulk_edit_assignee_sub'];
                    }

                    
                    if (isset($_GET['stgh_bulk_edit_status']) && !empty($_GET['stgh_bulk_edit_status'])) {
                        Stg_Helpdesk_Ticket::changeStatus($post_id, $_GET['stgh_bulk_edit_status']);
                        $_POST['post_status_override'] = $_GET['stgh_bulk_edit_status'];
                    }
                    
                    self::handleCustomFields($post_id);
                    unset($_POST['stgh_assignee']);
                }
            }
        }
    }

    /**
     *  Save category
     *
     * @param $postId
     * @param $value
     */
    public static function saveCategory($postId, $value)
    {
        $value = (int)$value;
        if ($value == 0) {
            self::deleteCategory($postId);
        } else {
            $term = get_term($value, STG_HELPDESK_POST_TYPE_CATEGORY);
            if (!is_null($term) && !has_term($term->term_id, STG_HELPDESK_POST_TYPE_CATEGORY, $postId)) {
                wp_set_object_terms($postId, $value, STG_HELPDESK_POST_TYPE_CATEGORY, false);

                            }
        }
    }

    /**
     *  Save tag
     *
     * @param $postId
     * @param $value
     */
    public static function saveTag($postId, $value)
    {
        $value = (int)$value;

        $term = get_term($value, STG_HELPDESK_POST_TYPE_TAG);

        if ( is_wp_error( $term ) ) {
            return;
        }

        if (!is_null($term) && !has_term($term->term_id, STG_HELPDESK_POST_TYPE_TAG, $postId)) {
            wp_set_object_terms($postId, $value, STG_HELPDESK_POST_TYPE_TAG, false);
        }
    }

    /**
     * Remove category
     *
     * @param int $postId
     */
    protected static function deleteCategory($postId)
    {
        wp_delete_object_term_relationships($postId, STG_HELPDESK_POST_TYPE_CATEGORY);
    }

    /**
     * Save replies
     *
     * @param string $postId
     */
    protected static function saveTicketReplies($postId)
    {

                if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE || wp_is_post_revision($postId)) {
            return;
        }

        if (!stgh_request_is_not_ajax()) {
            return;
        }

        // have permissions
        if (!stgh_current_user_can('edit_ticket', $postId)) {
            return;
        }

        $current_user = stgh_get_current_user();

        if ((!empty($_POST['stgh_comment']) || !empty($_POST['stgh_comment_private'])) && isset($_POST[Stg_Helpdesk_TicketComments::$nonceName]))
        {
            /* Check for the nonce */
            if (wp_verify_nonce($_POST[Stg_Helpdesk_TicketComments::$nonceName], Stg_Helpdesk_TicketComments::$nonceAction)) {

                $userId = $current_user->ID;

                $content =                         wp_kses_post($_POST['stgh_comment']);

                $data = apply_filters('stgh_admin_ticket_comment_args', array(
                    'post_content' => $content,
                    'post_type' => STG_HELPDESK_COMMENTS_POST_TYPE,
                    'post_author' => $userId,
                    'post_parent' => $postId,
                    'post_status' => 'publish',
                    'ping_status' => 'closed',
                    'comment_status' => 'closed',
                ));

                do_action('stgh_admin_before_comment_on_ticket', $postId, $data);

                $reply = Stg_Helpdesk_TicketComments::addToPost($data, $postId);

                $to = sanitize_text_field($_POST['stgh_reply_to']);
                $cc = sanitize_text_field($_POST['stgh-cc']);
                $bcc = sanitize_text_field($_POST['stgh-bcc']);

                $to && add_post_meta($reply, '_stgh_reply_to', $to, true);
                $cc && add_post_meta($reply, '_stgh_reply_cc', $cc, true);
                $bcc && add_post_meta($reply, '_stgh_reply_bcc', $bcc, true);

                if ($reply) {
                                            $ticket = \StgHelpdesk\Ticket\Stg_Helpdesk_Ticket::getInstance($postId);
                        $author = $ticket->getUser();

                        $agentTicket = get_post_meta( $postId, '_stgh_ticket_author_agent', true);
                        
                        if($agentTicket == 1){
                            $contact = $ticket->getContact();
                            if (($to && trim($to) != trim($contact->user_email)) || $cc || $bcc) {
                                add_post_meta($reply, '_stgh_type_color', 'agent-to-other-person', true);
                            } else {
                                add_post_meta($reply, '_stgh_type_color', 'agent', true);
                            }

                        }else{
                            if (($to && trim($to) != trim($author->user_email)) || $cc || $bcc) {
                                add_post_meta($reply, '_stgh_type_color', 'agent-to-other-person', true);
                            } else {
                                add_post_meta($reply, '_stgh_type_color', 'agent', true);
                            }
                        }


                                            // Save user nickname and email in meta
                    Stg_Helpdesk_TicketComments::addCommentUserMeta($reply, $userId);

                    Stg_Helper_UploadFiles::handleUploadsForm($reply, $postId);
                }

                do_action('stgh_admin_after_comment_on_ticket', $postId, $data, $reply);

                if (is_wp_error($reply)) {

                    stgh_add_redirect_to(add_query_arg(array('stgh-message' => 'stgh_comment_error'),
                        get_permalink($postId)));

                } else {
                                            //email
                        Stg_Helper_Email::sendEventTicket('agent_reply', $reply);
                        
                    if (isset($_POST['stgh-do']) && 'reply_close' == $_POST['stgh-do']) {
                        /* Confirm the post type and close */
                        if (stgh_is_our_post_type(get_post_type($postId))) {

                            do_action('stgh_admin_before_close_ticket', $postId);

                            $closed = Stg_Helpdesk_Ticket::close($postId);
                            do_action('stgh_admin_close_ticket', $postId);
                        }
                    }
                }
            }
        }

        return;
    }

    public static function addBulkEditCustomColumns($posts_columns)
    {
        $posts_columns['assigned'] = 'Assigned to';
        return $posts_columns;
    }

    public static function showBulkEditCustomColumns($column_name, $post_type)
    {
        if ('assigned' == $column_name) {
            $statuses = stgh_get_statuses();

            $options = '<option selected ></option>';
            foreach ($statuses as $status => $label) {
                $selected = '';
                $options .= '<option value="' . $status . '" ' . $selected . '> ' . $label . ' </option>';
            }
            $assignedTo = null;
            echo '<fieldset class="inline-edit-col-right">
                <div class="inline-edit-tags">
                    <div class="stgh_table_div">
                        <div class="stgh_table_row_div">
                            <div class="stgh_table_cell_div">' . __('Change status', STG_HELPDESK_TEXT_DOMAIN_NAME) . '</div>
                            <div class="stgh_table_cell_div">
                                <span id="stgh-metabox-details-status-select">
                                    <select name="stgh_bulk_edit_status">' . $options . '</select>
                                </span>
                            </div>
                        </div>
                        <div class="stgh_table_row_div">
                            <div class="stgh_table_cell_div">' . __('Assigned to', STG_HELPDESK_TEXT_DOMAIN_NAME) . '</div>
                            <div class="stgh_table_cell_div">
                            ' . stgh_display_assign_to_select(array('class' => 'stgh-assign-select'), !is_null($assignedTo) ? $assignedTo->ID : 0, 'stgh_bulk_edit_assignee_sub') . '
                            </div>
                        </div>
                     </div>
                </div>
            </fieldset>';
        } else {
            return false;
        }
    }


    }