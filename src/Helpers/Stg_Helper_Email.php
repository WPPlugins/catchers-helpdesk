<?php
namespace StgHelpdesk\Helpers;

use StgHelpdesk\Ticket\Stg_Helpdesk_Ticket;

/**
 * Class Stg_Helper_Email
 * @package StgHelpdesk\Helpers
 */
class Stg_Helper_Email
{
    private static $ticket_id;
    private static $need_footer_notifications = array('agent_reply');

    /**
     * Send email on registered event plugins
     * EVENTS
     * -> user notify:
     * 'ticket_closed',
     * 'ticket_open',
     * 'agent_reply'
     *
     * -> agent notify:
     * 'client_reply',
     * 'ticket_assign'
     *
     * @param $event
     * @param $post_id
     * @return array|bool
     */
    public static function sendEventTicket($event, $post_id)
    {
        if (!in_array(get_post_type($post_id), array(STG_HELPDESK_POST_TYPE, STG_HELPDESK_COMMENTS_POST_TYPE))) {
            return array(
                'status' => false,
                'Notification failed. The post ID provided does not match any of the plugin post types'
            );
        }

        if (!self::checkNotificationEvent($event) || !self::isActive($event)) {
            return array(
                'status' => false,
                'msg' => 'Notification failed. The requested notification does not exist or disabled'
            );
        }

        if (STG_HELPDESK_POST_TYPE === get_post_type($post_id)) {
            $ticket_id = $post_id;
        } else {
            $reply = self::getPostData($post_id);
            $ticket_id = $reply->post_parent;
        }

        //ADD ACTION phpmailer
        self::$ticket_id = $ticket_id;
        /*add_action('phpmailer_init', function ($phpmailer) {
            self::phpmailer_init($phpmailer);
        });*/

        $headers = self::getHeaders(false, $event);

        $refs = self::getCustomHeaders();
        $headers = array_merge($headers, $refs);

        $emailTo = get_post_meta($post_id, '_stgh_reply_to', true);
        $emailNotify = $emailTo ? $emailTo : self::getEmailNotifyTicket($event, $ticket_id);


        if (!$emailNotify) {
            return array(
                'status' => false,
                'msg' => 'Notification failed. User for notification not found.'
            );
        }

        $params = apply_filters('stg_email_notifications_email', array(
            'to' => $emailNotify,
            'subject' => self::getSubject($event, $post_id, $ticket_id),
            'body' => self::getBody($event, $post_id, $ticket_id),
            'headers' => $headers,
            'attachments' => ''
        ), $event);

        return self::send($params, $post_id);
    }

    /**
     * Send email
     * @param $params
     * @return bool
     */
    public static function send($params, $post_id = null)
    {
        if(stgh_get_option('smtp_settings_enabled'))
        {
            remove_all_actions('phpmailer_init');
            add_action( 'phpmailer_init','stgh_init_smtp');
        }

        $result = wp_mail($params['to'], $params['subject'], $params['body'],
            $params['headers'] ? $params['headers'] : '', $params['attachments'] ? $params['attachments'] : array());

        if ($result) {
            return array('status' => true);
        } else {
            return array('status' => false);
        }
    }

    /**
     * Set the email content type to HTML
     * @return string
     */
    public static function set_html_mime_type()
    {
        return 'text/html';
    }

    /**
     * Set the email content type to plain text
     * @return string
     */
    public static function set_text_mime_type()
    {
        return 'text/plain';
    }

    /**
     * @param $author
     * @return bool|string
     */
    public static function getNewMessageId($author)
    {
        if (!$author) {
            return false;
        }

        /*if (stgh_get_option('stg_enabled_mail_feedback', false)) {
            $email = stgh_get_option('stg_mail_login', get_bloginfo('admin_email'));
        } else {*/
        $email = stgh_get_option('stg_mail_login', get_bloginfo('admin_email'));
        //}

        $domain = substr(strrchr($email, "@"), 1);

        return '<' . md5('stg_' . time() . $author) . '@' . $domain . '>';
    }


    /** private **/

    /**
     * Action Phpmailer_init - add MessageID
     *
     * @param $phpmailer
     */
    /*private static function phpmailer_init(&$phpmailer)
    {
        if(!empty(self::$ticket_id)) {
            $references = Stg_Helpdesk_Ticket::getReferences(self::$ticket_id);
            $last_id = get_post_meta(self::$ticket_id, '_stgh_msg_lastid', true);

            if ('' !== $references) {
                $phpmailer->addCustomHeader("References: " . $references);
            }

            if ('' !== $last_id) {
                $phpmailer->addCustomHeader("In-Reply-To: " . $last_id);
            }
        }
    }*/

