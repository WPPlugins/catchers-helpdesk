<div class="wrap about-wrap">
    <h1>Welcome to the catchers helpdesk</h1>

    <p>Catchers Helpdesk lets you catch feedback and requests directly from

        your website. When you create an account in Catchers Helpdesk, you

        automatically get support desk.</p>

    <p>All the entries submitted from the Catchers Helpdesk form, which you

        have embedded on your website, are automatically converted into

        tickets that you can start working on from your account.</p>

    <h3>Getting started</h3>

    <p>
        1. For create Submit Ticket page - click "CREATE USER PAGES" button. To

        edit, please go to Pages in your left navigation and edit the relevant page.

        Please remember to keep the shortcode on the page so that

        the submit ticket form shows correctly.
<?php
/*
        1. The Submit Ticket page is created automatically upon activation. To

        edit, please go to Pages in your left navigation and edit the relevant

        page. Please remember to keep the shortcode on the page so that

        the submit ticket form shows correctly.
*/?>
    </p>

    <p>2. E-mail settings and notifications are sent out when there is a new

        support ticket as well as when there are new support ticket

        responses – these can be changed in the settings page.</p>

    <p>3. Administrators/team members can manage and respond to tickets via the

        administration panel and responses are sent to users via email.</p>

    <p>4. See and try how it works on <a href="http://demo.mycatchers.com/">http://demo.mycatchers.com/</a></p>

    <p><a class="button button-primary stgh_welcome_page_start"
          href="<?php echo stgh_link_to_wizard(); ?>">START</a></p>

    <p><a class="button button-primary stgh_welcome_page_create"
          href="<?php echo stgh_link_to_create_user_pages(); ?>">CREATE USER PAGES</a></p>

    <p><a class="button button-primary stgh_welcome_page_skip"
          href="<?php echo stgh_link_to_skip_setting(); ?>">SKIP SETTING</a></p>

    <!--img height="100%" width="100%" src="<?php //echo STG_HELPDESK_URL . 'images/ticket-screenshot.jpg'; ?>"-->
</div>