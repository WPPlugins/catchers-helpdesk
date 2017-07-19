<?php

use PhpImap\IncomingMail;
use StgHelpdesk\Helpers\Stg_Helpdesk_Mailbox;
use StgHelpdesk\Helpers\Stg_Helper_Email;
use StgHelpdesk\Helpers\Stg_Helper_Logger;
use StgHelpdesk\Helpers\Stg_Helper_UploadFiles;
use StgHelpdesk\Ticket\Stg_Helpdesk_Ticket;
use StgHelpdesk\Ticket\Stg_Helpdesk_TicketComments;


if (!function_exists('stgh_mailbox_connect')) {
    /**
     * @return \PhpImap\Mailbox
     */
    function stgh_mailbox_connect()
    {
        // get some options from settings
        $login = stgh_get_option('stg_mail_login');
        $password = stgh_get_option('mail_pwd');
        $encryption = stgh_get_option('mail_encryption', 'SSL');
        $ssl = $encryption == 'SSL' ? 1 : false;
        $tls = $encryption == 'TLS' ? 1 : false;
        $server = stgh_get_option('mail_server', false);
        $port = stgh_get_option('mail_port', false);
        $protocol = stgh_get_option('mail_protocol', 'pop3');
        $validateCert = false;
        $attachmentDir = __DIR__;

        $mailbox = Stg_Helpdesk_Mailbox::instance($server, $port, $login, $password, $attachmentDir, $ssl,
            $validateCert, $tls);


        return $mailbox->connect($protocol);
    }
}

if (!function_exists('stgh_mailbox_disconnect')) {
    function stgh_mailbox_disconnect()
    {
        // get some options from settings
        $login = stgh_get_option('stg_mail_login');
        $password = stgh_get_option('mail_pwd');
        $encryption = stgh_get_option('mail_encryption', 'SSL');
        $ssl = $encryption == 'SSL' ? 1 : 0;
        $tls = $encryption == 'TLS' ? 1 : 0;
        $server = stgh_get_option('mail_server', false);
        $port = stgh_get_option('mail_port', false);
        $protocol = stgh_get_option('mail_protocol', 'pop3');
        $validateCert = false;
        $attachmentDir = __DIR__;

        $mailbox = Stg_Helpdesk_Mailbox::instance($server, $port, $login, $password, $attachmentDir, $ssl,
            $validateCert, $tls);


        $mailbox->disconnect($protocol);
    }
}

if (!function_exists('stgh_mailbox_check_connection')) {
    /**
     * Check connection
     *
     * @return bool
     */
    function stgh_mailbox_check_connection()
    {
        try {
            stgh_mailbox_is_imap_exist();
            $connection = stgh_mailbox_connect();

            $connection->checkMailbox();
        } catch (Exception $e) {
            return $e->getMessage();
        }

        return true;
    }
}

if (!function_exists('stgh_mailbox_is_imap_exist')) {
    function stgh_mailbox_is_imap_exist()
    {
        // Check PHP compiled with IMAP and IMAP compiled with SSL.
        if (!function_exists('imap_open')) {
            throw new \PhpImap\Exception(" IMAP doesn't exist. PHP must be compiled with IMAP enabled");
        }
        $encryption = stgh_get_option('mail_encryption', false);
        if ($encryption == 'SSL') {

            $phpimap = stgh_parse_phpextension('imap');
            if (isset($phpimap["SSL Support"]) && strtolower($phpimap["SSL Support"]) != 'enabled') {
                throw new \PhpImap\Exception(" PHP IMAP must be compiled with SSl enabled");
            }

        }
    }
}

if (!function_exists('stgh_mailbox_list_mail')) {
    function stgh_mailbox_list_mail($status)
    {
        try {
            // $status = strtoupper($status);
            $connection = stgh_mailbox_connect();

            return $connection->searchMailbox($status);
        } catch (Exception $e) {
            return false;
        }
    }
}

if (!function_exists('stgh_mailbox_list_unread')) {
    /**
     * get unread mails
     *
     * @return array|bool
     */
    function stgh_mailbox_list_unread()
    {
        return stgh_mailbox_list_mail('unseen');
    }
}

if (!function_exists('stgh_mailbox_list_all')) {
    /**
     * Get all mails
     *
     * @return array|bool
     */
    function stgh_mailbox_list_all()
    {
        return stgh_mailbox_list_mail('all');
    }
}

