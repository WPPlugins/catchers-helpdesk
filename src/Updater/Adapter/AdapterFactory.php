<?php

namespace StgHelpdesk\Updater\Adapter;


class AdapterFactory
{

    protected static $instances = array();
    private static $_envatoName = 'Envato';
    private static $_mycatchersName = 'Mycatchers';

    private function __construct()
    {
    }

    /**
     * @param $token
     * @param $itemId
     * @return StgHelpdesk\Updater\Adapter\AdapterEnvato;
     */
    public static function getEnvatoAdapter($token, $itemId, $license,$currentVersion){
        if (isset(self::$instances[self::$_envatoName])) {
            return self::$instances[self::$_envatoName];
        } else {
            $className = __NAMESPACE__ . '\\' . 'Adapter'.ucfirst(strtolower(self::$_envatoName));

            if (class_exists($className)) {
                return self::$instances[self::$_envatoName] = new $className($token,$itemId,$license,$currentVersion);
            }
            else
                return false;
        }
    }

    /**
     * @param $license
     * @return StgHelpdesk\Updater\Adapter\AdapterMycatchers;
     */
    public static function getMycatchersAdapter($license,$currentVersion){
        if (isset(self::$instances[self::$_mycatchersName])) {
            return self::$instances[self::$_mycatchersName];
        } else {
            $className = __NAMESPACE__ . '\\' . 'Adapter'.ucfirst(strtolower(self::$_mycatchersName));

            if (class_exists($className)) {
                return self::$instances[self::$_mycatchersName] = new $className($license,$currentVersion);
            }
            else
                return false;
        }
    }

    public static function getAdapter($name,$data,$currentVersion){
        switch($name){
            case self::$_envatoName:
                return self::getEnvatoAdapter($data['token'],$data['itemId'],$data['license'],$currentVersion);
                break;
            case self::$_mycatchersName:
                return self::getMycatchersAdapter($data['license'],$currentVersion);
                break;
        }
    }

}