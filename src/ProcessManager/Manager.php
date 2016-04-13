<?php

namespace BlackholeBot\ProcessManager;

use BlackholeBot\Clients\IrcClient;
use BlackholeBot\Clients\XmppClient;
use BlackholeBot\Exceptions\ForkingException;
use Symfony\Component\DependencyInjection\Container;

class Manager
{
    /**
     * @var Container
     */
    private $container;

    /**
     * @var array
     */
    private $children = [];

    /**
     * Manager constructor.
     *
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * @throws \Exception
     */
    public function initialize()
    {
        // Start IRC Client
        $this->startIrcClient();

        // Start the XMPP Client
        $this->startXmppClient();

        // Wait for children to join
        $this->waitForChildren();
    }

    /**
     * Tells the bot to shutdown
     */
    public function shutdown()
    {
        foreach ($this->children as $pid) {
            posix_kill($pid, SIGTERM);
        }
    }

    /**
     * @throws ForkingException
     */
    private function startIrcClient()
    {
        $this->children[] = (new IrcClient($this->container))->init();
    }

    /**
     * @throws ForkingException
     */
    private function startXmppClient()
    {
        $this->children[] = (new XmppClient($this->container))->init();
    }

    /**
     * Waits for children to join
     */
    private function waitForChildren()
    {
        // Wait for children to join
        while (count($this->children) > 0) {
            foreach ($this->children as $key => $pid) {
                $res = pcntl_waitpid($pid, $status, WNOHANG);

                // If the process has already exited
                if ($res == -1 || $res > 0) {
                    unset($this->children[$key]);
                }
            }

            sleep(1);
        }
    }
}