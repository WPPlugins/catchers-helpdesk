<?php
/**
 * CRM user profile.
 */

if (!defined('WPINC')) {
    die;
}

global $post;

$contact = get_post_meta($post->ID, '_stgh_contact', true);
$user_id = $contact ? $contact : false;

if ($user_id):
    $email = get_the_author_meta('email', $user_id);
    $phone = get_the_author_meta('_stgh_crm_phone', $user_id);
    ?>
    <div id="stgh-crm-info">
        <div class="stgh-metabox stgh-ticket-crm stgh-ticket-crm-user-info">
            <div class="stgh-ticket-crm-user">
                <div>
                    <?php echo get_avatar($email, '50'); ?>
                </div>
                <div class="stgh_ticket_crm_user_title">
                    <h3 class="user-title">
                        <a href="<?php echo esc_url(get_edit_user_link($user_id)); ?>">
                            <?= esc_attr(stgh_crm_get_user_full_name($user_id)) ?>
                        </a>
                    </h3>
                    <?php
                    $position = esc_attr(get_the_author_meta('_stgh_crm_position', $user_id));
                    $companyName = esc_attr(get_the_author_meta('_stgh_crm_company', $user_id));
                    ?>
                    <span class="title"><?= $position ?></span>
                    <?php if (!empty($companyName)): ?>
                        <span class="title"><?= __('at', STG_HELPDESK_TEXT_DOMAIN_NAME) ?></span>
                    <?php endif; ?>
                    <span class="company-name"><?= $companyName ?></span>
                </div>
            </div>

            <div class="clear"></div>

            <div class="stgh-ticket-crm-element stgh-metabox-inner-item">
                <label for="user-email">
                    <?= esc_attr($email) ?>
                </label>
            </div>

            <?php if (!empty($phone)): ?>
                <div class="stgh-ticket-crm-element stgh-metabox-inner-item">
                    <label for="user-phone">
                        <?= esc_attr($phone) ?>
                    </label>
                </div>
            <?php endif; ?>
        </div>

        <div class="stgh-ticket-crm-box submitbox">
            <input type="hidden" name="_stgh_crm_user_id" value="<?php echo $user_id; ?>">

            <div class="major-publishing-actions">
                <div id="stgh-change-btn-block" class="stgh-ticket-right-button">
                    <?php submit_button(__('Change', STG_HELPDESK_TEXT_DOMAIN_NAME), 'button tagadd', 'change', false, array('id' => 'stgh-change-btn')); ?>
                </div>
                <div class="clear"></div>
            </div>
        </div>
    </div>

    <div id="stgh-crm-select-form" class="stgh_display_none">
        <div class="stgh-metabox stgh-ticket-crm stgh-ticket-select-form">
            <input class="stgh_width100pro" id="stgh-contact-crm-autocomplete" type="text" name="stgh_crm_contact_label"
                   placeholder="Enter 3 or more characters"
                   value=""/>
            <input type="hidden" id="stgh-crm-contact-value" name="stgh_crm_contact_value" value=""/>
            <a id="stgh-crm-new-contact-link"
               href="#"><?php _e('Add new contact', STG_HELPDESK_TEXT_DOMAIN_NAME); ?></a>
        </div>

        <div class="stgh-ticket-crm-box submitbox">
            <div class="major-publishing-actions">
                <div id="stgh-update-btn-block" class="stgh-ticket-right-button">
                    <?php submit_button(__('Cancel', STG_HELPDESK_TEXT_DOMAIN_NAME), 'button tagadd', 'cancel', false,
                        array('id' => 'stgh-cancel-update-btn')); ?>
                    <?php submit_button(__('Update', STG_HELPDESK_TEXT_DOMAIN_NAME), 'primary button-large', 'stgh-update', false,
                        array('accesskey' => 'u')); ?>
                </div>
                <div class="clear"></div>
            </div>
        </div>
    </div>

    <div class="stgh_display_none" id="stgh-crm-new-contact">
        <div class="stgh-metabox stgh-ticket-crm stgh-crm-new-contact-form">
            <div>
                <strong><?php _e('Name:', STG_HELPDESK_TEXT_DOMAIN_NAME); ?></strong><br/>
                <input type="text" name="stgh_crm_new_contact_name"
                       value=""/>
            </div>
            <div>
                <strong><?php _e('Email:', STG_HELPDESK_TEXT_DOMAIN_NAME); ?></strong><br/>
                <input type="text" name="stgh_crm_new_contact_email"
                       value=""/>
            </div>
        </div>

        <div class="stgh-ticket-crm-box submitbox">
            <div class="major-publishing-actions">
                <div id="stgh-new-btn-block" class="stgh-ticket-right-button">
                    <?php submit_button(__('Cancel', STG_HELPDESK_TEXT_DOMAIN_NAME), 'button tagadd', 'cancel', false,
                        array('id' => 'stgh-cancel-add-btn')); ?>
                    <?php submit_button(__('Add', STG_HELPDESK_TEXT_DOMAIN_NAME), 'primary button-large', 'stgh-add-contact', false); ?>
                </div>
                <div class="clear"></div>
            </div>
        </div>
    </div>
