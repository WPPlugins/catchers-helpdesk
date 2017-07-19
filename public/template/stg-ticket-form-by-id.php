<?php
use StgHelpdesk\Helpers\Stg_Helper_Template;
use StgHelpdesk\Helpers\Stg_Helper_Custom_Forms;

global $post;

$formContent = Stg_Helper_Custom_Forms::getFormText($formId);
$recaptchaContent = "";
$isRecaptcha = stgh_get_option('recaptcha_enable',false);

if($isRecaptcha)
{
        $grecaptchaKey = stgh_get_option('recaptcha_key');
        $grecaptchaSKey = stgh_get_option('recaptcha_secret_key');
        $grecaptchaHL = stgh_get_option('recaptcha_hl');
        $recaptchaContent = Stg_Helper_Custom_Forms::getStandartFieldReCAPTCHA();

        if($grecaptchaKey && $grecaptchaSKey) {
            wp_enqueue_script('grecaptcha', '//www.google.com/recaptcha/api.js?hl='.$grecaptchaHL, false, null);
            $keyValue = 'key';
        }
        else {
            $keyValue = 'nokey';
        }
}else{
    $keyValue = 'nocaptcha';
}
echo "<script type=\"text/javascript\">
            /* &lt;![CDATA[ */
            var captcha = '{$keyValue}';
            /* ]]&gt; */
            </script>";


$user = get_user_by('id', stgh_get_current_user_id());
$disabled = false; //is_user_logged_in()

if (isset($_REQUEST['stgh_message']) && $_REQUEST['stgh_message']) {
    echo '<div id="message">' . $_REQUEST['stgh_message'] . '</div>';
}
if (isset($_REQUEST['stgh_message_success']) && $_REQUEST['stgh_message_success']) {
    echo '<div id="message">' . $_REQUEST['stgh_message_success'] . '</div>';
} else {

    ?>
    <div id="stg-ticket-form-block">
        <form enctype="multipart/form-data" id="stg-ticket-form"
              action="<?php echo(isset($post->ID) ? get_permalink($post->ID) : ''); ?>" method="post" role="form"
              class="stg-form">

            <input type="hidden" name="stg_saveTicket" value="1">

            <?php
                echo $formContent;
                echo $recaptchaContent;
            ?>
            <br/>
            <button value="" name="stgsubmit" id="stgsubmit" type="submit"><?php _e('Submit', STG_HELPDESK_TEXT_DOMAIN_NAME) ?></button>
        </form>
    </div>
<?php } ?>