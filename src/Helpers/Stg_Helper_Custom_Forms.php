<?php
namespace StgHelpdesk\Helpers;

use StgHelpdesk\Admin\Stg_Helpdesk_Admin;
use StgHelpdesk\Core\PostType\Stg_Helpdesk_Post_Type_Statuses;
use StgHelpdesk\Ticket\Stg_Helpdesk_Ticket;
use StgHelpdesk\Helpers\Stg_Helper_Logger;

/**
 * Class Stg_Helper_Custom_Forms
 * @package StgHelpdesk\Helpers
 */
class Stg_Helper_Custom_Forms
{
    public static function getLocalizationArray()
    {
        return array(
            'selectFields' => __('Select fields', STG_HELPDESK_TEXT_DOMAIN_NAME),
            'requiredField' => __('Required field', STG_HELPDESK_TEXT_DOMAIN_NAME),
            'notFound' => __('not found', STG_HELPDESK_TEXT_DOMAIN_NAME),
            'recaptchaRequired' => stgh_get_option('recaptcha_enable',false),
        );
    }

    public static function getCustomFields()
    {
        $args = array(
            'orderby' => 'name',
            'order' => 'ASC',
            'hide_empty' => false,
            'exclude' => array(),
            'exclude_tree' => array(),
            'include' => array(),
            'number' => '',
            'fields' => 'all',
            'slug' => '',
            'parent' => '',
            'hierarchical' => true,
            'child_of' => 0,
            'get' => '',
            'name__like' => '',
            'pad_counts' => false,
            'offset' => '',
            'search' => '',
            'cache_domain' => 'core',
            'name' => '',
            'childless' => false,
            'update_term_meta_cache' => true,
            'meta_query' => '',
        );


        $customFields = get_terms('customfields', $args);

        $result = $tmp = array();
        foreach ($customFields as $customField) {

            $container = "Ticket";

            $tmp = array('text' => $container.": ".$customField->name, 'id' => '[custom_field \''.$customField->name.'\']', 'label' => $customField->name);
            $result[] = $tmp;
        }

        $result = array(
            array('id' => 'ticket_fields', 'text' => __('Ticket fields', STG_HELPDESK_TEXT_DOMAIN_NAME),
                'children' => array(
                    array('text' => __('Category', STG_HELPDESK_TEXT_DOMAIN_NAME), 'id' => '[ticket_field \'category\']', 'label' => __('Category', STG_HELPDESK_TEXT_DOMAIN_NAME)),
                    array('text' => __('Tags', STG_HELPDESK_TEXT_DOMAIN_NAME), 'id' => '[ticket_field \'tags\']', 'label' => __('Tags', STG_HELPDESK_TEXT_DOMAIN_NAME)),
                    array('text' => __('Subject (required field)', STG_HELPDESK_TEXT_DOMAIN_NAME), 'id' => '[ticket_field \'subject\']', 'label' => __('Subject', STG_HELPDESK_TEXT_DOMAIN_NAME)),
                    array('text' => __('Message (required field)', STG_HELPDESK_TEXT_DOMAIN_NAME), 'id' => '[ticket_field \'message\']', 'label' => __('Description', STG_HELPDESK_TEXT_DOMAIN_NAME)),
                    array('text' => __('Files', STG_HELPDESK_TEXT_DOMAIN_NAME), 'id' => '[ticket_field \'files\']'),
                    //array('text' => __('reCAPTCHA (required field)', STG_HELPDESK_TEXT_DOMAIN_NAME), 'id' => '[ticket_field \'reCAPTCHA\']'),
                )),
            array('id' => 'contact_fields', 'text' => __('Contact fields', STG_HELPDESK_TEXT_DOMAIN_NAME),
                'children' => array(
                    array('text' => __('Name (required field)', STG_HELPDESK_TEXT_DOMAIN_NAME), 'id' => '[contact_field \'name\']', 'label' => __('Name', STG_HELPDESK_TEXT_DOMAIN_NAME)),
                    array('text' => __('Email (required field)', STG_HELPDESK_TEXT_DOMAIN_NAME), 'id' => '[contact_field \'email\']', 'label' => __('Email', STG_HELPDESK_TEXT_DOMAIN_NAME)),
                )),
            array('id' => 'custom_fields', 'text' => __('Custom fields', STG_HELPDESK_TEXT_DOMAIN_NAME),
                'children' => $result));


            $result = apply_filters( 'stg_get_customfield_result', $result );


        return $result;
    }

