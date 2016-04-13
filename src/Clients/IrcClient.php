<?php

namespace BlackholeBot\Clients;

use Phergie\Irc\Client\React\Client as ReactClient;
use Phergie\Irc\Client\React\WriteStream;
use Phergie\Irc\Connection;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\DependencyInjection\Container;

class IrcClient extends Client
{
    /**
     * @var bool|WriteStream
     */
    public $write = false;

    /**
     * @var ReactClient
     */
    public $client;

    /**
     * @var Connection
     */
    public $connection;

    /**
     * @var bool
     */
    public $connected = false;

    /**
     * IrcClient constructor.
     *
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        parent::__construct($container);

        $this->connection = new Connection();
        $this->client     = new ReactClient();
    }

    /**
     * Shut down the IRC connection
     */
    public function __destruct()
    {
        $this->client->removeAllListeners();

        unset($this->client);
        unset($this->connection);
        unset($this);
    }

    /**
     * Connect to IRC and attach some event listeners
     */
    protected function run()
    {
        /**
         * Configure the IRC Client
         */
        $this->configure();

        /**
         * Read messages from the stream
         */
        $this->attachReadListener();

        /**
         * Attach the socket listener to write messages to the channel
         * when received from XMPP
         */
        $this->attachSocketReadTimer();

        /**
         * Attach the disconnect listener
         */
        $this->attachDisconnectListener();


        $this->client->run($this->connection);
    }

    /**
     * Configures the IRC Client
     */
    private function configure()
    {
        $this->connection
            ->setServerHostname($this->getContainer()->get('config')['irc']['server']['host'])
            ->setServerPort($this->getContainer()->get('config')['irc']['server']['port'])
            ->setNickname($this->getContainer()->get('config')['irc']['nickname'])
            ->setUsername($this->getContainer()->get('config')['irc']['username'])
            ->setRealname($this->getContainer()->get('config')['irc']['realname']);

        if ($this->getContainer()->get('config')['irc']['server']['ssl']) {
            $this->connection->setOption('transport', 'ssl');
        }


        $this->client->setLogger($this->getContainer()->get('logger'));


        $this->client->setDnsServer($this->getContainer()->get('config')['dns_server']);
    }

    /**
     * Attaches the reading listener
     */
    private function attachReadListener()
    {
        $this->client->on(
            'irc.received',
            function ($message, $writeStream) {

                /**
                 * Store the write stream so we can use it later...
                 */
                if ($this->write === false) {
                    $this->write = $writeStream;
                }

                /**
                 * Join the configured channel once connected
                 */
                if (isset($message['code']) && $message['code'] == 'RPL_ENDOFMOTD') {
                    $this->write->ircJoin($this->getContainer()->get('config')['irc']['channel']);

                    $this->connected = true;

                    $this->getContainer()->get('logger')->info('Connected to IRC');

                    return;
                }


                /**
                 * Respond to PING
                 */
                if (
                    isset($message['command'])
                    && $message['command'] === 'PING'
                ) {
                    $this->getContainer()->get('logger')->debug('Responding to PING with a PONG');

                    $this->write->ircPong($message['params']['server1']);

                    return;
                }

                /**
                 * Read the chat, and send messages to the socket
                 */
                if (
                    isset($message['command'])
                    && $message['command'] === 'PRIVMSG'
                    && !isset($message['ctcp'])
                ) {
                    $nick    = $message['nick'];
                    $text    = $message['params']['text'];
                    $channel = $message['params']['receivers'];

                    if (
                        $channel == $this->getContainer()->get('config')['irc']['channel']
                        && $nick != $this->getContainer()->get('config')['irc']['nickname']
                    ) {
                        $this->getContainer()->get('logger')->debug(
                            'Found a message in the IRC channel. Writing it to the XMPP Socket'
                        );


                        $this->ircStream->write(sprintf(
                            "<%s> %s",
                            $nick,
                            $text
                        ));
                    }

                    return;
                }

            }
        );
    }

    /**
     * Reads from the XMPP Socket and writes to the IRC channel
     */
    private function attachSocketReadTimer()
    {
        /**
         * Read messages from the socket and write them to the channel
         *
         * @noinspection PhpParamsInspection
         */
        $this->client->addPeriodicTimer(1, function () {
            if (!$this->connected) {
                return;
            }

            $text = $this->xmppStream->read();

            if (!empty($text)) {
                $messageParts = explode(' ', $text);
                $nickName     = str_replace(['<', '>'], '', $messageParts[0]);

                if (
                    $nickName != $this->getContainer()->get('config')['xmpp']['nickname']
                    && $nickName != $this->getContainer()->get('config')['irc']['nickname']
                ) {
                    $this->getContainer()->get('logger')->debug(
                        'Found a message in the XMPP socket. Writing it to the IRC channel'
                    );


                    $this->write->ircPrivmsg(
                        $this->getContainer()->get('config')['irc']['channel'],
                        htmlspecialchars_decode($text)
                    );
                }
            }

        });
    }

    /**
     * Watches for a DC and reconnects to IRC
     */
    private function attachDisconnectListener()
    {
        $this->client->on(
            'connect.end',
            function (Connection $connection, LoggerInterface $logger) {
                /**
                 * @var Connection $connection
                 * @var Client     $client
                 */
                $logger->debug('Connection to ' . $connection->getServerHostname() . ' lost, attempting to reconnect');
                $this->client->addConnection($connection);
            }
        );
    }
}