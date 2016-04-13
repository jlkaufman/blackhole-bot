<?php
declare (ticks = 1);

namespace BlackholeBot\Clients;

use BlackholeBot\Exceptions\ForkingException;
use BlackholeBot\ProcessManager\Sockets\Socket;
use Symfony\Component\Console\Input\Input;
use Symfony\Component\DependencyInjection\Container;

abstract class Client
{
    /**
     * @var Socket
     */
    public $ircStream;

    /**
     * @var Socket
     */
    public $xmppStream;

    /**
     * @var array
     */
    private $config;

    /**
     * @var Input
     */
    private $input;

    /**
     * @var Container
     */
    private $container;


    /**
     * Client constructor.
     *
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;

        $this->config = $this->container->get('config');
        $this->input  = $this->container->get('input');

        $this->ircStream  = $this->getContainer()
                                 ->get('socketManager')
                                 ->getSocket('irc');
        $this->xmppStream = $this->getContainer()
                                 ->get('socketManager')
                                 ->getSocket('xmpp');
    }

    /**
     * @return Container
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * @return int
     * @throws \Exception
     */
    public function init()
    {
        $pid = pcntl_fork();

        switch ($pid) {
            case -1:
                throw new ForkingException('Could not fork...');
                break;
            case 0:
                $this->setSignalHandlers();
                $this->run();
                break;
            default:
                break;
        }

        return $pid;
    }

    /**
     * Kills the client
     */
    public function stop()
    {
        unset($this);

        exit;
    }

    /**
     * Starts up the client
     * @return mixed
     */
    abstract protected function run();

    /**
     * Sets signal handlers for the children processes
     */
    private function setSignalHandlers()
    {
        pcntl_signal(SIGTERM, [$this, 'stop']);
        pcntl_signal(SIGINT, [$this, 'stop']);
    }
}