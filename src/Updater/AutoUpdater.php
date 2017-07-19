<?php

namespace StgHelpdesk\Updater;

use StgHelpdesk\Updater\Adapter\AdapterFactory;

class AutoUpdater
{
    private $_adapterName;
    private $_currentVersion;
    private $_pluginSlug;
    private $_slug;
    private $_adapterData;
    private $_timeout;

    public function __construct($adapterName, $currentVersion, $data){
        $this->_adapterName = $adapterName;
        $this->_currentVersion = $currentVersion;
        $this->_pluginSlug = STG_HELPDESK_NAME."/".STG_HELPDESK_NAME.".php";
        $this->_timeout = 12*60*60;

        list ($temp1, $temp2) = explode('/', $this->_pluginSlug);
        $this->_slug = str_replace('.php', '', $temp2);

        $this->_adapterData = $data[$adapterName];

        //add_filter( 'pre_set_site_transient_update_plugins', array( &$this, 'checkPluginUpdate' ) );
        add_filter( 'site_transient_update_plugins', array( &$this, 'checkPluginUpdate' ) );

        add_filter( 'plugins_api', array( &$this, 'getPluginInfo' ), 10, 3 );
            }


    public function checkPluginUpdate($transient){

        $prev = stgh_get_option('prev_check_u', false);

        $remoteVersion = stgh_get_option('prev_check_version',false);

        if(!$prev || (time() > $prev + $this->_timeout) || (isset($_GET['force-check']) && $_GET['force-check'] == '1') ) {
            $updateAdapter = AdapterFactory::getAdapter($this->_adapterName, $this->_adapterData, $this->_currentVersion);
            $remoteVersion = $updateAdapter->getRemoteVersion();

            stgh_set_option('prev_check_u',time());
            stgh_set_option('prev_check_version',$remoteVersion);
        }

                return $transient;

    }


    public function getPluginInfo( $result, $action, $args ){
        if (isset($args->slug) && $args->slug === $this->_slug ) {

            $updateAdapter = AdapterFactory::getAdapter($this->_adapterName,$this->_adapterData,$this->_currentVersion);
            $pluginInfo = $updateAdapter->getPluginInfo();


                        return false;
            
                    } else {
            return false;
        }
    }
    
}