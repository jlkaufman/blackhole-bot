<?php

namespace BlackholeBot\ProcessManager\Sockets;

/**
 * This class will handle socket management
 *
 * @package BlackholeBot\ProcessManager\Sockets
 */
class SocketManager
{
    /**
     * Array of sockets
     *
     * @var array
     */
    protected $sockets = [];

    /**
     * Closes all open sockets
     */
    public function __destruct()
    {
        foreach ($this->sockets as $socket) {
            unset($socket);
        }
    }

    /**
     * Gets a socket. If the socket doesn't exist, one will be created.
     *
     * @param      $name
     * @param bool $blocking Only affects new sockets.
     *
     * @return mixed
     */
    public function getSocket($name, $blocking = false)
    {
        if (!isset($this->sockets[$name])) {
            $this->sockets[$name] = new Socket($blocking);
        }

        return $this->sockets[$name];
    }

    /**
     * Closes a socket
     *
     * @param $name
     *
     * @return $this
     */
    public function closeSocket($name)
    {
        if (isset($this->sockets[$name])) {
            unset($this->sockets[$name]);
        }

        return $this;
    }

    /**
     * Prevent cloning of this object
     */
    private function __clone()
    {
    }
}