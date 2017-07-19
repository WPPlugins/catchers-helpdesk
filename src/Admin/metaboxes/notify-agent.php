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

?>
<div class="stgh-metabox submitbox">
    <?php wp_nonce_field(Stg_Helpdesk_Admin::$nonceAction, Stg_Helpdesk_Admin::$nonceName, false, true); ?>
    <div class="stgh-metabox-inner-item stgh-metabox-details-item stgh-notify-metabox">
        <div>
            <input type="radio" name="stgh_notify" value="not-send" checked="checked">
            <label for="post-not-send">
                <?= __('Not send', STG_HELPDESK_TEXT_DOMAIN_NAME) ?>
            </label>
        </div>
        <div>
            <input type="radio" name="stgh_notify" value="notify">
            <label for="post-notify">
                <?= __('Notify contact', STG_HELPDESK_TEXT_DOMAIN_NAME) ?>
            </label>
        </div>
        <div>
            <input type="radio" name="stgh_notify" value="send">
            <label for="post-send">
                <?= __('Send to contact', STG_HELPDESK_TEXT_DOMAIN_NAME) ?>
            </label>
        </div>
    </div>

    <?php if (isset($_GET['post'])): ?>
        <input type="hidden" name="stgh_post_parent" value="<?php echo $_GET['post']; ?>">
    <?php endif; ?>

</div>