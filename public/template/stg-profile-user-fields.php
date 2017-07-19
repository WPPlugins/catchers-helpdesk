<h3><?php _e('CRM Profile', STG_HELPDESK_TEXT_DOMAIN_NAME); ?></h3>

<table class="form-table">
    <tr>
        <th>
            <label for="_stgh_crm_company"><?php _e('Company', STG_HELPDESK_TEXT_DOMAIN_NAME); ?></label>
        </th>
        <td>
            <input type="text" name="_stgh_crm_company" id="_stgh_crm_company"
                   value="<?php echo esc_attr(get_the_author_meta('_stgh_crm_company', $user->ID)); ?>"
                   class="regular-text"/><br/>
            <span
                class="description"><?php _e('Please enter your crm company.', STG_HELPDESK_TEXT_DOMAIN_NAME); ?></span>
        </td>
    </tr>

    <tr>
        <th>
            <label for="_stgh_crm_skype"><?php _e('Skype', STG_HELPDESK_TEXT_DOMAIN_NAME); ?></label>
        </th>
        <td>
            <input type="text" name="_stgh_crm_skype" id="_stgh_crm_skype"
                   value="<?php echo esc_attr(get_the_author_meta('_stgh_crm_skype', $user->ID)); ?>"
                   class="regular-text"/><br/>
            <span class="description"><?php _e('Please enter your crm skype.', STG_HELPDESK_TEXT_DOMAIN_NAME); ?></span>
        </td>
    </tr>

    <tr>
        <th>
            <label for="_stgh_crm_phone"><?php _e('Phone', STG_HELPDESK_TEXT_DOMAIN_NAME); ?></label>
        </th>
        <td>
            <input type="text" name="_stgh_crm_phone" id="_stgh_crm_phone"
                   value="<?php echo esc_attr(get_the_author_meta('_stgh_crm_phone', $user->ID)); ?>"
                   class="regular-text"/><br/>
            <span class="description"><?php _e('Please enter your crm phone.', STG_HELPDESK_TEXT_DOMAIN_NAME); ?></span>
        </td>
    </tr>

    <tr>
        <th>
            <label for="_stgh_crm_site"><?php _e('Website', STG_HELPDESK_TEXT_DOMAIN_NAME); ?></label>
        </th>
        <td>
            <input type="text" name="_stgh_crm_site" id="_stgh_crm_site"
                   value="<?php echo esc_attr(get_the_author_meta('_stgh_crm_site', $user->ID)); ?>"
                   class="regular-text"/><br/>
            <span
                class="description"><?php _e('Please enter your crm website.', STG_HELPDESK_TEXT_DOMAIN_NAME); ?></span>
        </td>
    </tr>

    <tr>
        <th>
            <label for="_stgh_crm_position"><?php _e('Position', STG_HELPDESK_TEXT_DOMAIN_NAME); ?></label>
        </th>
        <td>
            <input type="text" name="_stgh_crm_position" id="_stgh_crm_position"
                   value="<?php echo esc_attr(get_the_author_meta('_stgh_crm_position', $user->ID)); ?>"
                   class="regular-text"/><br/>
            <span
                class="description"><?php _e('Please enter your crm position.', STG_HELPDESK_TEXT_DOMAIN_NAME); ?></span>
        </td>
    </tr>
</table>