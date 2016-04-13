<?php
declare (ticks = 1);

namespace BlackholeBot\Commands;

use BlackholeBot\Bot;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Container;


class BotCommand extends Command
{
    /**
     * @var Container
     */
    protected $container;

    /**
     * @var Bot $bot
     */
    private $bot;

    /**
     * BotCommand constructor.
     *
     * @param Container $container
     */
    public function __construct($container)
    {
        $this->container = $container;

        parent::__construct(null);
    }


    /**
     * Handle signals
     *
     * @param $signal
     */
    public function signalHandler($signal)
    {
        switch ($signal) {
            case SIGTERM:
            case SIGINT:
                $this->bot->stop();
                $this->container->get('daemon')->removePidFile();
                break;

            default:
                break;

        }
    }

    /**
     * Configuration of the command
     */
    protected function configure()
    {
        $this
            ->setName('bot')
            ->setDescription('Jabber - IRC Relay Bot')
            ->addOption(
                'daemon',
                'd',
                InputOption::VALUE_NONE,
                'Run the bot in the background. (Must be run as root)'
            );

    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /**
         * Make sure the config is parsed
         */
        $this->container->get('configuration');

        /**
         * Set some more services
         */
        $this->container->set('input', $input);
        $this->container->set('output', $output);

        if ($input->getOption('daemon')) {
            $this->container->get('daemon')->daemonize();

            $this->container->get('logger')->info('Daemonizing');
        }

        $this->setSignalHandlers();

        $this->bot = new Bot($this->container);

        $this->bot->start();
    }

    /**
     * Sets the signal handlers that we use during execution
     */
    private function setSignalHandlers()
    {
        $this->container->get('logger')->debug('Setting signal handlers');

        pcntl_signal(SIGTERM, [$this, 'signalHandler']);
        pcntl_signal(SIGINT, [$this, 'signalHandler']);
        pcntl_signal(SIGHUP, [$this, 'signalHandler']);
    }


    /**
     * Prevent cloning of this object
     */
    private function __clone()
    {
    }
}