    /**
     * Fill headers References and in-Reply-To
     *
     * @return array
     */
    private static function getCustomHeaders()
    {
        $result = array();
        if (!empty(self::$ticket_id)) {
            $references = Stg_Helpdesk_Ticket::getReferences(self::$ticket_id);
            $last_id = get_post_meta(self::$ticket_id, '_stgh_msg_lastid', true);

            if ('' !== $references) {
                $result[] = "References: " . $references;
            }

            if ('' !== $last_id) {
                $result[] = "In-Reply-To: " . $last_id;
            }
        }
        return $result;
    }

    /**
     * Get available notification cases.
     * @return mixed|void
     */
    private static function getEvents()
    {
        $events = array(
            'client_reply',
            'agent_reply',
            'ticket_open',
            'ticket_assign'
        );

        return apply_filters('stg_email_notifications_cases', $events);
    }

    /**
     * Get email notify
     * @param $event
     * @param $ticket_id
     * @return string
     */
    private static function getEmailNotifyTicket($event, $ticket_id)
    {
        $user = NULL;
        switch ($event) {
            //user notify
            case 'ticket_open':
            case 'agent_reply':
                $ticket = \StgHelpdesk\Ticket\Stg_Helpdesk_Ticket::getInstance($ticket_id);
                $user = $ticket->getContact();
                break;
            //agent notify
            case 'client_reply':
            case 'ticket_assign':
                $user = get_user_by('id', intval(get_post_meta($ticket_id, '_stgh_assignee', true)));

                if (!$user) {
                    $users_query = new \WP_User_Query(array(
                        'role' => 'administrator',
                        'orderby' => 'display_name',
                        'limit' => '1',
                    ));
                    $results = $users_query->get_results();
                    if (current($results) instanceof \WP_User) {
                        $user = current($results);
                    }
                }
                break;
        }

        if (!$user) {
            return false;
        }

        return $user->user_email;
    }

    /**
     * Get the post object
     * @param $post_id
     * @return array|bool|null|\WP_Post
     */
    private static function getPostData($post_id)
    {
        if (STG_HELPDESK_POST_TYPE !== get_post_type($post_id) && STG_HELPDESK_COMMENTS_POST_TYPE !== get_post_type($post_id)) {
            return false;
        }
        return get_post($post_id);
    }


    /**
     * Generate tracking image
     * @param $post_id
     * @return bool|string
     */

    private static function getTrackingImage($post_id)
    {
        if (STG_HELPDESK_COMMENTS_POST_TYPE !== get_post_type($post_id) && STG_HELPDESK_POST_TYPE !== get_post_type($post_id)) {
            return false;
        }


        if(!stgh_get_option('open_tracking',false))
            return false;

        add_post_meta($post_id, '_stgh_post_read', false, true);

        $hash = md5($post_id."//".STG_HELPDESK_SALT_USER);

        add_post_meta($post_id, '_stgh_post_read_hash', $hash, true);

        return "<img src='".get_site_url()."/?stgh-do=tracking_image&postid=".$post_id."&sh=".$hash."' border='0'/>";
    }




    /**
     * Get content for tags value message
     * @param $post_id
     * @return bool
     */
    private static function getContentTagsValue($post_id)
    {
        if (STG_HELPDESK_COMMENTS_POST_TYPE !== get_post_type($post_id) && STG_HELPDESK_POST_TYPE !== get_post_type($post_id)) {
            return false;
        }

        $postObject = get_post($post_id);

        $content = $postObject->post_content;

        $attachments = Stg_Helper_UploadFiles::getAttachments($post_id);

        $attachmentsList = '';
        if ($attachments) {
            $attachmentsList = __('Attachments', STG_HELPDESK_TEXT_DOMAIN_NAME) . ':<br>';
            foreach ($attachments as $attachment_id => $attachment) {
                $filename = explode('/', $attachment['url']);
                $filename = htmlspecialchars($filename[count($filename) - 1]);

                //type ticket-attachment
                $upload_dir = wp_upload_dir();
                $filepath = trailingslashit($upload_dir['basedir']) . 'stg-helpdesk/ticket_' . get_the_ID() . '/' . $filename;
                $filesize = file_exists($filepath) ? Stg_Helper_UploadFiles::getHumanFilesize(filesize($filepath), 1) : '';
                $link = add_query_arg(array('ticket-attachment' => $attachment['id']), home_url());

                //link key
                $client = get_user_by('id', self::getPostData($post_id)->post_author);
                //$ticket = \StgHelpdesk\Ticket\Stg_Helpdesk_Ticket::getInstance($post_id);
                //$client = $ticket->getContact();
                $key = md5($client->user_email . STG_HELPDESK_SALT_USER);
                $link = add_query_arg(array('uid' => $client->ID, 'key' => $key), $link);

                //type base
                //$link = $attachment['url'];

                $attachmentsList .= "<a href=\"{$link}\" target=\"_blank\">{$filename}</a><br>";
            }
        }

        //$attachmentsList = Stg_Helper_Template::getTemplate('stg-attachements-block', array('attachments' => $attachments), false);

        return $content . '<br >' . $attachmentsList;
    }

