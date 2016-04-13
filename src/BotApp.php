<?php

namespace BlackholeBot;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputInterface;

class BotApp extends Application
{
    /**
     * Gets the name of the command based on input.
     *
     * @param InputInterface $input The input interface
     *
     * @return string The command name
     */
    protected function getCommandName(InputInterface $input)
    {
        // This should return the name of your command.
        return 'bot';
    }
}