    public static function getFormText($formId)
    {
        global $post;

        $formTemplate = get_term($formId, 'customforms')->description;
        $fieldsFull = self::getCustomFields();


        $replaces = $rreplaces = array();

        $loggerTag = Stg_Helper_Logger::getLogger('shorttags');
        $loggerTag->log($fieldsFull);

        $loggerTemplate = Stg_Helper_Logger::getLogger('template');
        $loggerTemplate->log($formTemplate);


        $fieldsFull = apply_filters( 'stg_get_form_text_before', $fieldsFull );

        foreach ($fieldsFull as $macro) {
            foreach ($macro['children'] as $child) {
                $tmp = $rtmp = array();
                $rtmp['search'] =$tmp['search'] = $child['id'];
                switch ($child['id']) {
                    case '[ticket_field \'category\']':
                        $tmp['replace'] = self::getStandartFieldCategory();
                        $rtmp['replace'] = self::getStandartFieldCategory(true);
                        break;
                    case '[ticket_field \'tags\']':
                        $tmp['replace'] = self::getStandartFieldTag();
                        $rtmp['replace'] = self::getStandartFieldTag(true);
                        break;
                    case '[ticket_field \'subject\']':
                        $tmp['replace'] = self::getStandartFieldSubject();
                        break;
                    case '[ticket_field \'message\']':
                        $tmp['replace'] = self::getStandartFieldMessage();
                        break;
                    case '[ticket_field \'files\']':
                        $tmp['replace'] = self::getStandartFieldFiles();
                        break;
                    case '[contact_field \'name\']':
                        $tmp['replace'] = self::getStandartFieldName();
                        break;
                    case '[contact_field \'email\']':
                        $tmp['replace'] = self::getStandartFieldEmail();
                        break;

                    default:

                        $fieldName = self::getFieldNameFromShortcode($child['id']);
                        $field = get_term_by('name',addslashes($fieldName),'customfields');

                        $tmp['replace'] = self::getCustomField($field->term_id,$field);

                }

                $replaces[] = $tmp;
                $rreplaces[] = $rtmp;
            }
        }

        $loggerReplaces = Stg_Helper_Logger::getLogger('replaces');
        $loggerReplaces->log($replaces);



        $tmp = array();
        $tmp['search'] = '[ticket_field \'reCAPTCHA\']';
        $tmp['replace'] = "";
        $replaces[] = $tmp;

        foreach ($rreplaces as $item) {
            if(isset($item['replace']))
            {
                $formTemplate = str_ireplace($item['search']."*", $item['replace'], $formTemplate);
            }
        }


        $replaces = apply_filters( 'stg_get_form_text_replaces', $replaces );

        foreach ($replaces as $item) {
            $formTemplate = str_ireplace($item['search'], $item['replace'], $formTemplate);
        }


        return $formTemplate;
    }

    public static function getTermMetaValue($termId){
        if(function_exists('get_term_meta')) {
            $values = get_term_meta($termId,'stgh_term_meta');
            if(isset($values[0]))
                $values = $values[0];
        }else{
            $values = get_option("stgh_taxonomy_".$termId);
        }

        return $values;
    }

    public static function setTermMetaValue($termId, $values){
            if(function_exists('add_term_meta')){
                $cat_keys = array_keys( $values['stgh_term_meta'] );
                foreach ( $cat_keys as $key ) {
                    if ( isset ( $values['stgh_term_meta'][$key] ) ) {
                        $term_meta[$key] = $values['stgh_term_meta'][$key];
                    }
                }

                delete_term_meta($termId, 'stgh_term_meta');
                add_term_meta($termId, 'stgh_term_meta', $term_meta);
            }else{
                $cat_keys = array_keys( $values['stgh_term_meta'] );
                foreach ( $cat_keys as $key ) {
                    if ( isset ( $values['stgh_term_meta'][$key] ) ) {
                        $term_meta[$key] = $values['stgh_term_meta'][$key];
                    }
                }

                // Save the option array.
                update_option( "stgh_taxonomy_".$termId, $term_meta );
            }
    }


    public static function getStandartFieldCategory($required = false){
        return \StgHelpdesk\Ticket\Stg_Helpdesk_TicketCategory::getSelectList(null, null, 'stgh_standart_field',$required);
    }

    public static function getStandartFieldTag($required = false){
        return stgh_ticket_tags_list(null, null, 'stgh_standart_field','stgh_tags', $required);
    }

    public static function getStandartFieldSubject(){
        $text = '<input required="required" name="stg_ticket_subject" value="" type="text" class="stgh_standart_field">';
        return $text;
    }

    public static function getStandartFieldMessage(){
        $text = '<textarea required="required" name="stg_ticket_message" class="stgh_standart_field"></textarea>';
        return $text;
    }

