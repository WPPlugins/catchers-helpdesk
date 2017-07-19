(function ($) {
    "use strict";

    $(function () {

        if ($("*").is('#stgh-contact-crm-autocomplete')) { // if element exists

            $('#stgh-contact-crm-autocomplete').autocomplete({
                source: function (request, response) {
                    $.getJSON(ajaxurl + "?callback=?&action=stgh-autocomplete", request, function (data) {
                        response($.map(data, function (item) {
                            return {
                                value: item.id,
                                label: item.label,
                                first: item.first_name,
                                last: item.last_name,
                                email: item.email
                            }
                        }));
                    });
                },
                select: function (event, ui) {
                    event.preventDefault();

                    $("#stgh-contact-crm-autocomplete").val(ui.item.label);
                    $("#stgh-crm-contact-value").val(ui.item.value);
                },
                focus: function (event, ui) {
                    event.preventDefault();

                    $("#stgh-contact-crm-autocomplete").val(ui.item.label);
                },
                minLength: 3
            }).data("ui-autocomplete")._renderItem = function (ul, item) {

                if (item.first != "" && item.last != "") {
                    return $("<li>")
                        .append("<b>" + item.last + " " + item.first + "</b><br>" + item.email)
                        .appendTo(ul);
                }

                if (item.email != item.label) {
                    return $("<li>")
                        .append("<b>" + item.label + "</b><br>" + item.email)
                        .appendTo(ul);
                }

                return $("<li>")
                    .append("<b>" + item.label + "</b>")
                    .appendTo(ul);
            };
        }

    });


}(jQuery));

