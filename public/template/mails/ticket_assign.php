<p><?php _e('Hello', STG_HELPDESK_TEXT_DOMAIN_NAME); ?>, {agent_name}</p>
<p><?php _e('Ticket has been assigned to you', STG_HELPDESK_TEXT_DOMAIN_NAME); ?>: <a href="{ticket_admin_url}">#{ticket_id}</a>
    "{ticket_title}".</p>
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