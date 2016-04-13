<?php

namespace BlackholeBot\Process;


use BlackholeBot\Exceptions\AlreadyRunningException;
use BlackholeBot\Exceptions\DaemonizeException;
use Symfony\Component\DependencyInjection\Container;

/**
 * This class handles the daemonization process
 *
 * @package BlackholeBot\Process
 */
class Daemon
{
    const PID_DIR = '/var/run/BlackholeBot/';

    const PID =  self::PID_DIR . 'bot.pid';

    /**
     * @var Container
     */
    private $container;

    /**
     * @var array
     */
    private $config = [];

    /**
     * @var int
     */
    private $sid;

    /**
     * Daemon constructor.
     *
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->config = $this->container->get('config');
    }

    /**
     * Daemonizes the process
     */
    public function daemonize()
    {
        $this->checkIfRoot();

        $this->checkIfAlreadyRunning();

        $this->changeUMask();

        $this->forkProcess();

        $this->setSID();

        $this->writeSID();

        $this->setIds();

        $this->closeFileDescriptors();

        $this->redirectIO();
    }



    /**
     * Removes the PID file
     */
    public function removePidFile()
    {
        if (is_file(self::PID) && is_readable(self::PID)) {
            unlink(self::PID);
        }
    }

    /**
     * @throws DaemonizeException
     */
    private function checkIfRoot()
    {
        if (posix_getuid() != 0) {
            throw new DaemonizeException('You must start the bot as root!');
        }
    }

    /**
     * Checks if the daemon is already running
     * 
     * @throws AlreadyRunningException
     */
    private function checkIfAlreadyRunning()
    {
        if (is_file(self::PID) && is_readable(self::PID)) {
            $pid = file_get_contents(self::PID);
        }

        if (isset($pid) && is_numeric($pid) && posix_kill($pid, SIG_DFL)) {
            throw new AlreadyRunningException('Process already running!');
        }
    }

    /**
     * @throws DaemonizeException
     */
    private function setSID()
    {
        $this->sid = posix_setsid();

        if ($this->sid < 0) {
            throw new DaemonizeException('Could not set SID');
        }
    }

    /**
     * Writes the pid file
     */
    private function writeSID()
    {
        if (!file_exists(self::PID_DIR)) {
            if (!mkdir(self::PID_DIR)) {
                throw new DaemonizeException('Could not create PID directory... Are you root?');
            }
        }

        file_put_contents(self::PID, $this->sid);
    }

    /**
     * Forks the process
     *
     * @throws DaemonizeException
     */
    private function forkProcess()
    {
        $pid = pcntl_fork();

        switch ($pid) {
            case -1:
                throw new DaemonizeException('Could not fork...');
                break;
            case 0:
                break;
            default:
                exit(0);
                break;
        }
    }

    /**
     * Sets the processes umask
     */
    private function changeUMask()
    {
        umask(0);
    }

    /**
     * De-escalates privileges
     */
    private function setIds()
    {
        posix_seteuid($this->config['daemon']['user']['uid']);
        posix_setegid($this->config['daemon']['user']['gid']);
    }

    /**
     * Closes file descriptors
     */
    private function closeFileDescriptors()
    {
        fclose(STDIN);
        fclose(STDOUT);
        fclose(STDERR);
    }

    /**
     * Redirects output
     */
    private function redirectIO()
    {
        /** @noinspection PhpUnusedLocalVariableInspection */
        $stdIn  = fopen('/dev/null', 'r'); // set fd/0

        /** @noinspection PhpUnusedLocalVariableInspection */
        $stdOut = fopen('/dev/null', 'w'); // set fd/1

        /** @noinspection PhpUnusedLocalVariableInspection */
        $stdErr = fopen('php://stdout', 'w'); // a hack to duplicate fd/1 to 2
    }
}