<p><?php _e('User ', STG_HELPDESK_TEXT_DOMAIN_NAME); ?>
    {client_name}
    <?php _e('just replied to ticket', STG_HELPDESK_TEXT_DOMAIN_NAME); ?>
    <a href="{ticket_admin_url}">#{ticket_id}</a>:"{ticket_title}"
</p>

<div class="MsoNormal" align="center" style="text-align:center">
    <span style="font-size:9.5pt;font-family:'Verdana','sans-serif';color:#484848">
        <hr size="1" width="100%" align="center">
    </span>
</div>

<p>{message}</p>

<div class="MsoNormal" align="center" style="text-align:center">
    <span style="font-size:9.5pt;font-family:'Verdana','sans-serif';color:#484848">
        <hr size="1" width="100%" align="center">
    </span>
</div>
<p>
    <i>
        <span style="font-size:7.5pt;font-family:'Verdana','sans-serif';color:#484848">
            <?php _e('You have received this notification because you have either subscribed to it, or are involved in it.', STG_HELPDESK_TEXT_DOMAIN_NAME); ?>
        </span>
    </i>
</p>

