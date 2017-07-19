<?php
global $post;

$fields = get_terms( array(
    'taxonomy' => 'customfields',
    'hide_empty' => false,
));
?>
<div class="stgh-customfields" data-authorId="<?php echo $post->post_author ?>" data-postId="<?php echo $post->ID ?>"
     id="stgh-customfields">
    <div id="stgh-customfields-content">
        <?php

            $allTicketMetas = (get_post_meta($post->ID));

            foreach($fields as $field):
                if(!isset($allTicketMetas['custom_field_'.$field->term_id]))
                    continue;

                $fieldValue =  get_post_meta($post->ID, 'custom_field_'.$field->term_id, true);

                $fieldValue = is_serialized($fieldValue) ? @unserialize($fieldValue) : $fieldValue;

                $options = \StgHelpdesk\Helpers\Stg_Helper_Custom_Forms::getFieldOptions($field->term_id);

                if(count($options) > 0)
                {
                    if(is_array($fieldValue)){
                        $tmp = array();
                        foreach($fieldValue as $currValue)
                        {
                            if(isset($options[$currValue]))
                                $tmp[] = $options[$currValue];
                            else
                                $tmp[] = '';
                        }

                        $fieldValueT = implode(',',$tmp);

                    }else{
                        if(isset($options[$fieldValue]))
                            $fieldValueT = $options[$fieldValue];
                        else
                            $fieldValueT = '';
                    }

                }
                else{
                    $fieldValueT = $fieldValue;
                }

                $fieldNameForId = 'stgh_custom_'.$field->term_id;

                ?>
                <div class="stgh-metabox-inner-item stgh-metabox-customfields-item">
                    <label for="<?=$fieldNameForId?>">
                        <b><?=$field->name?>:</b>
                                    <span id="stgh-metabox-customfields-<?=$fieldNameForId?>-selected">
                                        <?=$fieldValueT?>
                                        <a class="stgh-metabox-customfields-click"
                                           data-block="stgh-metabox-customfields-<?=$fieldNameForId?>"><?= __('Change', STG_HELPDESK_TEXT_DOMAIN_NAME) ?></a>
                                    </span>

                                    <span id="stgh-metabox-customfields-<?=$fieldNameForId?>-select" class="stgh_customfield_metabox_edit">
                                        <?=\StgHelpdesk\Helpers\Stg_Helper_Custom_Forms::getCustomField($field->term_id,$field,$fieldValue,false);?>
                                    </span>
                    </label>
                </div>
            <?php endforeach;
        ?>
    </div>
</div>