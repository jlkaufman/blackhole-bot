<?php

namespace BlackholeBot\ProcessManager\Sockets;

/**
 * This class represents a bidirectional socket
 *
 * @package BlackholeBot\ProcessManager\Sockets
 */
class Socket
{
    const READ = 0;

    const WRITE = 1;

    /**
     * @var array
     */
    private $socket;

    /**
     * Opens up a new socket pair
     *
     * @param bool $blocking
     */
    public function __construct($blocking = false)
    {
        $this->socket = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);

        foreach ([self::READ, self::WRITE] as $socket) {
            stream_set_blocking($this->socket[$socket], $blocking);
        }
    }

    /**
     * Closes the socket
     */
    public function __destruct()
    {
        foreach ([self::READ, self::WRITE] as $socket) {
            fclose($this->socket[$socket]);
        }
    }

    /**
     * Write to the socket
     *
     * @param $data
     *
     * @return $this
     */
    public function write($data)
    {
        fwrite($this->socket[self::WRITE], $data);

        return $this;
    }

    /**
     * Read a line from the socket
     *
     * @return string
     */
    public function read()
    {
        return fgets($this->socket[self::READ]);
    }

    /**
     * Prevent cloning of this object
     */
    private function __clone()
    {
    }
}