    public static function getStandartFieldFiles(){
        return Stg_Helper_Template::getTemplate('stg-upload-file-field', array(), false);
    }

    public static function getStandartFieldReCAPTCHA(){
        $key =  stgh_get_option('recaptcha_key');

        if($key) {

//            $dataSize = stgh_get_option('recaptcha_data_size','normal');
//            $dataType = stgh_get_option('recaptcha_data_type','image');
//            $dataTheme = stgh_get_option('recaptcha_data_theme','light');
//            $dataBadge = stgh_get_option('recaptcha_data_badge','bottomright');
//
//
//
//            switch($dataSize){
//                case "invisible":
//                    $recaptchaTxt = '<div id="g-recaptcha" class="g-recaptcha" data-sitekey="' . $key . '" data-callback="onloadCallbackIn" data-size="'.$dataSize.'" data-type="'.$dataType.'" data-theme="'.$dataTheme.'" data-badge="'.$dataBadge.'"></div>';
//                    break;
//                default:
//                    $recaptchaTxt = '<div id="g-recaptcha" class="g-recaptcha" data-sitekey="' . $key . '" data-callback="onloadCallback" data-size="'.$dataSize.'" data-type="'.$dataType.'" data-theme="'.$dataTheme.'"></div>';
//            }



            $recaptchaTxt = '<div id="g-recaptcha" class="g-recaptcha" data-sitekey="' . $key . '" data-callback="onloadCallbackIn" data-size="invisible"  data-badge="inline"></div>';



            $recaptchaTxt .= '<noscript>
              <div>
                <div style="width: 302px; height: 422px; position: relative;">
                  <div style="width: 302px; height: 422px; position: absolute;">
                    <iframe src="https://www.google.com/recaptcha/api/fallback?k='.$key.'"
                            frameborder="0" scrolling="no"
                            style="width: 302px; height:422px; border-style: ">
                    </iframe>
                  </div>
                </div>
                <div style="width: 300px; height: 60px; border-style: none;
                               bottom: 12px; left: 25px; margin: 0px; padding: 0px; right: 25px;
                               background: #f9f9f9; border: 1px solid #c1c1c1; border-radius: 3px;">
                  <textarea id="g-recaptcha-response" name="g-recaptcha-response"
                               class="g-recaptcha-response"
                               style="width: 250px; height: 40px; border: 1px solid #c1c1c1;
                                      margin: 10px 25px; padding: 0px; resize: none;" >
                  </textarea>
                </div>
              </div>
            </noscript>';


            return $recaptchaTxt;
        }

        return '';
    }


    public static function getStandartFieldName(){
        $user = get_user_by('id', stgh_get_current_user_id());
        $value = isset($user->user_nicename) ? $user->user_nicename : '';
        $text = '<input required="required" name="stg_ticket_name" value="'.$value.'" type="text" class="stgh_standart_field">';
        return $text;
    }

    public static function getStandartFieldEmail(){
        $user = get_user_by('id', stgh_get_current_user_id());
        $value = !empty($user->user_email) ? $user->user_email : '' ;
        $text = '<input required="required" name="stg_ticket_email" value="'.$value.'" type="text" class="stgh_standart_field">';
        return $text;
    }



    public static function getCustomField($fieldId, $field = false, $value = false, $required = true, $isAdmin = false){
        if(!$field)
        {
            $field = get_term($fieldId,'customfields');
        }

        $fieldMeta = self::getTermMetaValue($fieldId);

        $fieldConstructor = self::getFieldConstructor($fieldMeta);

        if($fieldConstructor)
            return self::$fieldConstructor($fieldMeta,'custom_field_'.$field->term_id, $value, $required, $isAdmin);

        return "";
    }

    public static function getFieldConstructor($fieldMeta){
        if(isset($fieldMeta['custom_field_type_meta']))
            $classConstructorName = strtolower($fieldMeta['custom_field_type_meta'])."Constructor";
        else
            $classConstructorName = "defaultConstructor";

        if(method_exists('StgHelpdesk\Helpers\Stg_Helper_Custom_Forms',$classConstructorName)){
            return $classConstructorName;
        }
        else
            return null;
    }

    public static function dateConstructor($fieldMeta,$fieldName,$value = false, $required, $isAdmin){
        $defaultValue = isset($fieldMeta['custom_field_dvalue_meta'])?$fieldMeta['custom_field_dvalue_meta']:"";

        $value = (!$value)?$defaultValue:$value;

        $format = isset($fieldMeta['custom_field_format_meta']) && !empty($fieldMeta['custom_field_format_meta'])? $fieldMeta['custom_field_format_meta']:'dd.mm.yy';

        if($isAdmin)
            $disabled = 'disabled="disabled"';
        else
            $disabled = '';

        return "<input type='text' {$disabled} value='{$value}'  dateformat='{$format}' name='stgh_custom_fields[{$fieldName}]' class=\"datepicker stgh_custom_fields\"/>";
    }


