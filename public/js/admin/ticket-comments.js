(function ($) {
    "use strict";

    $(function () {

        var data, btnEdit, btnQuote, btnDelete, btnCancel, btnSave, btnSpam, commentId, editorId, comment, controls, wpEditor;

        btnEdit = $('.stgh-edit');
        btnQuote = $('.stgh-quote');
        btnDelete = $('.stgh-delete');
        btnCancel = $('.stgh-editcancel');
        btnSpam = $('.stgh-spam');

        /*
         Check if TinyMCE is active in WordPress
         http://stackoverflow.com/a/1180199/1414881
         */
        var is_tinyMCE_active = false;
        if (typeof (tinyMCE) != "undefined") {
            if (tinyMCE.activeEditor === null || tinyMCE.activeEditor.isHidden() !== false) {
                is_tinyMCE_active = true;
            }
        }

        if (is_tinyMCE_active) {

            // There is an instance of wp_editor
            btnEdit.on('click', function (event) {
                event.preventDefault();
                // debugger;

                btnEdit = $(this);
                controls = $(this).parents('.stgh-ticket-controls');
                commentId = $(this).data('replyid');
                editorId = $(this).data('wysiwygid');
                comment = $($(this).data('origin'));
                btnSave = $('#stgh-edit-submit-' + commentId);

                // Update the UI
                controls.hide();
                comment.hide();
                wpEditor = $('.stgh-editwrap-' + commentId);
                // if wp editor was created
                if (wpEditor.hasClass('wp_editor_active')) {
                    wpEditor.show();
                } else {
                    // AJAX data
                    data = {
                        'action': 'stgh_edit_comment',
                        'post_id': commentId,
                        'editor_id': editorId
                    };

                    // AJAX request
                    $.post(ajaxurl, data, function (response) {
                        $('.stgh-editwrap-' + commentId).addClass('wp_editor_active').show();
                        $('.stgh-editwrap-' + commentId + ' .stgh-editor').html(response);

                        tinyMCE.init(tinyMCEPreInit.mceInit[data.editor_id]);
                    });

                }

                // Save the reply
                btnSave.on('click', function (e) {
                    e.preventDefault();

                    var tinyMCEContent = tinyMCE.get(editorId).getContent();
                    if (!tinyMCEContent) { // No empty edit
                        alert(stghLocale.alertEmptyEdit);
                        return false;
                    }
                    // Update the UI
                    controls.show();
                    btnSave.prop('disabled', true).val('Saving...');

                    var data = {
                        'action': 'stgh_edit_comment_save',
                        'comment_id': commentId,
                        'comment_content': tinyMCEContent
                    };

                    $.post(ajaxurl, data, function (response) {
                        // check if the response is an integer
                        if (Math.floor(response) == response && $.isNumeric(response)) {

                            // Revert to save button
                            btnSave.prop('disabled', false).val('Save changes');
                            comment.html(tinyMCEContent).show();
                            wpEditor.hide();
                            controls.show();
                        } else {
                            alert(response);
                        }
                    });
                });

                // Cancel
                btnCancel.on('click', function (e) {
                    e.preventDefault();

                    var data = {
                        'action': 'wp_editor_content_ajax',
                        'post_id': commentId
                    };
                    $.post(ajaxurl, data, function (response) {
                        // Restore the original wp_editor content
                        tinyMCE.get(editorId).setContent(response);

                        // Update the UI
                        comment.show();
                        wpEditor.hide();
                        controls.show();
                    });
                });
            });

            // Quote
            btnQuote.on('click', function (event) {
                event.preventDefault();

                commentId = $(this).data('replyid');
                comment = '<blockquote>' + $('#stgh-comment-' + commentId).html() + '</blockquote>';
                fillToMail($(this).data('mail'));


                if ($('#stgh_comment').is(':visible')) {
                    $('#stgh_comment').insertAtCaret(comment);
                    $('#stgh_comment').setSelection(0, 0);
                    $(document).scrollTop(0);
                    return;
                } else {
                    if ($('iframe#stgh_comment_ifr').is(':visible')) {
                        var textarea = $('iframe#stgh_comment_ifr').contents().find('#tinymce');
                        textarea.html(textarea.html() + comment);
                        textarea.focus();
                        $(document).scrollTop(textarea.offset().top);
                        return;
                    }
                }


                if ($('#stgh_comment_private').is(':visible')) {
                    $('#stgh_comment_private').insertAtCaret(comment);
                    $('#stgh_comment_private').setSelection(0, 0);
                    $(document).scrollTop(0);
                    return;
                } else {
                    if ($('iframe#stgh_comment_private_ifr').is(':visible')) {

                        var textarea = $('iframe#stgh_comment_private_ifr').contents().find('#tinymce');
                        textarea.html(textarea.html() + comment);
                        textarea.focus();
                        $(document).scrollTop(textarea.offset().top);
                        return;
                    }
                }
            });


            btnSpam.click(function () {
                return !!confirm(stghLocale.alertSpam);
            });

            btnDelete.click(function () {
                return !!confirm(stghLocale.alertDelete);
            });

        } else {
            // There is NO instance of wp_editor
            btnEdit.on('click', function (event) {
                event.preventDefault();
                alert(stghLocale.alertNoTinyMCE);
            });
        }

        // Validate no empty reply and email
        $('[name="stgh-do"]').on('click', function (e) {
            var textarea, comment_type, check_email = true;
            comment_type = $('li.stgh-ntab-active').data('show');

            // Reply
            if (comment_type == 'stgh-form-agent') {

                if ($('#stgh_comment').is(':visible')) {
                    textarea = $('#stgh_comment');
                    if (!textarea.val()) {
                        e.preventDefault();
                        alert(stghLocale.alertNoContent);
                        return false;
                    }
                }
                else {
                    if (tinyMCE.get('stgh_comment').getContent() == "") {
                        e.preventDefault();
                        alert(stghLocale.alertNoContent);
                        return false;
                    }

                }

                            }
            // Private note
            else if (comment_type == 'stgh-form-private') {


                if ($('#stgh_comment_private').is(':visible')) {
                    textarea = $('#stgh_comment_private');
                    if (!textarea.val()) {
                        e.preventDefault();
                        alert(stghLocale.alertNoContent);
                        return false;
                    }
                }
                else {

                    if (tinyMCE.get('stgh_comment_private').getContent() == "") {
                        e.preventDefault();
                        alert(stghLocale.alertNoContent);
                        return false;
                    }

                }

            }
            return true;
        })

    });

    function checkEmail(email) {
        //var pattern = /^([a-z0-9_\.-])+@[a-z0-9-]+\.([a-z]{2,4}\.)?[a-z]{2,4}$/i;
        var pattern = /^([a-z0-9_\.-])+@[a-z0-9-\.]+$/i;

        if (pattern.test(email)) {
            return true;
        } else {
            alert(stghLocale.alertBademail);
            return false;
        }
    }

    function fillToMail(mail) {
        if (!mail) {
            return false;
        }
        $('label.stgh-label-to-cc-bcc-input').html(mail);
        $('[name="stgh_reply_to"]').val(mail);
    }
}(jQuery));