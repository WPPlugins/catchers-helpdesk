<?php

namespace StgHelpdesk\Versionobject;


class Version_Object_Factory
{

    protected static $instances = array();

    private function __construct()
    {
    }

    private static function parseVersion($version)
    {
        return str_replace('.', '_', $version);
    }

    public static function getObject()
    {
        $version = self::parseVersion(get_bloginfo('version'));

        if (isset(self::$instances[$version])) {
            return self::$instances[$version];

        } else {
            $class_name = __NAMESPACE__ . '\\' . 'Version_Object_' . $version;
            // Version class
            if (!class_exists($class_name)) {
                /*
                 *  If class version does not exist, take the nearest younger version
                 */
                $class_name = self::getNearVersionObject();
            }
            return self::$instances[$version] = new $class_name();
        }
    }

    /**
     * Gets object nearest to the current version
     */
    public static function getNearVersionObject()
    {
        $version = self::parseVersion(get_bloginfo('version'));

        $version_parts = explode('_', $version);

        // To last octet
        if (isset($version_parts['2']) && !empty($version_parts['2'])) {

            $release = (int)$version_parts['2'];
            unset($version_parts['2']);

            $version = implode('_', $version_parts);

            // Looking for the nearest younger version
            for ($i = $release; $i >= 0; $i--) {
                $class_name = __NAMESPACE__ . '\\' . 'Version_Object_' . $version . "_$i";

                if (class_exists($class_name)) {
                    return new $class_name();
                }
            }
        }

        return new Version_Object();
    }

} 