    public static function dropdownConstructor($fieldMeta,$fieldName,$value = false, $required, $isAdmin){
        $options = "";

        if(isset($fieldMeta['custom_field_required_meta']) && $fieldMeta['custom_field_required_meta'] == "on" && $required)
        {
            $required = "required = 'required'";
        }else{
            $required = "";
        }

        if(isset($fieldMeta['custom_field_options_meta']))
        {
            foreach(explode(PHP_EOL,$fieldMeta['custom_field_options_meta']) as $option)
            {
                if(!empty($option)) {
                    @list($currValue,$text) = explode(":",$option);
                    if(!$text) {
                        $currValue = trim($currValue);
                        $text = $currValue;

                    }

                    $selected = ($currValue === $value)?'selected="selected"':'';

                    $options .= "<option value='{$currValue}' {$selected}>{$text}</option>";
                }
            }

        }

        if($isAdmin)
            $disabled = 'disabled="disabled"';
        else
            $disabled = '';

        return "<select {$disabled} {$required} name='stgh_custom_fields[{$fieldName}]' class=\"stgh_custom_fields\">{$options}</select>";
    }

    public static function checkboxesConstructor($fieldMeta,$fieldName,$value = false, $required, $isAdmin){
        $options = "";

        if(isset($fieldMeta['custom_field_required_meta']) && $fieldMeta['custom_field_required_meta'] == "on" && $required)
        {
            $required = "required = 'required'";
        }else{
            $required = "";
        }

        $optionsArr = array();
        if(isset($fieldMeta['custom_field_options_meta']))
            $optionsArr = explode(PHP_EOL,$fieldMeta['custom_field_options_meta']);

        if($isAdmin)
            $disabled = 'disabled="disabled"';
        else
            $disabled = '';

        if(count($optionsArr)>1)
        {
            foreach($optionsArr as $option)
            {
                if(!empty($option)) {
                    @list($currValue,$text) = explode(":",$option);
                    if(!$text) {
                        $currValue = trim($currValue);
                        $text = $currValue;

                    }

                    if($value !== false && is_array($value))
                        $checked = (in_array($currValue,$value))?'checked="checked"':'';
                    else
                        $checked = '';
                    $options .= "<input {$disabled} {$checked} type='checkbox' name='stgh_custom_fields[{$fieldName}][]' {$required} value='{$currValue}'/>{$text}<br/>";
                }
            }
            $options .= "<input {$disabled} type='hidden' name='stgh_custom_fields[{$fieldName}][]' value='' />";
        }

        if(count($optionsArr) == 1)
        {
            $options .= "<input type='hidden' {$disabled} name='stgh_custom_fields[{$fieldName}]' value='' />";
            foreach($optionsArr as $option)
            {
                if(!empty($option)) {
                    @list($currValue,$text) = explode(":",$option);
                    if(!$text) {
                        $currValue = trim($currValue);
                        $text = $currValue;

                    }


                    $checked = ($currValue == $value)?'checked="checked"':'';
                    $options .= "<input {$disabled} {$checked} type='checkbox' name='stgh_custom_fields[{$fieldName}]' value='{$currValue}'/>{$text}<br/>";
                }
            }

        }


        return "<div class='stgh-checks-required'>".$options."</div>";
    }

    public static function radioConstructor($fieldMeta,$fieldName,$value = false, $required, $isAdmin){
        $options = "";

        if(isset($fieldMeta['custom_field_required_meta']) && $fieldMeta['custom_field_required_meta'] == "on" && $required)
        {
            $required = "required = 'required'";
        }else{
            $required = "";
        }

        $optionsArr = array();
        if(isset($fieldMeta['custom_field_options_meta']))
            $optionsArr = explode(PHP_EOL,$fieldMeta['custom_field_options_meta']);


        if($isAdmin)
            $disabled = 'disabled="disabled"';
        else
            $disabled = '';

        if(count($optionsArr)>1)
        {
            foreach($optionsArr as $option)
            {
                if(!empty($option)) {
                    @list($currValue,$text) = explode(":",$option);
                    if(!$text) {
                        $currValue = trim($currValue);
                        $text = $currValue;

                    }

                    $checked = ($currValue == $value)?'checked="checked"':'';
                    $options .= "<input {$disabled} {$checked} type='radio' name='stgh_custom_fields[{$fieldName}]' {$required} value='{$currValue}'/>{$text}<br/>";
                }
            }
        }

        return "<div>".$options."</div>";
    }

