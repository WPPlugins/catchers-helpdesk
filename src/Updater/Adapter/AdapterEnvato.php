<?php

namespace StgHelpdesk\Updater\Adapter;


class AdapterEnvato
{
    private $_personalToken;
    private $_license;
    private $_itemId;
    private $_pluginInfo;

    public function __construct($envatoToken,$envatoProductId,$license,$currentVersion)
    {
        $this->_personalToken = $envatoToken;
        $this->_license = $license;
        $this->_itemId = $envatoProductId;
        $this->_pluginInfo = null;
        $this->_currentVersion = $currentVersion;
    }

    public function getPluginInfoFromMycatchers($version = false){
        $updateAdapter = AdapterFactory::getAdapter('Mycatchers',array( 'license' => $this->_license ),$this->_currentVersion);
        return $updateAdapter->getPluginInfo($version);
    }

    public function getPluginInfo($version = false){
        if($version === false){
            $updateAdapter = AdapterFactory::getAdapter('Mycatchers',array( 'license' => $this->_license),$this->_currentVersion);
            return $updateAdapter->getPluginInfo();
        }
        else{
            if(!empty($this->_pluginInfo))
            {
                return $this->_pluginInfo;
            }
            else{
                $args = array(
                    'headers' => array(
                        'Authorization' => 'Bearer ' . $this->_personalToken,
                    ),
                    'timeout' => 30,
                );
                $url  = 'https://api.envato.com/v2/market/catalog/item?id=' . $this->_itemId;

                $response = wp_remote_get( esc_url_raw( $url ), $args );
                $response_code    = wp_remote_retrieve_response_code( $response );
                $response_message = wp_remote_retrieve_response_message( $response );

                if ( 200 !== $response_code && ! empty( $response_message ) ) {
                    return new \WP_Error( $response_code, $response_message );
                } elseif ( 200 !== $response_code ) {
                    return new \WP_Error( $response_code, __( 'An unknown API error occurred.', STG_HELPDESK_TEXT_DOMAIN_NAME ) );
                } else {
                    $out = json_decode( wp_remote_retrieve_body( $response ), true );
                    if ( null === $out ) {
                        return new \WP_Error( 'api_error', __( 'An unknown API error occurred.', STG_HELPDESK_TEXT_DOMAIN_NAME ) );
                    }
                    $this->_pluginInfo = $out;
                    return $out;
                }
            }
        }

    }

    public function getPluginPackageLink(){
        $args = array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->_personalToken,
            ),
            'timeout' => 30,
        );

        $url = 'https://api.envato.com/v3/market/buyer/download?item_id=' . $this->_itemId . '&shorten_url=true';

        $response = wp_remote_get( esc_url_raw( $url ), $args );
        $response_code    = wp_remote_retrieve_response_code( $response );
        $response_message = wp_remote_retrieve_response_message( $response );

        if ( 200 !== $response_code && ! empty( $response_message ) ) {
            return new \WP_Error( $response_code, $response_message );
        } elseif ( 200 !== $response_code ) {
            return new \WP_Error( $response_code, __( 'An unknown API error occurred.', STG_HELPDESK_TEXT_DOMAIN_NAME ) );
        } elseif ( 200 == $response_code ) {
            $out = json_decode(wp_remote_retrieve_body($response), true);
            if (null === $out) {
                return new \WP_Error('api_error', __( 'An unknown API error occurred.', STG_HELPDESK_TEXT_DOMAIN_NAME ));
            }
        }
            return $out['wordpress_plugin'];

    }

    public function getRemoteVersion(){
        self::getPluginInfoFromMycatchers(true);

        $pluginInfo = $this->getPluginInfo(true);
        if(is_wp_error($pluginInfo))
            return false;

        $pluginMeta = $pluginInfo['wordpress_plugin_metadata'];
        return $pluginMeta['version'];
    }
}