    /**
     * Get email subject
     * @param $event
     * @param $post_id
     * @param $ticket_id
     * @return mixed|void
     */
    private static function getSubject($event, $post_id, $ticket_id)
    {
        return apply_filters('stg_email_notifications_subject',
            self::getContent('subject', $event, $post_id, $ticket_id), $post_id);
    }

    /**
     * Get email body
     * @param $event
     * @param $post_id
     * @param $ticket_id
     * @return mixed|void
     */
    private static function getBody($event, $post_id, $ticket_id)
    {
        $body = apply_filters('stg_email_notifications_body',
            stripcslashes(self::getContent('content', $event, $post_id, $ticket_id)), $post_id);

        
        $body = self::getCutLine() . $body;

        
        return $body;
    }

    private static function getCutLine()
    {
        $cut_line = stgh_get_option('stgh_email_cut_line', '');
        if ($cut_line) {
            $cut_line = '<span style="font-size: 9.5pt; font-family: \'Verdana\', \'sans-serif\'; color: #c1c1c1">' .
                $cut_line . '</span><br>';
        }
        return $cut_line;
    }

    
    /**
     * Get email content from options
     * @param $field
     * @param $event
     * @param $post_id
     * @param $ticket_id
     * @return bool|mixed
     */
    private static function getContent($field, $event, $post_id, $ticket_id)
    {
        if (!in_array($field, array('subject', 'content'))) {
            return false;
        }

        $template = '';
        if ($field == 'subject')
            $template = stgh_get_option("ticket_subject", __('Re: {ticket_title}', STG_HELPDESK_TEXT_DOMAIN_NAME));
        else {
            switch ($event) {
                case 'ticket_open':
                    //$template = Stg_Helper_Template::getTemplate('mails/ticket_accepted', array(), false);

                    //$template = stgh_get_option('content_auto_reply','');
                    $template = Stg_Helper_Template::getTemplate('mails/ticket_open', array(), false);
                    break;
                case 'client_reply':
                    $template = Stg_Helper_Template::getTemplate('mails/reply_client', array(), false);
                    break;
                case 'agent_reply':
                    $template = Stg_Helper_Template::getTemplate('mails/reply_agent', array(), false);
                    break;
                case 'ticket_assign':
                    $template = Stg_Helper_Template::getTemplate('mails/ticket_assign', array(), false);
                    break;
            }
        }

        return self::replaceTemplateTags($template, $post_id, $ticket_id);
    }

    /**
     * Replace template tags on value
     * @param $template
     * @param $post_id
     * @param $ticket_id
     * @return mixed
     */
    private static function replaceTemplateTags($template, $post_id, $ticket_id)
    {
        $data = self::getValuesTemplate($post_id, $ticket_id);
        foreach ($data as $item) {
            $tag = $item['tag'];
            $value = $item['value'];
            $template = str_ireplace($tag, $value, $template);
        }

        return $template;
    }

    /**
     * Get ticket url
     *
     * @param \WP_Post $post
     * @return string|void
     */
    private static function getTicketUrl($post)
    {
        return home_url('/' . STG_HELPDESK_SLUG . '/' . $post->post_name);
    }

