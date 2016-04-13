<?php
/**
 * The Bot.
 *
 * This class is responsible for starting and stopping the bot.
 */
namespace BlackholeBot;

use Symfony\Component\DependencyInjection\Container;

class Bot
{
    /**
     * @var Container
     */
    private $container;

    /**
     * Bot constructor.
     *
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Starts the bot up
     */
    public function start()
    {
        $this->container->get('logger')->info('Starting the bot...');
        $this->container->get('processManager')->initialize();
    }

    /**
     * Shuts the bot down
     */
    public function stop()
    {
        $this->container->get('logger')->info('Shutting down the bot...');

        $this->container->get('processManager')
                        ->shutdown();

        $this->removeTempDirectories();
    }

    /**
     * Remove temp dirs
     */
    private function removeTempDirectories()
    {
        $directories = [
            sys_get_temp_dir() . '/.jaxl'
        ];

        foreach ($directories as $directory) {
            if (file_exists($directory)) {
                $this->recursiveDirectoryDelete($directory);

                rmdir($directory);

                $this->container->get('logger')->debug('Removed directory: ' . $directory);
            }
        }

    }

    /**
     * @param $dir
     */
    private function recursiveDirectoryDelete($dir)
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(
                $dir,
                \FilesystemIterator::SKIP_DOTS
            ),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $filename => $fileInfo) {
            if ($fileInfo->isDir()) {
                rmdir($filename);

                $this->container->get('logger')->debug('Removed directory: ' . $filename);
            } else {
                unlink($filename);

                $this->container->get('logger')->debug('Removed file: ' . $filename);
            }
        }
    }
}