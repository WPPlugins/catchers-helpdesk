<?php

namespace StgHelpdesk\Helpers;

use PhpImap\Mailbox;

class Stg_Helpdesk_Mailbox
{
    protected static $instance;
    protected static $connection;
    protected $server;
    protected $port;
    protected $login;
    protected $password;
    /**
     * @var bool
     */
    protected $ssl;
    /**
     * @var bool
     */
    protected $validateCert;
    /**
     * @var bool
     */
    protected $tls;
    /**
     * @var
     */
    protected $attachmentDir;

    private function __construct(
        $server,
        $port,
        $login,
        $password,
        $attachmentDir,
        $ssl = false,
        $validateCert = false,
        $tls = false
    )
    {
        $this->server = $server;
        $this->port = $port;
        $this->login = $login;
        $this->password = $password;
        $this->ssl = $ssl;
        $this->validateCert = $validateCert;
        $this->tls = $tls;
        $this->attachmentDir = $attachmentDir;
    }

    /**
     * @param $server
     * @param $port
     * @param $login
     * @param $password
     * @param $attachmentDir
     * @param bool|false $ssl
     * @param bool|false $validateCert
     * @param bool|false $tls
     * @return static
     */
    public static function instance(
        $server,
        $port,
        $login,
        $password,
        $attachmentDir,
        $ssl = false,
        $validateCert = false,
        $tls = false
    )
    {
        if (is_null(static::$instance)) {
            static::$instance = new static($server, $port, $login, $password, $attachmentDir, $ssl, $validateCert,
                $tls);
        }

        return static::$instance;
    }

    /**
     * @param string $protocol
     * @return Mailbox
     */
    public function connect($protocol)
    {
        $dns = $this->generateConnectionString($protocol);
        $tmpDir = get_temp_dir();

        if (!self::$connection) {
            self::$connection = new Mailbox($dns, $this->login, $this->password, $tmpDir);

            if (version_compare(phpversion(), '5.3.2', '>')) {
                self::$connection->setConnectionArgs(0, 0, array('DISABLE_AUTHENTICATOR' => 'GSSAPI'));
            }
        }

        return self::$connection;
    }

    /**
     * @return void
     */
    public function disconnect()
    {
        self::$connection = null;
    }

    protected function generateConnectionString($protocol)
    {
        $option = '/' . $protocol;

        if ($this->ssl) {
            $option .= '/ssl';
        }

        if ($this->tls) {
            $option .= '/tls';
        }

        if (!$this->validateCert) {
            $option .= '/novalidate-cert';
        }

        if (preg_match("/google|gmail/i", $this->server)) {
            $connection = "{" . $this->server . ":" . $this->port . $option . "}INBOX";
        } else {
            $connection = "{" . $this->server . ":" . $this->port . $option . "}";
        }

        return $connection;
    }
}