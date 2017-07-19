<?php
use StgHelpdesk\Helpers\Stg_Helper_Template;

add_shortcode(STG_HELPDESK_SHORTCODE_TICKET_FORM, 'stg_sc_ticket_form');
/**
 * Form Add ticket
 */
function stg_sc_ticket_form($atts,$content,$tag)
{
     ob_start();

    if(!empty($content) && $tag == "ticket-form")
    {
        $formName = $content;
        $form = get_term_by('name',$formName,'customforms');
        Stg_Helper_Template::getTemplate('stg-ticket-form-by-id', array("formId" => $form->term_id));

    }else{
        $defaultForm= \StgHelpdesk\Helpers\Stg_Helper_Custom_Forms::getDefaultForm();
        if($defaultForm)
            Stg_Helper_Template::getTemplate('stg-ticket-form-by-id', array("formId" => $defaultForm->term_id));
        else
            Stg_Helper_Template::getTemplate('stg-ticket-form');
    }


    $content = ob_get_contents();
    ob_end_clean();
    return $content;
}