<?php else: ?>
    <p><?php _e('User not found', STG_HELPDESK_TEXT_DOMAIN_NAME); ?></p>
    <div id="stgh-crm-info">
        <div class="stgh-ticket-crm-box submitbox">
            <div class="major-publishing-actions">
                <div id="stgh-change-btn-block" class="stgh-ticket-right-button">
                    <?php submit_button(__('Change', STG_HELPDESK_TEXT_DOMAIN_NAME), 'button tagadd', 'change', false, array('id' => 'stgh-change-btn')); ?>
                </div>
                <div class="clear"></div>
            </div>
        </div>
    </div>

    <div id="stgh-crm-select-form" class="stgh_display_none">
        <div class="stgh-metabox stgh-ticket-crm stgh-ticket-select-form">
            <input class="stgh_width100pro" id="stgh-contact-crm-autocomplete" type="text" name="stgh_crm_contact_label"
                   placeholder="Enter 3 or more characters"
                   value=""/>
            <input type="hidden" id="stgh-crm-contact-value" name="stgh_crm_contact_value" value=""/>
            <a id="stgh-crm-new-contact-link"
               href="#"><?php _e('Add new contact', STG_HELPDESK_TEXT_DOMAIN_NAME); ?></a>
        </div>

        <div class="stgh-ticket-crm-box submitbox">
            <div class="major-publishing-actions">
                <div id="stgh-update-btn-block" class="stgh-ticket-right-button">
                    <?php submit_button(__('Cancel', STG_HELPDESK_TEXT_DOMAIN_NAME), 'button tagadd', 'cancel', false,
                        array('id' => 'stgh-cancel-update-btn')); ?>
                    <?php submit_button(__('Update', STG_HELPDESK_TEXT_DOMAIN_NAME), 'primary button-large', 'stgh-update', false,
                        array('accesskey' => 'u')); ?>
                </div>
                <div class="clear"></div>
            </div>
        </div>
    </div>

    <div class="stgh_display_none" id="stgh-crm-new-contact">
        <div class="stgh-metabox stgh-ticket-crm stgh-crm-new-contact-form">
            <div>
                <strong><?php _e('Name:', STG_HELPDESK_TEXT_DOMAIN_NAME); ?></strong><br/>
                <input type="text" name="stgh_crm_new_contact_name"
                       value=""/>
            </div>
            <div>
                <strong><?php _e('Email:', STG_HELPDESK_TEXT_DOMAIN_NAME); ?></strong><br/>
                <input type="text" name="stgh_crm_new_contact_email"
                       value=""/>
            </div>
        </div>

        <div class="stgh-ticket-crm-box submitbox">
            <div class="major-publishing-actions">
                <div id="stgh-new-btn-block" class="stgh-ticket-right-button">
                    <?php submit_button(__('Cancel', STG_HELPDESK_TEXT_DOMAIN_NAME), 'button tagadd', 'cancel', false,
                        array('id' => 'stgh-cancel-add-btn')); ?>
                    <?php submit_button(__('Add', STG_HELPDESK_TEXT_DOMAIN_NAME), 'primary button-large', 'stgh-add-contact', false); ?>
                </div>
                <div class="clear"></div>
            </div>
        </div>
    </div>
<?php endif; ?>