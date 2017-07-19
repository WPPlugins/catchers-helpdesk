<p>{message}</p>

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
{tracking_image}