if (!function_exists('stgh_letter_get')) {
    /**
     * @param string $id
     * @param bool $delete
     * @return bool|\PhpImap\IncomingMail
     */
    function stgh_letter_get($id, $delete = true)
    {
        try {
            $connection = stgh_mailbox_connect();

            $mail = $connection->getMail($id);
            if ($delete) {
                $connection->deleteMail($id);
            }

            return $mail;
        } catch (Exception $e) {
            return false;
        }
    }
}

if (!function_exists('stgh_letter_is_ticket')) {
    /**
     * Check letter is ticket
     *
     * @param $id
     * @param bool $checkBySubject
     * @return bool
     */
    function stgh_letter_is_ticket($id, $checkBySubject = true)
    {
        return 'ticket' === stgh_letter_type($id, $checkBySubject);
    }
}

if (!function_exists('stgh_letter_is_comment')) {
    /**
     * Check letter is comment
     *
     * @param $id
     * @param bool $checkBySubject
     * @return bool
     */
    function stgh_letter_is_comment($id, $checkBySubject = true)
    {
        return 'comment' === stgh_letter_type($id, $checkBySubject);
    }
}

if (!function_exists('stgh_letter_check_ticket_by_subject')) {
    /**
     * @param $data
     * @param null $address
     * @return string
     */
    function stgh_letter_check_ticket_by_subject($data, $address = null)
    {
        global $wpdb;
        if (is_null($address)) {
            $letter = stgh_letter_get($data, false);
            if (false != $letter) {
                $subject = $letter->subject;
                $address = $letter->fromAddress;
            } else {
                return false;
            }
        } else {
            $subject = $data;
        }

        $subject = trim(preg_replace("/Re\:|re\:|RE\:|Fwd\:|fwd\:|FWD\:/i", '', $subject));
        $user = get_user_by('email', $address);

        if (!$user) {
            return false;
        }

        $sql = $wpdb->prepare("
			SELECT ID
			FROM $wpdb->posts
			WHERE post_title = %s
			AND post_author = %s
			AND post_type LIKE %s
			AND post_status NOT IN ('trash')
		", $subject, $user->ID, STG_HELPDESK_POST_TYPE . '%');

        return $wpdb->get_var($sql);
    }
}

if (!function_exists('stgh_letter_type')) {
    /**
     * Get ticket type
     *
     * @param $id
     * @param bool|true $checkBySubject
     * @return bool|string
     */
    function stgh_letter_type($id, $checkBySubject = true)
    {
        $letter = stgh_letter_get($id, false);

        if (false !== $letter) {
            $letterInfo = stgh_mailbox_connect()->getMailsInfo(array($id));

            if (!empty($letterInfo)) {
                $letterInfo = reset($letterInfo);

                $hasReference = isset($letterInfo->references) && isset($letterInfo->in_reply_to);

                $hasSubject = false;

                if ($checkBySubject) {
                    $hasSubject = stgh_letter_check_ticket_by_subject($letter->subject, $letter->fromAddress);
                }

                if ($hasReference || $hasSubject) {
                    if ($hasSubject && !$hasReference) {
                        return get_post_type($hasSubject) == STG_HELPDESK_COMMENTS_POST_TYPE ? 'comment' : 'ticket';
                    }

                    return 'comment';
                }

                return 'ticket';
            }
        }

        return false;
    }
}

if (!function_exists('stgh_letter_get_reference')) {
    /**
     * Get reference
     *
     * @param $id
     * @return bool|mixed
     */
    function stgh_letter_get_reference($id)
    {
        if (stgh_letter_is_comment($id)) {
            $letterInfo = stgh_mailbox_connect()->getMailsInfo(array($id));

            $letterInfo = reset($letterInfo);

            if (empty($letterInfo->references)) {
                return false;
            }

            preg_match('/<.+?>/i', $letterInfo->references, $match);
            if (empty($match)) {
                return false;
            }

            $reference = reset($match);

            return $reference;
        }

        return false;
    }
}

if (!function_exists('stgh_letter_get_in_reply_to')) {
    /**
     * Get in-reply-to
     *
     * @param $id
     * @return bool|mixed
     */
    function stgh_letter_get_in_reply_to($id)
    {
        if (stgh_letter_is_comment($id)) {
            $letterInfo = stgh_mailbox_connect()->getMailsInfo(array($id));

            $letterInfo = reset($letterInfo);

            if (empty($letterInfo->in_reply_to)) {
                return false;
            }

            preg_match('/<.+?>/i', $letterInfo->in_reply_to, $match);
            if (empty($match)) {
                return false;
            }

            $in_reply_to = reset($match);

            return $in_reply_to;
        }

        return false;
    }
}

if (!function_exists('stgh_letter_get_actual_body')) {
    /**
     * Get real message body
     *
     * @param \PhpImap\IncomingMail |string $data mail id
     * @return mixed|string
     */
    function stgh_letter_get_actual_body($data)
    {
        if ($data instanceof \PhpImap\IncomingMail) {
            if (isset($data->textPlain)) {
                $body = $data->textPlain;
            } else {
                return '';
            }
        } else {
            $body = $data;
        }

        // ---- this line and those below will be ignored ----
        $cut_line = stgh_get_option('stgh_email_cut_line', '---- this line and those below will be ignored ----');
        if ($cut_line) {
            $pos = strpos($body, $cut_line);
            if ($pos)
                $body = substr($body, 0, $pos - 1);
        }
        // Remove quoted lines (lines that begin with '>').
        $body = preg_replace("/(^\w.+:\n)?(^>.*(\n|$))+/mi", '', $body);
        // Remove lines beginning with 'On' and ending with 'wrote:' (matches
        // Mac OS X Mail, Gmail).
        $body = preg_replace("/^(On).*(wrote:).*$/sm", '', $body);
        // Remove lines like '----- Original Message -----' (some other clients).
        // Also remove lines like '--- On ... wrote:' (some other clients).
        $body = preg_replace("/^---.*$/mi", '', $body);
        // Remove lines like '____________' (some other clients).
        $body = preg_replace("/^____________.*$/mi", '', $body);
        // Remove blocks of text with formats like:
        //   - 'From: Sent: To: Subject:'
        //   - 'From: To: Sent: Subject:'
        //   - 'From: Date: To: Reply-to: Subject:'
        $body = preg_replace("/From:.*^(To:).*^(Subject:).*/sm", '', $body);
        // Remove any remaining whitespace.
        $body = trim($body);

        return $body;
    }
}


// TO DO
if (!function_exists('stgh_letter_get_author')) {
    /**
     * Identifies who sent the letter. If necessary, user registers.
     * Returns user id
     * @param $letter
     * @return bool|int
     */
    function stgh_letter_get_author($letter)
    {
        if (!$letter) {
            $log = Stg_Helper_Logger::getLogger();
            $log->log('Can\'t get letter author for letter');

            return false;
        }

        $email = $letter->fromAddress;

        $replyToArray = $letter->replyTo;

        if(is_array($replyToArray) && count($replyToArray) == 1)
        {
            $replyTo = current(array_keys($replyToArray));
        }

        if(!empty($replyTo))
            $email = $replyTo;

        $user = get_user_by('email', $email);
        $contactInfo = stgh_parse_letter_contact($letter);
        if (!$user) {
            // create user
            $userId = register_new_user_without_notification($email, $email);

            if ($userId instanceof \WP_Error) {
                $log = Stg_Helper_Logger::getLogger();
                $log->log("Errors while user creating " . var_export($userId->get_error_messages()));

                return false;
            }

            // set roles
            $user = new WP_User($userId);
            $user->set_role('stgh_client');

            //set meta crm
            update_user_meta($userId, 'first_name', $contactInfo['firstName']);
            update_user_meta($userId, 'last_name', $contactInfo['lastName']);
            add_user_meta($userId, '_stgh_crm_company', $contactInfo['company']);

            //set display name
            wp_update_user(array('ID' => $userId,'display_name' => $contactInfo['firstName']." ".$contactInfo['lastName']));

        } else {
            if (!get_user_meta($user->ID, '_stgh_crm_company', true)) {
                update_user_meta($user->ID, '_stgh_crm_company', $contactInfo['company']);
            }

            if (!get_user_meta($user->ID, 'first_name', true)) {
                update_user_meta($user->ID, 'first_name', $contactInfo['firstName']);
            }

            if (!get_user_meta($user->ID, 'last_name', true)) {
                update_user_meta($user->ID, 'last_name', $contactInfo['lastName']);
            }

        }

        return $user->ID;
    }
}

// TO DO
if (!function_exists('stgh_letter_save_attachments')) {
    /**
     * Save mail attacments
     * @param $letter
     * @param $id
     * @param int $parent_id
     */
    function stgh_letter_save_attachments($letter, $id, $parent_id = 0)
    {
        if (!$letter) {
            return false;
        }

        $attachments = $letter->getAttachments();

        if ($attachments && is_array($attachments)) {
            foreach ($attachments as $k => $item) {
                $file = array(
                    'name' => $item->name,
                    'type' => finfo_file(finfo_open(FILEINFO_MIME_TYPE), $item->filePath),
                    'tmp_name' => $item->filePath,
                    'size' => filesize($item->filePath),
                    'error' => 0,
                );

                Stg_Helper_UploadFiles::uploadFile($file, $id, $parent_id, 'wp_handle_local_upload');
            }
        }
    }
}

if (!function_exists('stgh_letter_get_parent_postId')) {
    /**
     * Get ticket parent id
     *
     * @param $id int Letter id
     * @return int Ticket id
     */
    function stgh_letter_get_parent_postId($id)
    {
        if (!stgh_letter_is_comment($id)) {
            return false;
        }

        global $wpdb;
        // parent message id
        $parent_messageId = stgh_letter_get_reference($id);
        if (!$parent_messageId) {
            return false;
        }

        // Get post_id from wp_postmeta
        $query = "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_stgh_references' AND meta_value LIKE ('%$parent_messageId%') LIMIT 1";
        $result = $wpdb->get_results($query);

        if (!isset($result[0]->post_id) || empty($result[0]->post_id)) {
            $in_reply_to = stgh_letter_get_in_reply_to($id);
            $query = "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_stgh_references' AND meta_value LIKE ('%$in_reply_to%') LIMIT 1";
            $result = $wpdb->get_results($query);
        }

        return isset($result[0]->post_id) ? $result[0]->post_id : null;
    }
}

if (!function_exists('stgh_letter_save')) {
    /**
     * Save ticket or comment
     * @param $letterId
     * @return bool|int|WP_Error
     */
    function stgh_letter_save($letterId)
    {
        // Letter
        $letter = stgh_letter_get($letterId, false);
        if (!$letter) {
            $log = Stg_Helper_Logger::getLogger();
            $log->log('I can\'t find letter with ID=' . $letterId . ', so I skip saving ticket.');
            return false;
        }


        $autoreply = false;
        $logString = "#{$letter->fromAddress} && ID:{$letter->id}: ignoring email with ";
        if ($letter->xAutoResponseSuppress == "all" || $letter->xAutoResponseSuppress == "autoreply" || $letter->xAutoResponseSuppress == "oof") {
            $logString .= "X-Auto-Response-Suppress: {$letter->xAutoResponseSuppress} header; ";
            $autoreply = true;
        }

        if ($letter->xAutoreply == "yes") {
            $logString .= "X-AutoReply:{$letter->xAutoreply} header; ";
            $autoreply = true;
        }

        if ($letter->autoSubmitted == "auto-replied" || $letter->autoSubmitted == "auto-generated") {
            $logString .= "Auto-Submitted:{$letter->autoSubmitted} header; ";
            $autoreply = true;

        }
        if ($autoreply) {
            $logger = Stg_Helper_Logger::getLogger('autoreplies');
            $logger->log($logString);
            return false;
        }


        // Author
        $author_id = stgh_letter_get_author($letter);

        if (false === $author_id) {
            $log = Stg_Helper_Logger::getLogger();
            $log->log('I can\'t create a user, so I skip saving ticket.');

            return false;
        }
        // Letter type
        $type = stgh_letter_type($letterId);
        // Letter content
        $content = stgh_letter_get_actual_body($letter);

        $res = false;
        $res = false;
        if ($type == 'comment') { // saveComment
            $post = array();
            // Ticket id
            $post['ticket_id'] = stgh_letter_get_parent_postId($letterId);
            $post['stg_messsageId'] = $letter->messageId;
            $post['stgh_user_comment'] = $content;
            $post['stgh_userid_comment'] = $author_id;
            if (!$post['ticket_id']) {
                $type = 'ticket';
            } else {
                // If ticket is closed - it necessary to open
                $ticket = Stg_Helpdesk_Ticket::getInstance($post['ticket_id']);
                if ($ticket->isClosed()) {
                    $ticket->open($post['ticket_id']);
                }
                $res = Stg_Helpdesk_TicketComments::saveCommentPublic($post);
            }
        }
        if ($type == 'ticket') { // saveTicket
            $post = array();
            $post['stg_ticket_subject'] = $letter->subject;
            $post['stg_ticket_message'] = $content;
            $post['stg_ticket_author'] = $author_id;
            $post['stg_messsageId'] = $letter->messageId;
            $res = Stg_Helpdesk_Ticket::saveTicket($post, $author_id, true);
        }

        if (!empty($letter->cc) && is_array($letter->cc)) {
            $cc = implode(", ", array_keys($letter->cc));
            $cc && add_post_meta($res, '_stgh_reply_cc', $cc, true);
        }

        if ($res) {
            add_post_meta($res, '_stgh_type_source', stgh_get_option('stg_mail_login'), true);
        }

        return $res;
    }
}

if (!function_exists('stgh_letter_to_post')) {
    /*
     * Save letter as post
     */
    function stgh_letter_to_post($letterId)
    {
        $id = stgh_letter_save($letterId);
        if ($id) {
            $letter = stgh_letter_get($letterId, false);
            $type = stgh_letter_type($letterId);
            $parent_id = ($type == 'comment' ? stgh_letter_get_parent_postId($letterId) : 0);
            stgh_letter_save_attachments($letter, $id, $parent_id);

            // remove letter
            stgh_letter_get($letterId, true);

            //email
            if ($type == 'comment') {
                $assign = intval(get_post_meta($parent_id, '_stgh_assignee', true));
                $author = intval(get_post($id)->post_author);

                if ($assign != $author) {
                    Stg_Helper_Email::sendEventTicket('client_reply', $id);
                } else {
                    Stg_Helper_Email::sendEventTicket('agent_reply', $id);
                }
            } else {
                Stg_Helper_Email::sendEventTicket('ticket_open', $id);
                Stg_Helper_Email::sendEventTicket('ticket_assign', $id);
            }

        }
    }
}

if (!function_exists('stgh_letters_handler')) {
    /**
     * Letter handler
     * Get unread letters and save it as post
     */
    function stgh_letters_handler()
    {
        try {
            stgh_mailbox_is_imap_exist();
        } catch (Exception $e) {
            return array('status' => false, 'msg' => $e->getMessage());
        }

        $lettersIDs = stgh_mailbox_list_unread();
        if (is_array($lettersIDs)) {
            $amount = 0;
            if ($lettersIDs) {
                foreach ($lettersIDs as $letterId) {
                    stgh_letter_to_post($letterId);
                    $amount++;
                }
            }
            return array('status' => true, 'amount' => $amount);
        }

        return array('status' => false);
    }
}

if (!function_exists('stgh_parse_letter_contact')) {
    /**
     * Email parsing
     *
     * @param IncomingMail $letter
     * @return array
     */
    function stgh_parse_letter_contact($letter)
    {
        $contact = array();
        $contact['email'] = $letter->fromAddress;

        if (empty($letter->fromName)) {
            $names = preg_replace('/@.*$/i', '', $contact['email']);
            $names = explode('.', $names);
        } else {
            $names = explode(' ', $letter->fromName);
        }

        $names = array_map('ucfirst', $names);
        $contact['firstName'] = array_shift($names);
        $contact['lastName'] = implode(' ', $names);

        $contact['company'] = strtolower($letter->fromAddress);

        preg_match('/\((.*)\)/i', $contact['lastName'], $companyMatches);

        if (!empty($companyMatches)) {
            $contact['lastName'] = trim(substr($contact['lastName'], 0, strpos($contact['lastName'], '(')));
            $contact['company'] = $companyMatches[1];
        }

        if (false !== $pos = strpos($contact['firstName'], ',')) {
            $lastName = trim(substr($contact['firstName'], 0, $pos));
            $contact['firstName'] = $contact['lastName'];
            $contact['lastName'] = $lastName;
        }

        return $contact;
    }
}
if (!function_exists('register_new_user_without_notification')) {
    function register_new_user_without_notification($user_login, $user_email)
    {
        remove_all_filters('registration_errors');

        $errors = new \WP_Error();

        $sanitized_user_login = sanitize_user($user_login);
        /**
         * Filter the email address of a user being registered.
         *
         * @since 2.1.0
         *
         * @param string $user_email The email address of the new user.
         */
        $user_email = apply_filters('user_registration_email', $user_email);

        // Check the username
        if ($sanitized_user_login == '') {
            $errors->add('empty_username', __('<strong>ERROR</strong>: Please enter a username.'));
        } elseif (!validate_username($user_login)) {
            $errors->add('invalid_username', __('<strong>ERROR</strong>: This username is invalid because it uses illegal characters. Please enter a valid username.'));
            $sanitized_user_login = '';
        } elseif (username_exists($sanitized_user_login)) {
            $errors->add('username_exists', __('<strong>ERROR</strong>: This username is already registered. Please choose another one.'));
        }

        // Check the e-mail address
        if ($user_email == '') {
            $errors->add('empty_email', __('<strong>ERROR</strong>: Please type your e-mail address.'));
        } elseif (!is_email($user_email)) {
            $errors->add('invalid_email', __('<strong>ERROR</strong>: The email address isn&#8217;t correct.'));
            $user_email = '';
        } elseif (email_exists($user_email)) {
            $errors->add('email_exists', __('<strong>ERROR</strong>: This email is already registered, please choose another one.'));
        }

        /**
         * Fires when submitting registration form data, before the user is created.
         *
         * @since 2.1.0
         *
         * @param string $sanitized_user_login The submitted username after being sanitized.
         * @param string $user_email The submitted email.
         * @param WP_Error $errors Contains any errors with submitted username and email,
         *                                       e.g., an empty field, an invalid username or email,
         *                                       or an existing username or email.
         */
        do_action('register_post', $sanitized_user_login, $user_email, $errors);

        /**
         * Filter the errors encountered when a new user is being registered.
         *
         * The filtered WP_Error object may, for example, contain errors for an invalid
         * or existing username or email address. A WP_Error object should always returned,
         * but may or may not contain errors.
         *
         * If any errors are present in $errors, this will abort the user's registration.
         *
         * @since 2.1.0
         *
         * @param WP_Error $errors A WP_Error object containing any errors encountered
         *                                       during registration.
         * @param string $sanitized_user_login User's username after it has been sanitized.
         * @param string $user_email User's email.
         */
        $errors = apply_filters('registration_errors', $errors, $sanitized_user_login, $user_email);

        if ($errors->get_error_code())
            return $errors;

        $user_pass = wp_generate_password(12, false);
        $user_id = wp_create_user($sanitized_user_login, $user_pass, $user_email);
        if (!$user_id || is_wp_error($user_id)) {
            $errors->add('registerfail', sprintf(__('<strong>ERROR</strong>: Couldn&#8217;t register you&hellip; please contact the <a href="mailto:%s">webmaster</a> !'), get_option('admin_email')));
            return $errors;
        }

        update_user_option($user_id, 'default_password_nag', true, true); //Set up the Password change nag.\

        return $user_id;
    }

    if ( ! function_exists ( 'stgh_init_smtp' ) ) {
        function stgh_init_smtp( $phpmailer ) {
            $sender = Stg_Helper_Email::getSender();

            $phpmailer->IsSMTP();
            $from_email = $sender['from_email'];;
            $phpmailer->From = $from_email;
            $from_name  = $sender['from_name'];;
            $phpmailer->FromName = $from_name;

            $phpmailer->SetFrom($phpmailer->From, $phpmailer->FromName);

            if ( stgh_get_option('mail_sender_encryption') !== '' ) {
                $phpmailer->SMTPSecure = strtolower(stgh_get_option('mail_sender_encryption'));
            }


            $phpmailer->Host = stgh_get_option('mail_sender_server');
            $phpmailer->Port = stgh_get_option('mail_sender_port');

            $phpmailer->SMTPAuth = true;
            $phpmailer->Username = stgh_get_option('mail_sender_login');
            $phpmailer->Password = stgh_get_option('mail_sender_pwd');

            $phpmailer->SMTPAutoTLS = false;
        }
    }
}



