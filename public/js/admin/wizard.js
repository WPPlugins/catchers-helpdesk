(function ($) {

    $(function () {

        jQuery(document).ready(function ($) {

            var stgh_providers = ['Gmail', 'Yahoo', 'Outlook', 'Yandex'];

            window.max_pages = 3; // Form amount
            window.loc = locationWithoutAttr('step');
            var step;

            // F5 страницы
            if (loc[1] != undefined && loc[1] > 0 && loc[1] < max_pages + 1) {
                $('div.stgh_wiz_content').each(toggleFormHidden);
            }


            // Заполнять селектор провайдера в зависимости от введенного email
            $('#stgh_stg_mail_login').blur(function () {
                $('input[name="stgh_wiz_email"]').val($(this).val());
                var provider = getMailProvider($(this).val());
                var stgh_mail_protocol_visible_selector = $("select[name='stgh_mail_protocol_visible']");

                stgh_mail_protocol_visible_selector.val("IMAP");
                stgh_providers.forEach(function (element) {
                    if (provider && provider.toLowerCase() == element.toLowerCase()) {
                        stgh_mail_protocol_visible_selector.val(element);
                        return false;
                    }
                });
                stgh_mail_protocol_visible_selector.change();
            });


            // Back button click
            $("button.stgh_wiz_back_button").click(function (e) {
                // Loader
                $("img.stgh_wiz_img").hide();
                $(this).next("#stgh_wizard_loader").show();

                e.preventDefault();
                window.loc = locationWithoutAttr('step');
                history.replaceState({step: loc[1]}, document.title, loc[0] + "&step=" + (parseInt(loc[1]) - 1));
                $('div.stgh_wiz_content').each(toggleFormHidden);

            });

            // Next button click
            $("button.stgh_wiz_next_button").click(function (e) {
                e.preventDefault();
                $("img.stgh_wiz_img").hide();

                $(this).prev("img").show();
                loc = locationWithoutAttr('step');
                if (loc[1] != undefined && loc[1] != "0") {
                    // Go to next page
                    current = parseInt(loc[1]);
                    step = current + 1;
                    history.replaceState({}, document.title, loc[0] + "&step=" + step);
                } else {
                    step = 2;
                    history.replaceState({step: step}, document.title, loc[0] + "&step=" + step);
                }

                // обрабатываем форму
                switch ($(this).data('method')) {
                    case 'support_email':
                        var new_email = $('input[name="stgh_wiz_email"]').val();
                        if ($("#stgh_wiz_prev_email").val() != new_email) {
                            var data = {
                                'action': 'stgh_save_support_email_option',
                                'email': new_email
                            };
                            $.post(ajaxurl, data, function () {
                                $('div.stgh_wiz_content').each(toggleFormHidden);
                                $('#stgh_stg_mail_login').val(new_email);
                                // fill hidden field
                                $('#stgh_wiz_prev_email').val(new_email);
                                // fill selector
                                $('#stgh_stg_mail_login').blur();

                            });
                        } else {
                            $('div.stgh_wiz_content').each(toggleFormHidden);
                        }
                        break;
                    default:
                        $('div.stgh_wiz_content').each(toggleFormHidden);
                }
                return false;
            });


            // Test connection button click
            $("#stgh_chk_conf").click(function () {
                $(this).next("#stgh_wizard_loader").show();
            });
        });

    });

    // Проверка заполненности требуемых полей формы
    function checkInput(form) {
        $(form).find(':required').each(function () {
            if ($(this).val() == '') {
                // Если поле пустое
                return false;
            }
            return true;
        });
    }

    function toggleFormHidden() {
        var loc = locationWithoutAttr('step');
        var number = $(this).index('div.stgh_wiz_content');
        var step = parseInt(loc[1]) - 1;
        if (step > max_pages)
            step = max_pages;
        // Show or hide forms
        if (number == step) {
            $(this).removeClass('stgh_wiz_hidden');
        }
        else if (!$(this).hasClass('stgh_wiz_hidden')) {
            $(this).addClass('stgh_wiz_hidden');
        }
    }

    function locationWithoutAttr(prmName) {
        var res = '';
        var d = location.href.split("#")[0].split("?");
        var base = d[0];
        var query = d[1];
        var value;
        if (query) {
            var params = query.split("&");
            for (var i = 0; i < params.length; i++) {
                var keyval = params[i].split("=");

                if (keyval[0] == prmName) {
                    value = keyval[1];
                }
                if (keyval[0] !== prmName) {
                    res += (res ? '&' : '') + params[i];
                }
            }
        }

        if (value != undefined)
            value = parseInt(value);

        // Не выходим за количество форм
        if (value < 1 || value > parseInt(max_pages)) {
            value = 1;
        }
        return [base + '?' + res, value];
    }

    /**
     * Get mail provider from e-mail address
     *
     * @param email
     */
    function getMailProvider(email) {
        var reg = /@[a-z0-9-]+\./i;
        var result = email.match(reg);
        if (result && result[0]) {
            return result[0].slice(1, -1);
        }
        return false;
    }


}(jQuery));

