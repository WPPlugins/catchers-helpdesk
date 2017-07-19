<?php

namespace StgHelpdesk\Ticket;

class Stg_Helpdesk_Admin_Ticket_Layout
{

    protected static $instance;

    public static function instance()
    {
        if (is_null(static::$instance)) {
            static::$instance = new static;
        }

        return static::$instance;
    }

    private function __construct()
    {
        $this->removePublishingBox();
        //$this->removeTags();
        $this->removeSavedReply();
        $this->removeCustomForms();
        $this->removeCustomFields();
        //$this->tryToRemoveTitle();
        $this->removeCategories();
        $this->addCustomBoxes();
    }

    protected function removeCategories()
    {
        remove_meta_box('tagsdiv-ticket_category', STG_HELPDESK_POST_TYPE, 'side');
    }

    protected function tryToRemoveTitle()
    {
        if ($this->ticketExists()) {
            remove_post_type_support(STG_HELPDESK_POST_TYPE, 'title');
        }

    }

    protected function removeTags()
    {
        remove_meta_box('tagsdiv-ticket_tag', STG_HELPDESK_POST_TYPE, 'side');
    }

    protected function removeSavedReply()
    {
        remove_meta_box('tagsdiv-savedreply', STG_HELPDESK_POST_TYPE, 'side');
    }

    protected function removeCustomForms()
    {
        remove_meta_box('tagsdiv-customforms', STG_HELPDESK_POST_TYPE, 'side');
    }

    protected function removeCustomFields()
    {
        remove_meta_box('tagsdiv-customfields', STG_HELPDESK_POST_TYPE, 'side');
    }

    /**
     * Remove publication block
     */
    protected function removePublishingBox()
    {
        remove_meta_box('submitdiv', STG_HELPDESK_POST_TYPE, 'side');
    }

    /**
     * Adding custom block
     */
    protected function addCustomBoxes()
    {
        global $post;

        if ($this->ticketExists()) {

            $this->addTicketDetails();
            $this->addTicketCRM();
            $this->addRelatedTickets();
            
            add_meta_box('stgh-added-info', 'Added', array($this, 'addBox'),
                STG_HELPDESK_POST_TYPE, 'normal', 'high', array('template' => 'added-info'));

            add_meta_box('stgh-comments-form', __('Reply to ticket', STG_HELPDESK_TEXT_DOMAIN_NAME), array($this, 'addBox'),
                STG_HELPDESK_POST_TYPE, 'normal', 'high', array('template' => 'comments-form'));

            $count = stgh_ticket_count_answers(get_the_ID());

            if($post->post_content != '')
            {
                $count++;
            }

            $commentsTitle = __(sprintf('Conversation (%d)', $count), STG_HELPDESK_TEXT_DOMAIN_NAME);

            
            add_meta_box('stgh-comments', $commentsTitle, array($this, 'addBox'),
                STG_HELPDESK_POST_TYPE, 'normal', 'high', array('template' => 'comments'));
        }
        
    }

    /**
     * Adding a block ticket information
     */
    protected function addTicketDetails()
    {
        add_meta_box('stgh_ticket_details', __('Details', STG_HELPDESK_TEXT_DOMAIN_NAME), array($this, 'addBox'),
            STG_HELPDESK_POST_TYPE,
            'side', 'high', array('template' => 'details'));
    }

    protected function addTicketDetailsAgent()
    {
        add_meta_box('stgh_ticket_details', __('Details', STG_HELPDESK_TEXT_DOMAIN_NAME), array($this, 'addBox'),
            STG_HELPDESK_POST_TYPE,
            'side', 'high', array('template' => 'details-agent'));
    }



    /**
     * Adding a block user information
     */
    protected function addTicketCRM()
    {
        add_meta_box('stgh_ticket_crm', __('Contact', STG_HELPDESK_TEXT_DOMAIN_NAME), array($this, 'addBox'),
            STG_HELPDESK_POST_TYPE,
            'side', 'high', array('template' => 'user_crm_profile'));
    }


    protected function addTicketNotifyAgent()
    {
        add_meta_box('stgh_ticket_crm', __('Sending options', STG_HELPDESK_TEXT_DOMAIN_NAME), array($this, 'addBox'),
            STG_HELPDESK_POST_TYPE,
            'side', 'high', array('template' => 'notify-agent'));
    }

    /**
     * Adding related ticket block
     */
    protected function addRelatedTickets()
    {
        global $post;

        $ticket = Stg_Helpdesk_Ticket::getInstance($post->ID);
        $contact = $ticket->getContact();

        if($contact)
        {
            $posts = stgh_ticket_get_related_tickets($contact->ID, $post->ID);

            if ($posts->have_posts()) {
                add_meta_box('stgh_ticket_related', __('Previous conversations', STG_HELPDESK_TEXT_DOMAIN_NAME), array($this, 'addBox'),
                    STG_HELPDESK_POST_TYPE,
                    'side', 'default', array('template' => 'related_tickets'));
            }
        }

    }

    protected function addCustomFields()
    {
        add_meta_box('stgh_customfields', __('Custom fields', STG_HELPDESK_TEXT_DOMAIN_NAME), array($this, 'addBox'),
            STG_HELPDESK_POST_TYPE,
            'side', 'default', array('template' => 'custom_fields'));
    }

    protected function addCustomFieldsDefault()
    {
        add_meta_box('stgh_customfields', __('Custom fields', STG_HELPDESK_TEXT_DOMAIN_NAME), array($this, 'addBox'),
            STG_HELPDESK_POST_TYPE,
            'side', 'default', array('template' => 'custom_fields_default'));
    }

    /**
     * Ticket information
     *
     * @param $post
     * @param $args
     */
    public function addBox($post, $args)
    {
        if (!is_array($args) || !isset($args['args']['template'])) {
            _e('An error occurred while registering this metabox. Please contact the support.', STG_HELPDESK_TEXT_DOMAIN_NAME);
        }

        $template = $args['args']['template'];
        $template = STG_HELPDESK_PATH . "Admin/metaboxes/$template.php";

        if (!file_exists($template)) {
            _e('An error occured while loading this metabox. Please contact the support.', STG_HELPDESK_TEXT_DOMAIN_NAME);
        }

        include_once($template);
    }

    /**
     * Check ticket exists
     *
     * @return bool
     */
    protected function ticketExists()
    {
        return isset($_GET['post']);
    }
}