    public static function textConstructor($fieldMeta,$fieldName,$value = false, $required, $isAdmin){
        if(isset($fieldMeta['custom_field_required_meta']) && $fieldMeta['custom_field_required_meta'] == "on" && $required)
        {
            $required = "required = 'required'";
        }else{
            $required = "";
        }

        $defaultValue = isset($fieldMeta['custom_field_dvalue_meta'])?$fieldMeta['custom_field_dvalue_meta']:"";

        $value = (!$value)?$defaultValue:$value;

        if($isAdmin)
            $disabled = 'disabled="disabled"';
        else
            $disabled = '';

        return "<input {$disabled} {$required} type='text' name='stgh_custom_fields[{$fieldName}]' value='{$value}' class=\"stgh_custom_fields\"/>";
    }


    public static function textareaConstructor($fieldMeta,$fieldName,$value = false, $required, $isAdmin){
        if(isset($fieldMeta['custom_field_required_meta']) && $fieldMeta['custom_field_required_meta'] == "on" && $required)
        {
            $required = "required = 'required'";
        }else{
            $required = "";
        }

        if($isAdmin)
            $disabled = 'disabled="disabled"';
        else
            $disabled = '';

        $defaultValue = isset($fieldMeta['custom_field_dvalue_meta'])?$fieldMeta['custom_field_dvalue_meta']:"";

        $value = (!$value)?$defaultValue:$value;

        return "<textarea {$disabled} {$required} name='stgh_custom_fields[{$fieldName}]' class=\"stgh_custom_fields\">{$value}</textarea>";
    }

    public static function defaultConstructor($fieldMeta,$fieldName,$value = false, $required, $isAdmin){
        return self::textConstructor($fieldMeta,$fieldName);
    }

    public static function createDefaultForm(){

        $defaultForm = "<p><strong><label>Your name:</label></strong><br />[contact_field 'name']</p>
<p><strong><label>Email:</label></strong><br />[contact_field 'email']</p>
<p><strong><label>Subject:</label></strong><br />[ticket_field 'subject']</p>
<p><strong><label>Description:</label></strong><br />[ticket_field 'message']</p>
<p>[ticket_field 'files']</p>";


        $term = get_term_by('name', 'DefaultForm','customforms');

        if ($term === false) {
            wp_insert_term('DefaultForm', 'customforms', array('description' => $defaultForm));
        }

    }

    public static function getFormCountByField($termId){
        global $wpdb;
        $term = get_term($termId,'customfields');
        $like = addslashes("[custom_field '{$term->name}']");
        $query = "SELECT count(description) from {$wpdb->term_taxonomy} where description like \"%{$like}%\"";
        return $wpdb->get_var($query);
    }

    public static function getPageCountByForm($termId){
        global $wpdb;
        $shortcode = self::getFormShortCode($termId);
        $query = "SELECT count(ID) from {$wpdb->posts} where post_content like \"%{$shortcode}%\" and post_type != 'revision'";
        return $wpdb->get_var($query);
    }

    public static function isDefaultForm($termId,$term = false){
        if(!$term){
            $term = get_term($termId);
        }

        return $term->name == 'DefaultForm';
    }

    public static function getFormShortCode($termId, $term = false){
        if(!$term){
            $term = get_term($termId);
        }

        return "[".STG_HELPDESK_SHORTCODE_TICKET_FORM."]".$term->name."[/".STG_HELPDESK_SHORTCODE_TICKET_FORM."]";
    }

    public static function getDefaultForm(){
        return get_term_by('name','DefaultForm','customforms');
    }


    public static function getFieldNameFromShortcode($shortcode){
        $tmp = str_replace(array("[","]","custom_field "),'',$shortcode);
        $tmp = substr($tmp,1,strlen($tmp)-2);
        return trim($tmp);
    }

    public static function getFieldOptions($termId,$termMeta = false){
        if(!$termMeta){
            $termMeta = self::getTermMetaValue($termId);
        }

        if(isset($termMeta['custom_field_options_meta']))
            $optionsArr = explode(PHP_EOL,$termMeta['custom_field_options_meta']);
        else
            $optionsArr = array();

        $result = array();
        foreach($optionsArr as $option)
        {
            if(!empty($option)) {
                @list($value,$text) = explode(":",$option);
                if(!$text) {
                    $value = trim($value);
                    $text = $value;

                }


                $result[$value] = $text;
            }
        }

        return $result;

    }

 }