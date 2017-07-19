jQuery.fn.extend({
    setSelection: function (selectionStart, selectionEnd) {
        if (this.length == 0) return this;
        input = this[0];

        if (input.createTextRange) {
            var range = input.createTextRange();
            range.collapse(true);
            range.moveEnd('character', selectionEnd);
            range.moveStart('character', selectionStart);
            range.select();
        } else if (input.setSelectionRange) {
            input.focus();
            input.setSelectionRange(selectionStart, selectionEnd);
        }

        return this;
    }
});

jQuery.fn.extend({
    insertAtCaret: function (myValue) {

        return this.each(function () {

            //IE support
            if (document.selection) {

                this.focus();
                sel = document.selection.createRange();
                sel.text = myValue;
                this.focus();

            } else if (this.selectionStart || this.selectionStart == '0') {

                //MOZILLA / NETSCAPE support
                var startPos = this.selectionStart;
                var endPos = this.selectionEnd;
                var scrollTop = this.scrollTop;
                this.value = this.value.substring(0, startPos) + myValue + this.value.substring(endPos, this.value.length);
                this.focus();
                this.selectionStart = startPos + myValue.length;
                this.selectionEnd = startPos + myValue.length;
                this.scrollTop = scrollTop;

            } else {

                this.value += myValue;
                this.focus();
            }
        });
    }
});


