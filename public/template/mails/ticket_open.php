<p>{message_options}</p>
<p>
    <?php _e('On ', STG_HELPDESK_TEXT_DOMAIN_NAME); ?> {date},
    {client_email} <?php _e('wrote', STG_HELPDESK_TEXT_DOMAIN_NAME); ?>:
</p>

<span
    style="font-size:9.5pt;font-family:'Verdana', 'sans-serif'; color:#484848;display: block;border-left: 1px solid #C5C5C5;padding-left: 10px;margin-left: 10px;margin-top: 10px;">
{message}
</span>
<br>
<div class="MsoNormal" align="center" style="text-align:center">
    <span style="font-size:9.5pt;font-family:'Verdana','sans-serif';color:#484848">
        <hr size="1" width="100%" align="center">
    </span>
</div>
<?php if(stgh_get_option('show_ticket_link')):?>
<p>
    <i>
        <span style="font-size:7.5pt;font-family:'Verdana','sans-serif';color:#484848">
            <?php _e('View ticket in your web browser ', STG_HELPDESK_TEXT_DOMAIN_NAME); ?> <a href="{ticket_url}">#{ticket_id}</a>
        </span>
    </i>
</p>
<?php endif; ?>