    /**
     * Get tags and values content template
     * @param $post_id
     * @param $ticket_id
     * @return mixed|void
     */
    private static function getValuesTemplate($post_id, $ticket_id)
    {
        $tags = self::getTagsTemplate();
        $result = array();

        $user_id = get_post_meta($ticket_id, '_stgh_assignee', true);
        if (empty($user_id)) {
            $user_id = stgh_get_option('stgh_assignee_default', 1);
        }

        $user = get_user_by('id', (int)$user_id);
        $userIdContact = get_post_meta($ticket_id, '_stgh_contact', true);
        $clientEmail = get_the_author_meta('email', $userIdContact);

        //permalink key
        $permalink = get_post_permalink($ticket_id);
        $key = md5($clientEmail . STG_HELPDESK_SALT_USER);
        $permalink = add_query_arg(array('uid' => $userIdContact, 'key' => $key, 'r' => time()), $permalink);

        $adminUrl = add_query_arg(array('post' => $ticket_id, 'action' => 'edit'), admin_url('post.php'));

        $agentTicket = get_post_meta( $post_id, '_stgh_ticket_author_agent', true);

        foreach ($tags as $key => $tag) {
            $name = trim($tag['tag'], '{}');
            switch ($name) {
                case 'message_options';
                    if($agentTicket == 1){
                        //$tag['value'] = stgh_get_option('content_auto_reply_agent', '');
                        $tag['value'] = stgh_get_option('content_auto_reply', '');
                    }else{
                        $tag['value'] = stgh_get_option('content_auto_reply', '');
                    }
                    break;
                case 'ticket_id';
                    $tag['value'] = $ticket_id;
                    break;
                case 'site_name':
                    $tag['value'] = get_bloginfo('name');
                    break;
                case 'agent_name':
                    $tag['value'] = $user->display_name;
                    break;
                case 'agent_email':
                    $tag['value'] = $user->user_email;
                    break;
                case 'client_name':
                    $company = get_the_author_meta('_stgh_crm_company', $userIdContact);
                    $tag['value'] = stgh_crm_get_user_full_name($userIdContact) . (!empty($company) ? ' (' . $company . ')' : '');
                    break;
                case 'client_email':
                    $tag['value'] = $clientEmail;
                    break;
                case 'ticket_title':
                    $tag['value'] = wp_strip_all_tags(self::getPostData($ticket_id)->post_title);
                    break;
                case 'ticket_link':
                    $tag['value'] = '<a href="' . $permalink . '">' . $permalink . '</a>';
                    break;
                case 'ticket_url':
                    $tag['value'] = $permalink;
                    break;
                case 'ticket_admin_link':
                    $tag['value'] = '<a href="' . $adminUrl . '">' . $adminUrl . '</a>';
                    break;
                case 'ticket_admin_url':
                    $tag['value'] = $adminUrl;
                    break;
                case 'date':
                    $tag['value'] = date(get_option('date_format'));
                    break;
                case 'admin_email':
                    $tag['value'] = get_bloginfo('admin_email');
                    break;
                case 'message':
                    $tag['value'] = $ticket_id === $post_id ? self::getContentTagsValue($ticket_id) : self::getContentTagsValue($post_id);

                    $tag['value'] = str_ireplace('<blockquote>','<blockquote style=\'font-style: italic; border-left: 3px solid #e0e0e0; padding-left: 0.6em;margin-left: 2.4em;\'>',$tag['value']);
                    break;
                case 'tracking_image':
                    $tag['value'] = !self::getTrackingImage($post_id)? "":self::getTrackingImage($post_id);
                    break;
            }
            array_push($result, $tag);
        }

        $tags = apply_filters('stg_email_notifications_tags_values', $result, $post_id);
        return $tags;
    }

    /**
     * @return mixed|void
     */
    private static function getTagsTemplate()
    {

        $tags = array(
            array(
                'tag' => '{message_options}',
                'desc' => 'Message options from general settings'
            ),
            array(
                'tag' => '{ticket_id}',
                'desc' => 'Converts into ticket ID'
            ),
            array(
                'tag' => '{site_name}',
                'desc' => 'Converts into website name'
            ),
            array(
                'tag' => '{agent_name}',
                'desc' => 'Converts into agent name'
            ),
            array(
                'tag' => '{agent_email}',
                'desc' => 'Converts into agent e-mail address'
            ),
            array(
                'tag' => '{client_name}',
                'desc' => 'Converts into client name'
            ),
            array(
                'tag' => '{client_email}',
                'desc' => 'Converts into client e-mail address'
            ),
            array(
                'tag' => '{ticket_title}',
                'desc' => 'Converts into current ticket title'
            ),
            array(
                'tag' => '{ticket_link}',
                'desc' => 'Displays a link to public ticket'
            ),
            array(
                'tag' => '{ticket_url}',
                'desc' => 'Displays the URL only (not a link link) to public ticket'
            ),
            array(
                'tag' => '{ticket_admin_link}',
                'desc' => 'Displays a link to ticket details in admin (for agents)'
            ),
            array(
                'tag' => '{ticket_admin_url}',
                'desc' => 'Displays the URL only (not a link link) to ticket details in admin (for agents)'
            ),
            array(
                'tag' => '{date}',
                'desc' => 'Converts into current date'
            ),
            array(
                'tag' => '{admin_email}',
                'desc' => 'Converts into WordPress admin e-mail currently'
            ),
            array(
                'tag' => '{message}',
                'desc' => 'Converts into ticket content or reply content'
            ),
            array(
                'tag' => '{tracking_image}',
                'desc' => 'Tracking image'
            )
        );

        return apply_filters('stg_email_notifications_template_tags', $tags);

    }

