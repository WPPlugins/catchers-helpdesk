<?php
namespace StgHelpdesk\Helpers;

/**
 * Class Stg_Helper_Logger
 * @package StgHelpdesk\Helpers
 */

class Stg_Helper_Logger
{
    private $_logsPath;
    protected static $loggers = array();

    protected $name;
    protected $file;
    protected $fp;

    private $_enabled = false;

    public function __construct($name, $file = null)
    {
//        if(isset($_REQUEST['stglen']) && $_REQUEST['stglen'] == STG_HELPDESK_SALT_USER)
//            $this->_enabled = true;


        if(!$this->_enabled)
            return;

        $this->name = $name;
        $this->file = $file;
        $this->_logsPath = STG_HELPDESK_ROOT . "logs";
        $this->open();
    }

    public function open()
    {
        $this->fp = fopen($this->file == null ? $this->_logsPath . '/' . $this->name . '.log' : $this->_logsPath . '/' . $this->file, 'a+');
    }


    /**
     * @param string $name
     * @param null $file
     * @return Stg_Helper_Logger
     */
    public static function getLogger($name = 'root', $file = null)
    {
        if (!isset(self::$loggers[$name])) {
            self::$loggers[$name] = new Stg_Helper_Logger($name, $file);
        }

        return self::$loggers[$name];
    }

    public function log($message)
    {
        if(!$this->_enabled)
            return;

        if (!is_string($message)) {
            $this->logPrint($message);
            return;
        }

        $log = '';

        $log .= '[' . date('D M d H:i:s Y', time()) . '] ';
        if (func_num_args() > 1) {
            $params = func_get_args();

            $message = call_user_func_array('sprintf', $params);
        }

        $log .= $message;
        $log .= "\n";

        $this->_write($log);
    }

    public function logPrint($obj)
    {
        ob_start();

        print_r($obj);

        $ob = ob_get_clean();
        $this->log($ob);
    }

    protected function _write($string)
    {
        fwrite($this->fp, $string);
    }

    public function __destruct()
    {
        if(!$this->_enabled)
            return;

        fclose($this->fp);
    }
}
