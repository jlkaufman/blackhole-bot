<?php
namespace BlackholeBot\Clients;

class XmppClient extends Client
{
    const READ_RATE = 1000000;

    const STATUS_MESSAGE = "RelayBot";

    const STATUS = "chat";

    /**
     * @var \JAXL
     */
    public $client;

    /**
     * Connect to XMPP
     */
    public function run()
    {
        /**
         * Configure the client
         */
        $this->configure();

        /**
         * Callbacks
         */
        $this->attachAuthSuccessListener();

        $this->attachAuthFailureListener();

        $this->attachGroupChatMessageListener();

        $this->attachOnDisconnectListener();

        /**
         * Start the client
         */
        $this->startClient();
    }

    /**
     * Configures the XMPP Client
     */
    private function configure()
    {
        /**
         * It's actually really important that this class does not get defined in the __construct() function
         * because it sets it's own signal handlers, and will take over ours leading to zombie processes after
         * the parent gets killed.
         *
         * This needs to be instansiated AFTER this has forked into it's own process.
         */

        $config = $this->getContainer()->get('config');

        $this->client = new \JAXL([
            'jid'            => $config['xmpp']['account']['jid'],
            'pass'           => $config['xmpp']['account']['password'],
            'host'           => $config['xmpp']['server']['host'],
            'port'           => $config['xmpp']['server']['port'],
            'resource'       => 'bot' . md5(time()),
            'priv_dir'       => sys_get_temp_dir() . '/.jaxl',
            'force_tls'      => $config['xmpp']['server']['ssl']['force_tls'],
            'log_level'      => JAXL_INFO,
            'auth_type'      => $config['xmpp']['server']['auth_type'],
            'strict'         => true,
            'stream_context' => stream_context_create([
                'ssl' => [
                    'verify_peer'       => $config['xmpp']['server']['ssl']['verify_peer'],
                    'allow_self_signed' => $config['xmpp']['server']['ssl']['allow_self_signed'],
                    'verify_peer_name'  => $config['xmpp']['server']['ssl']['verify_peer_name']
                ]
            ])
        ]);

        $this->client->require_xep([
            '0045',    // MUC
            '0203',    // Delayed Delivery
            '0199'     // XMPP Ping
        ]);
    }

    /**
     * Attaches the Auth Success listener
     */
    private function attachAuthSuccessListener()
    {
        $this->client->add_cb('on_auth_success', function () {

            /**
             * Set status
             */
            $this->client->set_status(self::STATUS_MESSAGE, self::STATUS, 10);

            /**
             * Join the MUC
             */
            $this->client->xeps['0045']->join_room(new \XMPPJid(
                $this->getContainer()->get('config')['xmpp']['conference']
                . '/'
                . $this->getContainer()->get('config')['xmpp']['nickname']
            ));

            /**
             * Attach the stream reader. This has to be done inside a callback
             * so that JAXL's clock is already instantiated.
             */
            $this->attachStreamReader();
        });
    }

    /**
     * Attaches the stream reader that will write to the MUC
     */
    private function attachStreamReader()
    {
        /**
         * @var \JAXLClock $clock
         */
        $clock = \JAXLLoop::$clock;

        $clock->call_fun_periodic(self::READ_RATE, function ()  {
            $text = $this->ircStream->read();

            if (!empty($text)) {
                $messageParts = explode(' ', $text);
                $nickName = str_replace(['<', '>'], '', $messageParts[0]);

                if (
                    $nickName != $this->getContainer()->get('config')['xmpp']['nickname']
                    && $nickName != $this->getContainer()->get('config')['irc']['nickname']
                ) {
                    $message = new \XMPPStanza('message');

                    $message->type = 'groupchat';
                    $message->to   = $this->getContainer()->get('config')['xmpp']['conference'];
                    $message->body = $text;

                    $this->getContainer()->get('logger')->debug(
                        'Found a message in the IRC socket. Writing it to the XMPP Channel'
                    );

                    $this->client->send($message);
                }

            }

        });
    }

    /**
     * Handles failed authentication
     */
    private function attachAuthFailureListener()
    {
        $this->client->add_cb('on_auth_failure', function ($reason){
            $this->getContainer()->get('logger')->alert(sprintf(
                'Received an Auth Failure: %s',
                $reason
            ));

            $this->client->send_end_stream();
        });
    }

    /**
     * Handles receiving a message from the MUC
     */
    private function attachGroupChatMessageListener()
    {
        $this->client->add_cb('on_groupchat_message', function ($stanza) {
            $from = new \XMPPJid($stanza->from);

            if ($from->resource) {
                $this->getContainer()->get('logger')->debug(
                    'Found a message in the XMPP channel. Writing it to the IRC Socket'
                );

                $this->xmppStream->write(sprintf(
                    "<%s> %s\n",
                    $from->resource,
                    $stanza->body
                ));
            }
        });
    }

    /**
     * Handles disconnections
     */
    private function attachOnDisconnectListener()
    {
        $this->client->add_cb('on_disconnect', function () {
            $this->getContainer()->get('logger')->warn(
                'Got disconnected from XMPP. Reconnecting.'
            );

            $this->startClient();
        });
    }

    /**
     * Starts the client
     */
    private function startClient()
    {
        $this->client->start();
    }
}