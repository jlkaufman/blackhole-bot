<?php

namespace BlackholeBot\Logger;

use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Container;

class Logger extends AbstractLogger
{
    /**
     * @var array
     */
    private $levelMap = [
        LogLevel::EMERGENCY => LOG_EMERG,
        LogLevel::ALERT     => LOG_ALERT,
        LogLevel::CRITICAL  => LOG_CRIT,
        LogLevel::ERROR     => LOG_ERR,
        LogLevel::WARNING   => LOG_WARNING,
        LogLevel::NOTICE    => LOG_NOTICE,
        LogLevel::INFO      => LOG_INFO,
        LogLevel::DEBUG     => LOG_DEBUG,
    ];

    /**
     * @var string
     */
    private $logLevel;

    /**
     * @var Container
     */
    private $container;

    /**
     * Logger constructor.
     *
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->logLevel  = $this->container->get('config')['log_level'];
    }

    /**
     * @param mixed  $level
     * @param string $message
     * @param array  $context
     *
     * @return null|void
     */
    public function log($level, $message, array $context = array())
    {
        $translatedLevel = $this->levelMap[$level];

        $this->container->get('output')->writeln(
            sprintf('%s: %s', $level, $message),
            OutputInterface::VERBOSITY_VERBOSE
        );

        if ($translatedLevel > $this->levelMap[$this->logLevel]) {
            return;
        }

        syslog($translatedLevel, $message);
    }
}