    /**
     * Get sender data
     * @return mixed|void
     */
    public static function getSender()
    {
        $data = array();
        $data['from_name'] = stgh_get_option('sender_name', get_bloginfo('name'));

        $data['reply_name'] = $data['from_name'];
        $data['from_email'] = stgh_get_option('stg_mail_login', get_bloginfo('admin_email'));

        if(stgh_get_option('smtp_settings_enabled')) {
            $data['from_email'] = stgh_get_option('new_sender_email');
        }

        /*if (stgh_get_option('stg_enabled_mail_feedback', false) && stgh_mailbox_check_connection()) {
            $data['reply_email'] = stgh_get_option('stg_mail_login', get_bloginfo('admin_email'));
        } else */
        $data['reply_email'] = $data['from_email'];

        return apply_filters('stg_email_notifications_sender_data', $data);
    }

    /**
     * Get headers data
     */
    public static function getHeaders($sender = false, $event = false)
    {
        if (!$sender) {
            $sender = self::getSender();
        } else {
            if (!isset($sender['from_name']))
                $sender['from_name'] = '';
            if (!isset($sender['from_email']))
                $sender['from_email'] = '';
            if (!isset($sender['reply_name']))
                $sender['reply_name'] = '';
            if (!isset($sender['reply_email']))
                $sender['reply_email'] = '';
        }
        if (isset($_POST["stgh-cc"])) {
            $stghCc = $_POST["stgh-cc"];
        } else
            $stghCc = "";

        if (isset($_POST["stgh-bcc"])) {
            $stghBcc = $_POST["stgh-bcc"];
        } else
            $stghBcc = "";


        $result = array(
            "MIME-Version: 1.0",
            "Content-type: text/html; charset=utf-8",
            "From: {$sender['from_name']} <{$sender['from_email']}>",
            "Reply-To: {$sender['reply_name']} <{$sender['reply_email']}>",
            "X-Mailer: Stg Helpdesk/" . STG_HELPDESK_VERSION,
            "Cc: {$stghCc}",
            "Bcc:{$stghBcc}",
        );

        switch ($event) {
            case "ticket_open":
                $result[] = "X-Auto-Response-Suppress: OOF";
                $result[] = "Auto-Submitted: auto-generated";
                $result[] = "X-AutoReply => yes";
                break;

            default:
        }

        return $result;
    }

    /**
     * Check notification event exists.
     * @param $event
     * @return bool
     */
    private static function checkNotificationEvent($event)
    {
        $events = self::getEvents();
        return !in_array($event, $events) ? false : true;
    }

    /**
     * Get notification active option event
     * @return mixed|void
     */
    private static function getActiveEvent()
    {
        $events = self::getEvents();
        //agent notify
        $events['client_reply'] = 'enable_reply_client';
        $events['ticket_assign'] = 'enable_ticket_assign';
        //client notify
        $events['agent_reply'] = 'enable_reply_agent';
        $events['ticket_open'] = 'enable_open';

        return apply_filters('stg_email_notifications_events_active_option', $events);
    }

    /**
     * Check enable event
     * @param $event
     * @return bool
     */
    private static function isActive($event)
    {
        if (!self::checkNotificationEvent($event)) {
            return false;
        }

        $options = self::getActiveEvent();
        if (!array_key_exists($event, $options)) {
            return false;
        }

        if ($event == 'ticket_assign' && !isset($_POST['stgh_assignee_sub']) && !stgh_get_option('assignee_default', false)) {
            return false;
        }
        if ($event == 'agent_reply') {
            return true;
        }

        $option = $options[$event];

        return stgh_get_option($option, false) ? true : false;
    }

}