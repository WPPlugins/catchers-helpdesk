<?php

$mail_login = stgh_get_option('stg_mail_login');
$mail_pwd = stgh_get_option('mail_pwd');
$protocol = stgh_get_option('mail_protocol', "IMAP");
$provider = stgh_get_option('mail_protocol_visible', '');
$host = stgh_get_option('mail_server');
$port = stgh_get_option('mail_port');
$encryption = stgh_get_option('mail_encryption', "");

?>

<div class="stgh_wiz_space">
    <div class="stgh_wiz_block">
        <div class="stgh_logo"></div>
        <div class="stgh_wiz_header stgh_wiz_mainheader">Your customer support is almost ready</div>
        <div class="stgh_wiz_header stgh_wiz_subheader">We will help you configure the main settings</div>

        <!-- Step 1 -->
        <div class="stgh_wiz_content">
            <form>
                <div class="stgh_wiz_huge"><b>1. Support e-mail</b></div>
                <p class="stgh_wiz_text stgh_wiz_middle">Add you primary support e-mail address</p>
                <input class="stgh_wiz_input" type="email" name="stgh_wiz_email" placeholder="example@support.com"
                       value="<?php echo $mail_login; ?>">
                <input id="stgh_wiz_prev_email" hidden type="text" value="<?php echo $mail_login; ?>">
                <p class="stgh_wiz_hint stgh_wiz_small">This will also be the default reply-to address when you respond
                    to tickets from Helpdesk</p>
                <div class="stgh_wiz_vault">
                    <img class="stgh_wiz_img stgh_loader_image"  hidden id="stgh_loader"
                         src="<?php echo STG_HELPDESK_URL . 'images/ajax-loader.gif'; ?>">
                    <button data-method="support_email" type="submit"
                            class="button button-primary stgh_wiz_next_button">next >
                    </button>
                </div>
            </form>
        </div>

        <!-- Step 2 -->
        <div class="stgh_wiz_content stgh_wiz_hidden" data-method="mail_settings">
            <form method="POST" name="stgh_form_step2" action="<?php echo add_query_arg(array(
                'stgh-do' => 'check-connection',
                'tconnection' => '1',
                'stgh_back' => '',
                'stgh_save_first' => ''
            )) ?>">
                <div class="stgh_wiz_huge"><b>2. Getting mail settings</b></div>
                <p class="stgh_wiz_text stgh_wiz_middle">Username*</p>
                <input class="regular-text" name="stgh_stg_mail_login" placeholder="" maxlength=""
                       id="stgh_stg_mail_login" type="text" value="<?php echo $mail_login; ?>" \>

                <p class="stgh_wiz_text stgh_wiz_middle">Password*</p>
                <input class="regular-text" name="stgh_mail_pwd" placeholder="" maxlength="" id="stgh_mail_pwd"
                       type="password" value="<?php echo $mail_pwd; ?>" \>

                <p class="stgh_wiz_text stgh_wiz_middle">Choose your mail provider*</p>
                <select required class="stgh_wiz_input" name="stgh_mail_protocol_visible">
                    <option value="Gmail" <?php echo $provider == 'Gmail' ? 'selected' : '' ?> > Gmail</option>
                    <option value="Yahoo" <?php echo $provider == 'Yahoo' ? 'selected' : '' ?> > Yahoo</option>
                    <option value="Outlook" <?php echo $provider == 'Outlook' ? 'selected' : '' ?> > Outlook</option>
                    <option value="Yandex" <?php echo $provider == 'Yandex' ? 'selected' : '' ?> > Yandex</option>
                    <option value="" <?php echo $provider == '' ? 'selected' : ''; ?> >--------</option>
                    <option value="POP3" <?php echo $provider == 'POP3' ? 'selected' : ''; ?> > POP3</option>
                    <option value="IMAP" <?php echo $provider == 'IMAP' ? 'selected' : ''; ?> > IMAP</option>
                </select>

                <p class="stgh_wiz_text stgh_wiz_middle stgh_hide">Host*</p>
                <input class="regular-text" name="stgh_mail_server" placeholder="" maxlength="" id="stgh_mail_server"
                       type="text" value="<?php echo $host; ?>" \>

                <input hidden class="regular-text" name="stgh_mail_protocol" placeholder="" maxlength=""
                       id="stgh_mail_protocol" type="text" value="<?php echo $protocol; ?>" \>

                <p class="stgh_wiz_text stgh_wiz_middle stgh_hide">Encryption*</p>
                <select class="stgh_wiz_input" name="stgh_mail_encryption">
                    <option value="" <?php echo $encryption == '' ? 'selected' : '' ?> >------</option>
                    <option value="SSL" <?php echo $encryption == 'SSL' ? 'selected' : '' ?> >SSL</option>
                    <option value="TLS" <?php echo $encryption == 'TLS' ? 'selected' : '' ?> >TLS</option>
                </select>

                <p class="stgh_wiz_text stgh_wiz_middle stgh_hide">Port*</p>
                <input class="regular-text stgh_wiz_input" id="stgh_mail_port" type="text" name="stgh_mail_port"
                       value="<?php echo $port; ?>" list="ports_list">
                <datalist id="ports_list">
                    <option value="110">POP3 - 110</option>
                    <option value="995">POP3-SSL - 995</option>
                    <option value="143">IMAP - 143</option>
                    <option value="993">IMAP-SSL - 993</option>
                </datalist>
                <p class='description stgh_hide'>Default ports:<br/> POP3 - 110, POP3-SSL - 995, IMAP - 143, IMAP-SSL-
                    993 </p>

                <div class="stgh_margintop_15">
                    <input id="stgh_chk_conf" type="submit" class="button button-primary" value="test connection">
                    <img class="stgh_wiz_img stgh_loader_image"  hidden id="stgh_wizard_loader"
                         src="<?php echo STG_HELPDESK_URL . 'images/ajax-loader.gif'; ?>">
                </div>

                <div class="stgh_wiz_sep1">
                    <div class="stgh_float_left">
                        <button class="button button-primary stgh_wiz_back_button ">< back</button>
                        <img class="stgh_wiz_img stgh_loader_image"  hidden id="stgh_wizard_loader"
                             src="<?php echo STG_HELPDESK_URL . 'images/ajax-loader.gif'; ?>">
                    </div>
                    <div class="stgh_wiz_vault">
                        <img class="stgh_wiz_img stgh_loader_image" hidden id="stgh_loader"
                             src="<?php echo STG_HELPDESK_URL . 'images/ajax-loader.gif'; ?>">
                        <button class="button button-primary stgh_wiz_next_button">next ></button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Step 3 -->
        <div class="stgh_wiz_content stgh_wiz_hidden">
            <form>
                <div class="stgh_wiz_huge"><b>3. Contact form</b></div>
                <p class="stgh_wiz_text stgh_wiz_middle">If you have a contact page ou your site, you need to copy
                    shortcode [ticket-form] in the page</p>
                <p class="stgh_wiz_text stgh_wiz_middle">If you don't please use our <a target="_blank"
                                                                                        href="<?php echo STG_HELPDESK_SHORTCODE_TICKET_FORM; ?>">contact
                        form page</a>. It will be created automatically. It can be managed from admin area.</p>
                <div class="stgh_wiz_sep1">
                    <div class="stgh_float_left">
                        <button class="button button-primary stgh_wiz_back_button">< back</button>
                    </div>
                    <div class="stgh_wiz_vault">
                        <a href="<?php echo stgh_link_to_admin_panel(); ?>" class="button button-primary ">finish</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>