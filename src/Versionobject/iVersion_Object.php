<?php
namespace StgHelpdesk\Versionobject;


interface iVersion_Object
{

    public function register_new_user($user_login, $user_email);

    public function registerSaveTicketFields();

    public function customEnqueueStyles();

    public function getPluginIcon();

    public function getProVerMenuStyle();

    public function getTitleSelector();

    public function enqueueAdminScripts();

    public function enqueueHelpcatcherScript();

    public function getHelpcatcherSettings();

} 