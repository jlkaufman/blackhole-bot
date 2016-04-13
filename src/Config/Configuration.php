<?php

namespace BlackholeBot\Config;

use Psr\Log\LogLevel;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Container;

class Configuration implements ConfigurationInterface
{
    /**
     * @var Container
     */
    private $container;

    /**
     * Configuration constructor.
     *
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;

        $this->loadConfig();

    }

    /**
     * Loads the config
     */
    private function loadConfig()
    {
        $directories = [BASE_DIR . '/config', '/etc/blackhole-bot/'];

        $locator = new FileLocator($directories);
        $loader  = new YamlConfigLoader($locator);


        $this->container->set('config', (new Processor())->processConfiguration(
            $this,
            $loader->load($locator->locate('blackhole.yml'))
        ));
    }

    /**
     * @return TreeBuilder
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();

        $rootNode = $treeBuilder->root('blackhole');

        $rootNode
            ->children()
            /**
             * DNS
             */
                ->scalarNode('dns_server')
                    ->isRequired()
                    ->cannotBeEmpty()
                ->end()
            /**
             * Daemon
             */
                ->arrayNode('daemon')
                    ->children()
                        ->arrayNode('user')
                            ->children()
                                ->integerNode('uid')
                                    ->isRequired()
                                    ->min(0)
                                ->end()
                                ->integerNode('gid')
                                    ->isRequired()
                                    ->min(0)
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            /**
             * Logging
             */
                ->enumNode('log_level')
                    ->values([
                        LogLevel::EMERGENCY,
                        LogLevel::ALERT,
                        LogLevel::CRITICAL,
                        LogLevel::ERROR,
                        LogLevel::WARNING,
                        LogLevel::NOTICE,
                        LogLevel::INFO,
                        LogLevel::DEBUG
                    ])
                    ->isRequired()
                    ->cannotBeEmpty()
                ->end()
            /**
             * IRC
             */
                ->arrayNode('irc')
                    ->children()
                        ->scalarNode('nickname')
                            ->isRequired()
                            ->cannotBeEmpty()
                        ->end()
                        ->scalarNode('username')
                            ->isRequired()
                            ->cannotBeEmpty()
                        ->end()
                        ->scalarNode('realname')
                            ->isRequired()
                            ->cannotBeEmpty()
                        ->end()
                        ->arrayNode('server')
                            ->children()
                                ->scalarNode('host')
                                    ->isRequired()
                                    ->cannotBeEmpty()
                                ->end()
                                ->integerNode('port')
                                    ->min(1)
                                    ->max(65535)
                                    ->isRequired()
                                    ->defaultValue(6697)
                                ->end()
                                ->booleanNode('ssl')
                                    ->defaultValue('true')
                                    ->isRequired()
                                ->end()
                            ->end()
                        ->end()
                        ->scalarNode('channel')
                            ->isRequired()
                            ->cannotBeEmpty()
                        ->end()
                    ->end()
                ->end()
            /**
             * XMPP
             */
                ->arrayNode('xmpp')
                    ->children()
                        ->arrayNode('account')
                            ->children()
                                ->scalarNode('jid')
                                    ->isRequired()
                                    ->cannotBeEmpty()
                                ->end()
                                ->scalarNode('password')
                                    ->isRequired()
                                    ->cannotBeEmpty()
                                ->end()
                            ->end()
                        ->end()
                        ->scalarNode('conference')
                            ->isRequired()
                            ->cannotBeEmpty()
                        ->end()
                        ->scalarNode('nickname')
                            ->isRequired()
                            ->cannotBeEmpty()
                        ->end()
                        ->arrayNode('server')
                            ->children()
                                ->scalarNode('host')
                                    ->isRequired()
                                    ->cannotBeEmpty()
                                ->end()
                                ->integerNode('port')
                                    ->min(1)
                                    ->max(65535)
                                    ->isRequired()
                                    ->defaultValue(5222)
                                ->end()
                                ->scalarNode('auth_type')
                                    ->isRequired()
                                    ->cannotBeEmpty()
                                    ->defaultValue('DIGEST-MD5')
                                ->end()
                                ->arrayNode('ssl')
                                    ->children()
                                        ->booleanNode('force_tls')
                                            ->isRequired()
                                            ->defaultValue(true)
                                        ->end()
                                        ->booleanNode('verify_peer')
                                            ->isRequired()
                                            ->defaultValue(true)
                                        ->end()
                                        ->booleanNode('allow_self_signed')
                                            ->isRequired()
                                            ->defaultValue(false)
                                        ->end()
                                            ->booleanNode('verify_peer_name')
                                            ->isRequired()
                                            ->defaultValue(true)
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}