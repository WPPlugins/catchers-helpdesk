<?php
/**
 * Ticket Information.
 */

// If this file is called directly, abort.
use StgHelpdesk\Admin\Stg_Helpdesk_Admin;

if (!defined('WPINC')) {
    die;
}
global $post;

$globalStatus = 'draft';
$ticketStatus = '' == stgh_ticket_get_status() ? 'new' : stgh_ticket_get_status();
$statuses = stgh_get_statuses();
$assignedTo = stgh_ticket_assigned_to($post->ID);
$isOpened = stgh_ticket_is_opened($post->ID);
$selectedCategory = stgh_ticket_get_category($post->ID);
$categories = stgh_ticket_category_list($selectedCategory);

?>
<div class="stgh-metabox submitbox">
    <?php wp_nonce_field(Stg_Helpdesk_Admin::$nonceAction, Stg_Helpdesk_Admin::$nonceName, false, true); ?>
    <div class="stgh-metabox-inner-item stgh-metabox-details-item">
        <label for="post-status">
            <?= __('Status:', STG_HELPDESK_TEXT_DOMAIN_NAME) ?>
            <span id="stgh-metabox-details-status-selected">
                <span class="stgh-ticket-details-item-selected"><?= stgh_ticket_get_named_status($post->ID) ?></span>
                <?php if ($isOpened): ?>
                    <a class="stgh-metabox-details-click"
                       data-block="stgh-metabox-details-status"><?= __('Change', STG_HELPDESK_TEXT_DOMAIN_NAME) ?></a>
                <?php endif; ?>
            </span>
            <span id="stgh-metabox-details-status-select" class="stgh_display_none">
                <select name="post_status_override_sub">
                    <?php foreach ($statuses as $status => $label):
                        $selected = ($ticketStatus === $status) ? 'selected="selected"' : ''; ?>
                        <option value="<?php echo $status; ?>" <?php echo $selected; ?>><?php echo $label; ?></option>
                    <?php endforeach; ?>
                </select>
            </span>
        </label>
    </div>
    <?php if (!empty($categories)): ?>
        <div class="stgh-metabox-inner-item stgh-metabox-details-item">
            <label for="post-category">
                <?= __('Category:', STG_HELPDESK_TEXT_DOMAIN_NAME) ?>
                <span id="stgh-metabox-details-category-selected">
                <span
                    class="stgh-ticket-details-item-selected"><?= !is_null($selectedCategory) ? $selectedCategory->name : '' ?></span>
                <a class="stgh-metabox-details-click"
                   data-block="stgh-metabox-details-category"><?= __('Change', STG_HELPDESK_TEXT_DOMAIN_NAME) ?></a>
            </span>
            <span id="stgh-metabox-details-category-select" class="stgh_display_none">
                <?= $categories ?>
            </span>
            </label>
        </div>
    <?php endif; ?>
    <div class="stgh-metabox-inner-item stgh-metabox-details-item">
        <label for="post-date">
            <?= __('Date:', STG_HELPDESK_TEXT_DOMAIN_NAME) ?>
            <span class="stgh-ticket-details-item-selected"><?= get_the_date() . ' ' . get_the_time(); ?></span>
        </label>
    </div>
    <div class="stgh-metabox-inner-item stgh-metabox-details-item">
        <label for="post-assigned-to">
            <?= __('Assigned to:', STG_HELPDESK_TEXT_DOMAIN_NAME) ?>
            <span id="stgh-metabox-details-assigned-selected">
                <span
                    class="stgh-ticket-details-item-selected"><?= !is_null($assignedTo) ? $assignedTo->data->display_name : '' ?></span>
                <?php if (stgh_current_user_can('assign_ticket') && $isOpened): ?>
                    <a class="stgh-metabox-details-click"
                       data-block="stgh-metabox-details-assigned"><?= __('Change', STG_HELPDESK_TEXT_DOMAIN_NAME) ?></a>
                <?php endif; ?>
            </span>
            <span id="stgh-metabox-details-assigned-select" class="stgh_display_none">
                <?= stgh_display_assign_to_select(array('class' => 'stgh-assign-select'), !is_null($assignedTo) ? $assignedTo->ID : 0, 'stgh_assignee_sub') ?>
            </span>
        </label>
    </div>
    <?php if (isset($_GET['post'])): ?>
        <input type="hidden" name="stgh_post_parent" value="<?php echo $_GET['post']; ?>">
    <?php endif; ?>
    <div class="major-publishing-actions">
        <?php if (stgh_current_user_can('edit_ticket')): ?>
            <div id="publishing-action">
                <span class="spinner"></span>
                <?php if ($isOpened): ?>
                    <?php if (isset($_GET['action']) && 'edit' === $_GET['action']) : ?>
                        <input name="original_publish" type="hidden" id="original_publish"
                               value="<?php esc_attr_e('Updating', STG_HELPDESK_TEXT_DOMAIN_NAME) ?>"/>
                        <?php submit_button(__('Update', STG_HELPDESK_TEXT_DOMAIN_NAME), 'primary button-large', 'publish', false,
                            array('accesskey' => 'u')); ?>
                    <?php else:
                        if (stgh_current_user_can('create_ticket')): ?>
                            <input name="original_publish" type="hidden" id="original_publish"
                                   value="<?php esc_attr_e('Creating...', STG_HELPDESK_TEXT_DOMAIN_NAME) ?>"/>
                            <?php submit_button(__('Open', STG_HELPDESK_TEXT_DOMAIN_NAME), 'primary button-large', 'publish', false,
                                array('accesskey' => 'o', 'tabindex' => '5')); ?>
                        <?php endif;
                    endif; ?>
                <?php else: ?>
                    <div id="delete-action">
                        <a class="submitdelete deletion" href="<?php echo stgh_link_to_open_ticket($post->ID); ?>">
                            <?= __('Re-open', STG_HELPDESK_TEXT_DOMAIN_NAME) ?>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        <div class="clear"></div>
    </div>
</div>