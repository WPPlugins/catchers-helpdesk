<p><?php _e('Hello', STG_HELPDESK_TEXT_DOMAIN_NAME); ?>,</p>
<p>
    <?php _e('We just got your help request! And do our best to answer emails as soon as possible, with most inquiries receiving a response within about a day.', STG_HELPDESK_TEXT_DOMAIN_NAME); ?>
</p>
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
<p>
    <i>
        <span style="font-size:7.5pt;font-family:'Verdana','sans-serif';color:#484848">
            <?php _e("To track your email, we've tagged it with a ticket code", STG_HELPDESK_TEXT_DOMAIN_NAME); ?>: <a
                href="{ticket_url}">#{ticket_id}</a>
        </span>
    </i>
</p>