(function ($) {
    "use strict";

    $(function () {

        jQuery(document).ready(function ($) {

            var interval = $('select[name = "stgh_stg_mail_interval"]').val();

            $('select[name = "stgh_stg_mail_interval"]').on('change', function () {
                interval = $(this).val();

                if (interval == 'never')
                    $('#stgh_get_mails').attr('disabled', true);
                else
                    $('#stgh_get_mails').removeAttr('disabled');
            });

            $('#stgh_get_mails').click(function (event) {
                if (interval == 'never') {
                    event.preventDefault();
                    event.stopPropagation();
                    return false;
                }
                $("#stgh_loader_get_mails").show();
            });


            if (interval == 'never') {
                $('#stgh_get_mails').attr('disabled', true);
            }
            else {
                $('#stgh_get_mails').removeAttr('disabled');
            }


            // Заполнить поле Порт в зависимости от полей Протокол и Encryption
            var protocol = $('select[name="stgh_mail_protocol_visible"]');
            var encryption = $('select[name="stgh_mail_encryption"]');
            var port = $("#stgh_mail_port");
            var function_set_port = function () {
                var enc_val = encryption.val();
                var protocol_val = protocol.val();
                switch (protocol_val) {
                    case "IMAP":
                        if (enc_val == 'SSL') {
                            port.val('993');
                        } else {
                            port.val('143');
                        }
                        break;
                    case "POP3":
                        if (enc_val == 'SSL') {
                            port.val('995');
                        } else {
                            port.val('110');
                        }
                        break;
                }
            };
            protocol.on("change", function_set_port);
            encryption.on("change", function_set_port);
            // End. Заполнить поле Port

            // Скрываем title на списке тикетов, нужен для Bulk Editor, на всякий случай еще тут ибо css могут переопределить
            $('.column-title').hide();

            /* Hide boxes from BulkEdit */
            // default Status select
            var status_fieldsset = $('select[name="_status"]').parents('fieldset');
            status_fieldsset.hide();
            // Replies textarea
            $(".tax_input_savedreply").hide();
            $(".tax_input_savedreply").prev('span').hide();


            // Show loader when send get help message. Settings - Get help
            $('#stgh_submit_button').click(function () {
                var stgh_gethelp_form_email_value = $("input[name='stgh_gethelp_form_email']").val();

                if ($("input[name='stgh_gethelp_form_name']").val() &&
                    stgh_gethelp_form_email_value &&
                    $("textarea[name='stgh_gethelp_form_msg']").val() &&
                    checkEmail(stgh_gethelp_form_email_value)
                )
                    $("#stgh_loader").show();
            });

            //Меняем местами  normal и slide  для приглядного вида одноколоночного дизайна
            //и поднимаем slide повыше
            var pc1 = $("#postbox-container-1");
            var pc2 = $("#postbox-container-2");
            pc1.css('position', 'relative');
            pc1.css('top', '-41px');
            pc2.insertBefore(pc1);

            // Hide incoming mail settings for mail client
            var old_settings = [];
            old_settings.host = $('#stgh_mail_server').val();
            old_settings.port = $('#stgh_mail_port').val();
            old_settings.ssl = $('select[name="stgh_mail_encryption"]').val();

            var stgh_mail_provider_list = $('select[name="stgh_mail_protocol_visible"]');
            stgh_mail_provider_list.on('change',function () {
                if ($.inArray($(this).val(), ['Gmail', 'Yahoo', 'Outlook', 'Yandex']) >= 0) {
                    $('#stgh_mail_protocol').val("IMAP");
                    var settings = getIncomingMailServerSettings($(this).val());
                    hideSettingsFields();

                    // set default settings
                    $('#stgh_mail_server').val(settings.host);
                    $('#stgh_mail_port').val(settings.port);
                    $('select[name="stgh_mail_encryption"]').val(settings.ssl);
                } else {
                    $('#stgh_mail_protocol').val($(this).val());
                    // show current settings
                    /*if(Object.keys(old_settings).length != 0){
                     $('#stgh_mail_server').val(old_settings.host);
                     $('#stgh_mail_port').val(old_settings.port);
                     $('select[name="stgh_mail_encryption"]').val(old_settings.ssl);
                     }*/
                    showSettingsFields();
                }
            });
            //stgh_mail_provider_list.change();
            // END Hide incoming mail settings for mail client

            $("#stgh-added-info").parent((".meta-box-sortables")).removeClass('meta-box-sortables');
            $("#stgh-comments-form .hndle").removeClass('hndle');
            $("#stgh-comments .hndle").removeClass('hndle');
        });

        if ($("#stgh_enable_open").is(':checked')) {
            $('#wp-stgh_content_auto_reply-wrap').parent().parent('tr').show();
            $('#wp-stgh_content_auto_reply_agent-wrap').parent().parent('tr').show();
        }
        $("#stgh_enable_open").change(function () {
            if (this.checked) {
                $('#wp-stgh_content_auto_reply-wrap').parent().parent('tr').show();
                $('#wp-stgh_content_auto_reply_agent-wrap').parent().parent('tr').show();
            } else {
                $('#wp-stgh_content_auto_reply-wrap').parent().parent('tr').hide();
                $('#wp-stgh_content_auto_reply_agent-wrap').parent().parent('tr').hide();
            }
        });

        if ($("#stgh_smtp_settings_enabled").is(':checked')) {
            $('#stgh_mail_sender_server').parent().parent('tr').show();
            $('#stgh_mail_sender_login').parent().parent('tr').show();
            $('#stgh_mail_sender_pwd').parent().parent('tr').show();
            $('select[name="stgh_mail_sender_encryption"]').parent().parent('tr').show();
            $('#stgh_mail_sender_port').parent().parent('tr').show();

            $('label[for = "stgh_sender_email"]').parent().parent('tr').hide();
            $('#stgh_new_sender_email').parent().parent('tr').show();
        }

        $("#stgh_smtp_settings_enabled").change(function () {
            if (this.checked) {
                $('#stgh_mail_sender_server').parent().parent('tr').show();
                $('#stgh_mail_sender_login').parent().parent('tr').show();
                $('#stgh_mail_sender_pwd').parent().parent('tr').show();
                $('select[name="stgh_mail_sender_encryption"]').parent().parent('tr').show();
                $('#stgh_mail_sender_port').parent().parent('tr').show();

                $('label[for = "stgh_sender_email"]').parent().parent('tr').hide();
                $('#stgh_new_sender_email').parent().parent('tr').show();

            } else {
                $('#stgh_mail_sender_server').parent().parent('tr').hide();
                $('#stgh_mail_sender_login').parent().parent('tr').hide();
                $('#stgh_mail_sender_pwd').parent().parent('tr').hide();
                $('select[name="stgh_mail_sender_encryption"]').parent().parent('tr').hide();
                $('#stgh_mail_sender_port').parent().parent('tr').hide();

                $('label[for = "stgh_sender_email"]').parent().parent('tr').show();
                $('#stgh_new_sender_email').parent().parent('tr').hide();
            }
        });


        /**
         * jQuery Select2
         * http://select2.github.io/select2/
         */
        if (jQuery().stgselect2 && $('select.stgh-select2').length) {
            $('select.stgh-select2:visible').stgselect2();
        }

        jQuery('#publish').on('click', function (e) {

            var textarea = getCommentValue();
            var auto_draft = $('#auto_draft').val();

            if (!textarea && auto_draft == 1) {
                e.preventDefault();
                alert(stghLocale.alertNoContent);
                return false;
            }



            var contact = $('#stgh-crm-contact-value').val();
            var already = $('[name="_stgh_crm_user_id"]').val();
            if(!contact && !already)
            {
                alert(stghLocale.contactEmpty);
                return false;
            }
            var title = jQuery('#titlediv > #titlewrap > #title');
            if (title.length > 0) {
                title.prop('required', true);

                if (title.val().length == 0) {
                    title.addClass('stgh-error-field').focus();
                    e.preventDefault();
                    return false;
                }
            }


            jQuery(this).val(jQuery('#original_publish').val());
        });

        jQuery('#stgh-update').on('click', function (e) {
            var contact = $('#stgh-crm-contact-value').val();
            var already = $('[name="_stgh_crm_user_id"]').val();
            if(!contact && !already)
            {
                alert(stghLocale.contactEmpty);
                return false;
            }
            var title = jQuery('#titlediv > #titlewrap > #title');
            if (title.length > 0) {
                title.prop('required', true);

                if (title.val().length == 0) {
                    title.addClass('stgh-error-field').focus();
                    e.preventDefault();
                    return;
                }
            }
            var textarea = getCommentValue();
            var auto_draft = $('#auto_draft').val();
            if (!textarea && auto_draft == 1) {
                e.preventDefault();
                alert(stghLocale.alertNoContent);
                return false;
            }
        });

        jQuery('#stgh-add-contact').on('click', function (e) {
            var title = jQuery('#titlediv > #titlewrap > #title');
            if (title.length > 0) {
                title.prop('required', true);

                if (title.val().length == 0) {
                    title.addClass('stgh-error-field').focus();
                    e.preventDefault();
                    return;
                }
            }

            var email = $('[name="stgh_crm_new_contact_email"]').val(),
                name = $('[name="stgh_crm_new_contact_name"]').val();

            if (!checkEmail(email)) {
                alert(stghLocale.alertBademail);
                return false;
            }

            if (name == '') {
                alert(stghLocale.alertEmptyName);
                return false;
            }

            var textarea = getCommentValue();
            var auto_draft = $('#auto_draft').val();
            if (!textarea && auto_draft == 1) {
                e.preventDefault();
                alert(stghLocale.alertNoContent);
                return false;
            }
        });

        jQuery('#stgh-add-contact-agent').on('click', function (e) {
            var email = $('[name="stgh_crm_new_contact_email"]').val(),
                name = $('[name="stgh_crm_new_contact_name"]').val();

            if (!checkEmail(email)) {
                alert(stghLocale.alertBademail);
                return false;
            }

            if (name == '') {
                alert(stghLocale.alertEmptyName);
                return false;
            }

            data = {
                'action': 'stgh_create_user',
                'email': email,
                'name': name
            };

            $.post(ajaxurl, data, function (response) {
                if(response.ID){
                    $('#stgh-contact-crm-autocomplete').val(response.display_name);
                    $('#stgh-crm-contact-value').val(response.ID);
                    $('#stgh-crm-new-contact').hide();
                    $('#stgh-crm-select-form').show();
                }
            });
            return false;
        });


        jQuery('#stgh-do').on('click', function (e) {

            var textarea = getCommentValue();
            var auto_draft = $('#auto_draft').val();

            if (!textarea && auto_draft == 1) {
                e.preventDefault();
                alert(stghLocale.alertNoContent);
                return false;
            }

            var contact = $('#stgh-crm-contact-value').val();
            var already = $('[name="_stgh_crm_user_id"]').val();

            if(!contact && !already)
            {
                alert(stghLocale.contactEmpty);
                return false;
            }
            var title = jQuery('#titlediv > #titlewrap > #title');
            if (title.length > 0) {
                title.prop('required', true);

                if (title.val().length == 0) {
                    title.addClass('stgh-error-field').focus();
                    e.preventDefault();
                    return false;
                }
            }
        });


        jQuery('#title').on('change', function () {
            if (jQuery(this).val().length > 0) {
                jQuery(this).removeClass('stgh-error-field');
            } else {
                jQuery('#title').addClass('stgh-error-field');
            }
        });

        var mainBlock = $('#stgh-related-tickets'),
            userId = mainBlock.data('authorid'),
            postId = mainBlock.data('postid'),
            data = {
                'action': 'stgh_get_related_tickets',
                'post_id': postId,
                'user_id': userId
            },
            relatedTicketResultContent = $('#stgh-related-tickets-content'),
            relatedTicketLoader = $('#stgh-related-tickets-loader');

        // AJAX request
        $.post(ajaxurl, data, function (response) {
            relatedTicketLoader.hide();
            relatedTicketResultContent.show().html(response);
        });

        $('.stgh-metabox-details-click').click(function () {
            var block = $(this).data('block');
            $('#' + block + '-selected').hide();
            $('#' + block + '-select').show();
        });

        $('.stgh-metabox-customfields-click').click(function () {
            var block = $(this).data('block');
            $('#' + block + '-selected').hide();
            $('#' + block + '-select').show();
            $('#' + block + '-select').find('input').prop('disabled',false);
            $('#' + block + '-select').find('textarea').prop('disabled',false);
            $('#' + block + '-select').find('select').prop('disabled',false);
        });

        $('.datepicker').each( function(index,element){
            $(this).datepicker({dateFormat: $(this).attr('dateformat')});
        });

        $('.stgh-ticket-comments, .stgh-ticket').mouseenter(function () {
            $(this).find('.stgh-ticket-controls').css('visibility', 'visible');
        }).mouseleave(function () {
            $(this).find('.stgh-ticket-controls').css('visibility', 'hidden');
        });

        $('#added-info-mail').click(function () {
            $('[name="stgh_reply_to"]').val($(this).find('span').text());
            return false;
        });

        

        $('.stgh-ntab').click(function () {
            
            if ($(this).hasClass("stgh-private-note")) {
                $('#stgh-add-note-advert').show('slow', function () {
                    $(this).delay(1000).hide('slow');
                });
                return;
            }

            
            
            $('.stgh-ntab').removeClass('stgh-ntab-active');

            $(this).addClass('stgh-ntab-active');

            $('.stgh-form-panel').hide();
            $('#' + $(this).attr('data-show')).show();

            $('button[name="stgh-do"]').text($(this).attr('data-label'));
        });

        $('#stgh-change-btn').click(function () {
            $('#stgh-crm-select-form').show();
            $('#stgh-crm-info').hide();
            $('#stgh_ticket_crm .hndle > span').text(stghLocale.selectContactLabel);

            return false;
        });

        $('#stgh-cancel-update-btn').click(function () {
            $('#stgh-crm-select-form').hide();
            $('#stgh-crm-info').show();

            $('#stgh-contact-crm-autocomplete').val('');
            $('#stgh_ticket_crm .hndle > span').text(stghLocale.contactLabel);

            return false;
        });

        $('#stgh-cancel-add-btn').click(function () {
            $('#stgh-crm-new-contact').hide();
            $('#stgh-crm-info').show();

            $('#stgh_ticket_crm .hndle > span').text(stghLocale.contactLabel);

            return false;
        });

        $('#stgh-cancel-add-btn-agent').click(function () {
            $('#stgh-crm-new-contact').hide();
            $('#stgh-crm-select-form').show();

            $('#stgh_ticket_crm .hndle > span').text(stghLocale.contactLabel);

            return false;
        });

        $('#stgh-crm-new-contact-link').click(function () {
            $('#stgh-contact-crm-autocomplete').val('');
            $('#stgh-crm-new-contact').show();
            $('#stgh-crm-select-form').hide();

            $('#stgh_ticket_crm .hndle > span').text(stghLocale.addContactLabel);

            return false;
        });

        $('#stgh-crm-new-contact-link-agent').click(function () {
            $('#stgh-crm-new-contact').show();
            $('#stgh-crm-select-form').hide();

            $('#stgh_ticket_crm .hndle > span').text(stghLocale.addContactLabel);

            return false;
        });


        $('#stgh_chk_conf').click(function () {
            $("#stgh_loader").show();
        });


        $('#stg-rating-anchor').click(function () {
            $.post(ajaxurl + "?callback=?&action=stgh-rating-click", {});
            $(this).parent().text($(this).data('msg-click'));
        });

            });

    function checkEmail(email) {
        //var pattern = /^([a-z0-9_\.-])+@[a-z0-9-]+\.([a-z]{2,4}\.)?[a-z]{2,4}$/i;
        var pattern = /^([a-z0-9_\.-])+@[a-z0-9-\.]+$/i;
        if (pattern.test(email)) {
            return true;
        } else {
            return false;
        }
    }

    function getCommentValue(){
        if ($('#stgh_comment').is(':visible')) {
            return $('#stgh_comment').val();
        } else {
            var editor = tinymce.get('stgh_comment');
            return editor.getContent();
        }
    }



    function getIncomingMailServerSettings(server) {
        var settings = [];
        switch (server) {
            case 'Gmail':
                settings.host = 'imap.gmail.com';
                break;
            case 'Yahoo':
                settings.host = 'imap.mail.yahoo.com';
                break;
            case 'Outlook':
                settings.host = 'imap-mail.outlook.com';
                break;
            case 'Yandex':
                settings.host = 'imap.yandex.com';
                break;
            default:
                settings.host = '';
        }
        settings.port = 993;
        settings.ssl = 'SSL';

        return settings;
    }

    function showSettingsFields() {
        $('#stgh_mail_server').parents('tr').show();
        $('#stgh_mail_port').parents('tr').show();
        $('select[name="stgh_mail_encryption"]').parents('tr').show();
        // show on wizard_page
        $('#stgh_mail_server').show();
        $('#stgh_mail_port').show();
        $('select[name="stgh_mail_encryption"]').show();
        $('p.stgh_hide').show();
    }

    function hideSettingsFields() {
        // hide settings
        $('#stgh_mail_server').parents('tr').hide();
        $('#stgh_mail_port').parents('tr').hide();
        $('select[name="stgh_mail_encryption"]').parents('tr').hide();
        // hide on wizard_page
        $('#stgh_mail_server').hide();
        $('#stgh_mail_port').hide();
        $('select[name="stgh_mail_encryption"]').hide();
        $('p.stgh_hide').hide();
    }

}(jQuery));

